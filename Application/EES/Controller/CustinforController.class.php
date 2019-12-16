<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-01-05
 * Time: 10:02
 */

namespace EES\Controller;

use Think\Log;
use Admin\Controller\CustomerController;
class CustinforController extends EESController
{
	
	/**
	 * 获取相似客户列表
	 **/
	public function getSameUserData( $userId )
	{
		$baseInfo = M( 'user' )->field( 'user_name,user_mobile,user_type,user_email,nick_name as user_nick' )->where( [ 'id' => $userId ] )->find();
		if ( !$baseInfo ) {
			$this->ajaxReturn( [], false );
		}
		//查询详细信息
		if ( $baseInfo[ 'user_type' ] == 2 || $baseInfo[ 'user_type' ] == 20 ) {
			if ( $baseInfo[ 'user_type' ] == 20 ) {
				$pUser = M( 'user_son' )->field( 'p_id' )->where( [
					'user_id'   => $userId,
					'is_delete' => 0,
				] )->find();
				if ( $pUser ) {
					$userId = $pUser[ 'p_id' ];
				} else {
					$this->ajaxReturn( [], false );
				}
			}
			$baseDetail = M( 'user_company' )->where( [ 'user_id' => $userId ] )->find();
			$baseDetail && $baseInfo[ 'info' ] = $baseDetail;
			$baseInfo[ 'user_type' ] = 2;
		}
		
		//查询收货地址信息数据
		$addressData = M( 'user_order_address' )->field( 'consignee,area_code,address,zipcode,mobile' )->where( [ 'user_id' => $userId ] )->select();
		$addressData && $baseInfo[ 'address' ] = $addressData;
		
		$this->ajaxReturn( $baseInfo );
	}
	
	protected function checkUserName( $userName )
	{
		if(in_array($userName[0],['0','1','2','3','4','5','6','7','8','9']) ){
			return false;
		}
		$count = M( 'user' )->where( [ 'user_name' => $userName ] )->count('id');
		return (int)$count === 0 ? true : false;
	}
	
	protected function createUserName( $length = 6 )
	{
		$flag = false;
		while ( $flag == false ){
			$name = generate_username( $length );
			$flag = $this->checkUserName( $name );
		}
		return $name;
	}
	
	//AJAX 上传导入的客户数据
	public function getUpCustData()
	{
		$flag = generate_username( 8 );
		$upPath = SITE_PATH . 'Uploads' . DS . 'Ees' . DS . 'log' . DS;
		$uploads_dir = SITE_PATH . 'Uploads' . DS . 'Ees' . DS . 'data';
		$tmp_name = $_FILES[ 'file' ][ "tmp_name" ];
		$name = $flag . '_' . date( 'Y-m-d' ) . '_custData.json';
		$res = move_uploaded_file( $tmp_name, $uploads_dir . DS . $name );
		if ( $res ) {
			//纪录标记
			if ( !file_exists( $upPath . date( 'Y-m-d' ) ) ) {
				mkDirectory( $upPath . date( 'Y-m-d' ) );
			}
			file_put_contents( $upPath . date( 'Y-m-d' ) . DS . $flag . '.txt', json_encode( [
				'status' => 0,
				'flag'   => $flag,
			] ) );
			$this->ajaxReturn( [
				'error' => 0,
				'msg'   => '上传成功!',
				'flag'  => $flag,
			] );
		} else {
			$this->ajaxReturn( [
				'error' => 1,
				'msg'   => '上传失败!文件移动错误',
				'flag'  => $flag,
			] );
		}
	}
	
	//获取文件纪录是否完成
	public function getCustIsEnd( $flag )
	{
		$upPath = SITE_PATH . 'Uploads' . DS . 'Ees' . DS . 'log' . DS;
		$date = date( 'Y-m-d' );
		$status = file_get_contents( $upPath . $date . DS . $flag . '.txt' );
		$arr = json_decode( $status, true );
		if ( !$status ) {
			return 5;
		}
		if ( empty( $status ) || empty( $arr ) ) {
			return 3;
		}
		$status = (int)$arr[ 'status' ];
		
		return $status;
	}
	
	//ajax 查询处理客户导入是否完成
	public function doCustIsEnd( $flag )
	{
		$is = $this->getCustIsEnd( $flag );
		if ( $is === 5 ) {
			$this->ajaxReturn( [
				'error' => 5,
				'msg'   => '该标记不存在!',
			] );
		}
		if ( $is === 0 ) {
			$this->ajaxReturn( [
				'error' => 0,
				'msg'   => '标记未开始处理',
			] );
		}
		if ( $is === 1 ) {
			$this->ajaxReturn( [
				'error' => 1,
				'msg'   => '该标记数据正在处理中!',
			] );
		}
		if ( $is === 2 ) {
			$d_url = 'http://' . C( 'WEB_ROOT_DOMAIN' ) . '/Uploads/Ees/back/' . $flag . 'down.json';
			$this->ajaxReturn( [
				'error'     => 2,
				'msg'       => '该标记数据已导入完成',
				'down_path' => urlencode( $d_url ),
			] );
		}
		if ( $is === 3 ) {
			$this->ajaxReturn( [
				'error' => 3,
				'msg'   => '该标记数据读取错误!请重新上传文件',
			] );
		}
	}
	
	/*
	 * ajax 请求执行任务
	 */
	public function doCreateCust( $flag )
	{
		//获取客户是否处理完成
		$status = $this->getCustIsEnd( $flag );
		if ( $status === 5 ) {
			$this->ajaxReturn( [
				'error' => 5,
				'msg'   => '该标记不存在!',
			] );
		} elseif ( $status === 1 ) {
			$this->ajaxReturn( [
				'error' => 1,
				'msg'   => '该标记数据正在处理中!',
			] );
		} elseif ( $status === 2 ) {
			$d_url = 'https://' . C( 'WEB_ROOT_DOMAIN' ) . '/Uploads/Ees/back/' . $flag . '_down.json';
			$this->ajaxReturn( [
				'error'     => 2,
				'msg'       => '该标记数据已导入完成',
				'down_path' => $d_url ,
			] );
		} elseif ( $status === 3 ) {
			$this->ajaxReturn( [
				'error' => 3,
				'msg'   => '该标记数据读取错误!请重新上传文件',
			] );
		} elseif ( $status === 0 ) {
			_curl( 'https://' . C( 'WEB_ROOT_DOMAIN' ) . '/EES/Custinfor/createCustForUser?flag=' . $flag );
			$this->ajaxReturn( [
				'error' => 0,
				'msg'   => '数据开始处理!',
			] );
		}
		
	}
	
	/**
	 * 批量创建客户账户
	 **/
	public function createCustForUser( $flag )
	{
		//解决session阻塞
		session_write_close();
		$logPath = SITE_PATH . 'Uploads' . DS . 'Ees' . DS . 'log' . DS . date( 'Y-m-d' ) . DS;
		if ( !file_exists( $logPath ) ) {
			mkDirectory( $logPath );
		}
		$downPath = SITE_PATH . 'Uploads' . DS . 'Ees' . DS . 'back' . DS . date( 'Y-m-d' ) . DS;
		if ( !file_exists( $downPath ) ) {
			mkDirectory( $downPath );
		}
		$status = $this->getCustIsEnd( $flag );
		if($status !== 0 ){
			if( $status === 1 ){
				$lastStore = file_get_contents($logPath . $flag . '.txt');
				if ( $lastStore === '' ) {
					file_put_contents( $logPath . $flag . '.txt', json_encode( [
						'status'     => 3,
						'flag'       => $flag,
						'store'      => 0,
						'currCustNo' => '',
					] ) );
				}
				$lastStore = json_decode($lastStore,true);
			}else{
				return false;
			}
		}else{
			//纪录开始处理导入客户
			file_put_contents( $logPath . $flag . '.txt', json_encode( [
				'status'     => 1,
				'flag'       => $flag,
				'store'      => 0,
				'currCustNo' => '',
			] ) );
		}

		//忽略客户端是否断开 程序继续运行
		ignore_user_abort( true );
		set_time_limit( 0 );
		//客户数据
		$name = $flag . '_' . date( 'Y-m-d' ) . '_custData.json';
		$readString = file_get_contents( SITE_PATH . 'Uploads' . DS . 'Ees' . DS . 'data' . DS . $name );
		//读取数据
		if ( $readString === '' ) {
			file_put_contents( $logPath . $flag . '.txt', json_encode( [
				'status'     => 3,
				'flag'       => $flag,
				'store'      => 0,
				'currCustNo' => '',
			] ) );
		}
		$arr = explode( '|@|', $readString );
		$data = [];
		foreach ( $arr as $k => $v ) {
			if ( empty( $v ) || $v === PHP_EOL ) {
				unset( $arr[ $k ] );
				continue;
			}
			$data[] = json_decode( $v, true );
		}
		$success = $error = [];
		$i = 1;
		$sumCount = count( $data );
		foreach ( $data as $k => $v ) {
			if( isset($lastStore) ){
				$lastI = $lastStore['store'];
				$lastCustNo = $lastStore['currCustNo'];
				$i = ((int)$lastI)-1;
				if( $k < $i ){
					continue;
				}
			}
			//纪录:
			file_put_contents( $logPath . $flag . '.txt', json_encode( [
				'status'     => 1,
				'flag'       => $flag,
				'store'      => $i,
				'currCustNo' => $v[ 'fcustno' ],
			] ) );
			$i++;
			if ( empty( $v ) || !isset( $v[ 'fcustname' ] ) ) {
				$error[] = [
					'no'    => $v[ 'fcustno' ],
					'error' => '数据为空',
				];
				continue;
			}
			$detail = $baseInfo = $account = [];
			$custNo = $v[ 'fcustno' ];
			$custName = $v[ 'fcustname' ];
			$custJc = $v[ 'fcustjc' ];
			$fsale = $v[ 'fsale' ];
			$fsaleName = $v[ 'fsalename' ];
			//自定账期名称
			$since_name = $v[ 'since_name' ];
			//检查客户简要信息是否存在商城表中
			$model = M( 'user' );
			$userWhere['fcustno'] = $custNo;
			$userWhere['user_type'] = ['in',[1,2]];
			$exists = $model->field('id')->where( $userWhere )->find();
//			if( $exists ){
//				$error[] = [
//					'no'    => $v[ 'fcustno' ],
//					'error' => '客户已有绑定商城账户!该商城账户id为:'.$exists['id'],
//				];
//				continue;
//			}
			$model->startTrans();
			$custModel = M( '', 'erp_customer' );
			$count = $custModel->where( [ 'fcustno' => $custNo ] )->count('id');
			if ( $count <= 0 ) {
				//不存在
				$add = [
					'fcustno'   => $custNo,
					'fcustname' => $custName,
					'fcustjc'   => $custJc,
					'fsale'     => $fsale,
					'fsalename' => $fsaleName,
				];
				$addRes = $custModel->add( $add );
				
				if ( $addRes === false ) {
					Log::error( '添加客户' . $custNo . '的数据到表erp_customer数据添加失败,数据:' . json_encode( $add ) );
					$error[] = [
						'no'    => $v[ 'fcustno' ],
						'error' => '添加客户数据到表erp_customer失败',
					];
				}
			}
			
			//查询业务员是否存在
			$saleModel = M( '', 'sys_user' );
			$isSale = $saleModel->field( 'uid' )->where( [ 'FEmplNo' => $fsale ] )->find();
			if ( $isSale ) {
				$sys_uid = $isSale[ 'uid' ];
			}
			
			//dx_user表 基础信息
			$baseInfo = [
				'user_name'        => $this->createUserName(),
				//客户登录名
				'user_pass'        => hash_string( '123456' ),
				//客户登录密码
				'user_type'        => (int)$v[ 'user_type' ],
				//客户用户类型
				'nick_name'        => $v[ 'fcustjc' ],
				//客户简称
				'fcustno'          => $v[ 'fcustno' ],
				'fcustjc'          => $v[ 'fcustjc' ],
				'sys_uid'          => isset( $sys_uid ) ? $sys_uid : 0,
				'isedit_user_name' => 1,
			];
			
			//是企业用户 dx_company 详细企业信息
			if ( (int)floatval( $v[ 'user_type' ] ) === 2 ) {
				$detail = [
					'company_name'            => $v[ 'fcustname' ],
					'company_address'         => $v[ 'faddress' ]?$v[ 'faddress' ]:'',
					'company_phone_num'       => $v[ 'ftel' ] ? $v[ 'ftel' ] : '',
					'company_user_name'       => $v[ 'fcontactor1' ] ? $v[ 'fcontactor1' ] : $v[ 'fcontactor2' ]?$v[ 'fcontactor2' ]:'',
					'company_user_position'   => $v[ 'fduty1' ] ? $v[ 'fduty1' ] : $v[ 'fduty2' ]?$v[ 'fduty2' ]:'',
					'company_user_phone'      => $v[ 'fmobile1' ] ? $v[ 'fmobile1' ] : ( $v[ 'ftel1' ] ? $v[ 'ftel1' ] : ( $v[ 'fmobile2' ] ? $v[ 'fmobile2' ] : $v[ 'ftel2' ] ) )?( $v[ 'ftel1' ] ? $v[ 'ftel1' ] : ( $v[ 'fmobile2' ] ? $v[ 'fmobile2' ] : $v[ 'ftel2' ] ) ):'',
					'check_status'            => 1,
					'company_user_qq'         => $v[ 'fqq1' ]?$v[ 'fqq1' ]:'',
					'company_user_wechat'     => $v[ 'fmsn1' ]?$v[ 'fmsn1' ]:'',
					//企业人数
					'company_people_num'      => $v[ 'femplnum' ]?$v[ 'femplnum' ]:0,
					//企业所在城市
					'company_city'            => $v[ 'fcity' ]?$v[ 'fcity' ]:'',
					//年营业额
					'company_annual_turnover' => $v[ 'fyysummoney' ]?$v[ 'fyysummoney' ]:'',
					//企业销售渠道
					'company_sales_channel'   => $v[ 'fsalearea' ]?$v['fsalearea']:'',
					//企业营业性质
					'company_business_nature' => $v[ 'fyyxz' ]?$v[ 'fyyxz' ]:'',
					//企业经营品牌
					'company_business_brand'  => $v[ 'fjyxm' ]?$v[ 'fjyxm' ]:'',
				];
			} elseif ( (int)floatval( $v[ 'user_type' ] ) === 1 ) {
				$detail = [
					'address' => $v[ 'faddress' ],
				];
			}
			
			//用户账期信息
			//用户延期未付欠款金额
			$debt = $v[ 'debt' ]; //欠款金额
			//用户账期信用额度
			$credit = (int)$v[ 'creditInfo' ][ 'credit' ];
			//用户已用额度
			$used_quota = (int)$v[ 'creditInfo' ][ 'used_quota' ];
			//用户账期方式
			switch ( (int)$v[ 'creditInfo' ][ 'payment' ] ) {
				case 1: //月结 ( 当月在账款截止日之前的订单都在下单时间的后一月的 付款日+1天 付款)
					//月结天数
					$days = (int)$v[ 'creditInfo' ][ 'days' ];
					//账款截止日
					$dzclosedate = (int)$v[ 'creditInfo' ][ 'dzclosedate' ];
					//对账日
					$dzdate = (int)$v[ 'creditInfo' ][ 'dzdate' ];
					//付款日
					$fkdate = (int)$v[ 'creditInfo' ][ 'fkdate' ];
					break;
				case 2://数期(每个订单延期数期天数付款)
					//数期天数
					$days = (int)$v[ 'creditInfo' ][ 'days' ];
					break;
				case 3://周结(下单时间的后一周的星期一付款)
					//周结天数
					$days = (int)$v[ 'creditInfo' ][ 'days' ];
					break;
			}
			
			//账期信息组装 录入 erp_accounts 表
			$account = [
				'fcustno'      => $v[ 'fcustno' ],
				'quota'        => $credit,
				'used_quota'   => $used_quota,
				'used_debt'    => $debt,
				'status'       => 1,
				'account_type' => (int)$v[ 'creditInfo' ][ 'payment' ],
				'account_days' => $days,
				'since_name'   => $since_name,
			];
			
			if ( $account[ 'account_type' ] === 1 ) {
				$account[ 'month_dzclosedate' ] = $dzclosedate;
				$account[ 'month_dzdate' ] = $dzdate;
				$account[ 'month_fkdate' ] = $fkdate;
			}
			//执行写入
			//基础数据
			if( !$exists ){
				$insertRes = M( 'user' )->add( $baseInfo );
				if ( $insertRes === false ) {
					$error[] = [
						'no'    => $v[ 'fcustno' ],
						'error' => '数据写入user表失败',
					];
					$model->rollback();
					continue;
				}
				if ( $insertRes === 0 ) {
					$existsRes = M( 'user' )->field( 'id' )->where( [ 'user_name' => $baseInfo[ 'user_name' ] ] )->find();
					$id = $existsRes[ 'id' ];
				} else {
					$id = $insertRes;
				}
				$ret['customerId_arr']=[$id];
				$repy=D('Admin/Customer')->updateCompanyNameFirstString($ret);
				if ($repy['error']==1){
					$model->rollback();
					continue;
				}
			}else{
				$id = $exists['id'];
				$saveRes = M('user')->where(['id'=>$id])->save([ 'user_type'=> (int)$v[ 'user_type' ]]);
				if ( $saveRes === false ) {
					$error[] = [
						'no'    => $v[ 'fcustno' ],
						'error' => '数据更新user表失败',
					];
					$model->rollback();
					continue;
				}
			}

			
			//用户详细信息
			if ( !empty( $detail ) ) {
				if ( (int)floatval( $v[ 'user_type' ] ) === 2 ) {
					$mod = M( 'user_company' );
				} else {
					$mod = M( 'user_normal' );
				}
				$detailCount  = $mod->where( ['user_id'=>$id] )->count();
				if( $detailCount > 0 ){
					$detailRes = $mod->where( ['user_id'=>$id] )->data($detail)->save();
				}else{
					$detail[ 'user_id' ] = $id;
					$detailRes = $mod->data($detail)->add();
				}
				
				if ( $detailRes === false ) {
					$error[] = [
						'no'    => $v[ 'fcustno' ],
						'error' => '数据写入用户详情表失败',
					];
					$model->rollback();
					continue;
				}
			}
			
			//ERP用户账期信息
			if ( isset( $account ) && $account ) {
				$account[ 'user_id' ] = $id;
				$erp_acc =  M( '', 'erp_accounts' );
				$ex = $erp_acc->where([ 'fcustno'=>$v['fcustno'] ])->find();
				if( $ex ){
					$accountRes = $erp_acc->where([ 'fcustno'=>$v['fcustno'] ])->data($account)->save( );
				}else{
					$accountRes = $erp_acc->add( $account );
				}
		
				if ( $accountRes === false ) {
					$error[] = [
						'no'    => $v[ 'fcustno' ],
						'error' => '账期数据写入account表失败',
					];
					$model->rollback();
					continue;
				}
				
				//dx_account 表数据写入
				$add = [
					'user_id' => $id,
					'quota'   => $credit,
					'type'    => (int)$v[ 'user_type' ],
					'status'  => 1,
				];
				//如果欠款大于0
				if ( (int)$debt > 0 ) {
					$add[ 'status' ] = 2;//停用
				}
				//用户账期停用
				$isEx = M( 'user_account' )->where( [ 'user_id' => $id ] )->find();
				if ( $isEx ){
					unset( $add[ 'user_id' ] );
					$res = M( 'user_account' )->where( [ 'user_id' => $id ] )->save( $add );
				}else{
					$res = M( 'user_account' )->add( $add );
				}
				if ( $res === false ) {
					Log::write( 'ERP客户' . $custNo . $custName . '在dx_user_account表中的用户id为' . $id . '的账期状态改为停用失败' );
					$error[] = [
						'no'    => $v[ 'fcustno' ],
						'error' => 'account表账期状态改为停用失败',
					];
					$model->rollback();
					continue;
				}
				
			}
			//收货地址写入
			$shAddress = [
				'user_id'   => $id,
				'consignee' => $v[ 'fshcontactor' ]?$v[ 'fshcontactor' ]:'',
				//收货联系人
				'area_code' => 0,
				'address'   => $v[ 'fshaddress' ]?$v[ 'fshaddress' ]:'',
				//收货地址
				'mobile'    => $v[ 'fshtel' ]?$v[ 'fshtel' ]:'0',
				//收货联系电话
				'zipcode'   => 0,
				'status'    => 1,
			];
			//检查是否存在
			$ex = M( 'user_order_address' )->where( $shAddress )->find();
			if( !$ex ){
				$addressRes = M( 'user_order_address' )->add( $shAddress );
				if ( $addressRes === false ) {
					$error[] = [
						'no'    => $v[ 'fcustno' ],
						'error' => '收货信息数据写入dx_user_order_address表失败',
					];
					$model->rollback();
					continue;
				}
			}
			
			$model->commit();
			file_put_contents( $logPath . $flag . '_success.txt', var_export(( [
				'user_id'   => $id,
				'user_name' => $baseInfo[ 'user_name' ],
				'user_pass' => '123456',
				'cust_no'   => $custNo,
				'cust_name' => $custName,
			] ),true).PHP_EOL,8 );
			$success[] = [
				'user_id'   => $id,
				'user_name' => $baseInfo[ 'user_name' ],
				'user_pass' => '123456',
				'cust_no'   => $custNo,
				'cust_name' => $custName,
			];
		}
		$successCount = count( $success );
		$errorCount = count( $error );
		
		//导入完成
		file_put_contents( $logPath . $flag . '.txt', json_encode( [
			'status'    => 2,
			'flag'      => $flag,
			'suc_count' => $successCount,
			'err_count' => $errorCount,
		] ) );
		//导出结果写入文件
		$return = [
			'success' => [
				'list'  => $success,
				'count' => $successCount,
			],
			'error'   => [
				'list'  => $error,
				'count' => $errorCount,
			],
			'sum'     => $sumCount,
		];
		file_put_contents( $downPath . $flag . '_down.json', json_encode( $return ) );
		//unlink( $logPath . $flag . '.txt' );
		//unlink( SITE_PATH . 'Uploads' . DS . 'Ees' . DS . 'data' . DS . $name );
		ignore_user_abort(false);
		
		return true;
	}
	
	function storeFail( $flag = '' )
	{
		$flag = 'xe155a16';
		$downFile = SITE_PATH . 'Uploads' . DS . 'Ees' . DS . 'back' . DS . date( 'Y-m-d' ) . DS . $flag . '_down.json';
		$dataFile = SITE_PATH . 'Uploads' . DS . 'Ees' . DS . 'data' . DS. $flag.'_'.date('Y-m-d').'_custData.json';
		$storeData = json_decode(file_get_contents( $downFile ),true);
		$error = $storeData['error'];
		$df = file_get_contents( $dataFile );
		$arr = explode( '|@|', $df );
		foreach( $arr as $k=>$v ){
			$data[$k] = json_decode($v,true);
		}
		foreach( $error['list'] as $k=>$value ){
			$custNo = $value['no'];
			dd( $custNo );
		}
		de( $error );
		de( $storeData );
	}
	
	public function OneFail($v=[])
	{
		$v = json_decode(I('v'),true) ;
		if ( empty( $v ) || !isset( $v[ 'fcustname' ] ) ) {
			$error = [
				'no'    => $v[ 'fcustno' ],
				'error' => '数据为空',
			];
			ajaxReturn(['error'=>1,'data'=>$error],'json');
		}
		$detail = $baseInfo = $account = [];
		$custNo = $v[ 'fcustno' ];
		$custName = $v[ 'fcustname' ];
		$custJc = $v[ 'fcustjc' ];
		$fsale = $v[ 'fsale' ];
		$fsaleName = $v[ 'fsalename' ];
		//自定账期名称
		$since_name = $v[ 'since_name' ];
		//检查客户简要信息是否存在商城表中
		$model = M( 'user' );
		$userWhere['fcustno'] = $custNo;
		$userWhere['user_type'] = ['in',[1,2]];
		$exists = $model->field('id,user_name')->where( $userWhere )->find();
		$model->startTrans();
		$custModel = M( '', 'erp_customer' );
		$count = $custModel->where( [ 'fcustno' => $custNo ] )->count('id');
		if ( $count <= 0 ) {
			//不存在
			$add = [
				'fcustno'   => $custNo,
				'fcustname' => $custName,
				'fcustjc'   => $custJc,
				'fsale'     => $fsale,
				'fsalename' => $fsaleName,
			];
			$addRes = $custModel->add( $add );
			
			if ( $addRes === false ) {
				Log::error( '添加客户' . $custNo . '的数据到表erp_customer数据添加失败,数据:' . json_encode( $add ) );
				$error = [
					'no'    => $v[ 'fcustno' ],
					'error' => '添加客户数据到表erp_customer失败',
				];
				ajaxReturn(['error'=>1,'data'=>$error],'json');
			}
		}
		//查询业务员是否存在
		$saleModel = M( '', 'sys_user' );
		$isSale = $saleModel->field( 'uid' )->where( [ 'FEmplNo' => $fsale ] )->find();
		if ( $isSale ) {
			$sys_uid = $isSale[ 'uid' ];
		}
		//dx_user表 基础信息
		$baseInfo = [
			'user_name'        => $this->createUserName(),
			//客户登录名
			'user_pass'        => hash_string( '123456' ),
			//客户登录密码
			'user_type'        => (int)$v[ 'user_type' ],
			//客户用户类型
			'nick_name'        => $v[ 'fcustjc' ],
			//客户简称
			'fcustno'          => $v[ 'fcustno' ],
			'fcustjc'          => $v[ 'fcustjc' ],
			'sys_uid'          => isset( $sys_uid ) ? $sys_uid : 0,
			'isedit_user_name' => 1,
		];
		//是企业用户 dx_company 详细企业信息
		if ( (int)floatval( $v[ 'user_type' ] ) === 2 ) {
			$detail = [
				'company_name'            => $v[ 'fcustname' ],
				'company_address'         => $v[ 'faddress' ]?$v[ 'faddress' ]:'',
				'company_phone_num'       => $v[ 'ftel' ] ? $v[ 'ftel' ] : '',
				'company_user_name'       => $v[ 'fcontactor1' ] ? $v[ 'fcontactor1' ] : $v[ 'fcontactor2' ]?$v[ 'fcontactor2' ]:'',
				'company_user_position'   => $v[ 'fduty1' ] ? $v[ 'fduty1' ] : $v[ 'fduty2' ]?$v[ 'fduty2' ]:'',
				'company_user_phone'      => $v[ 'fmobile1' ] ? $v[ 'fmobile1' ] : ( $v[ 'ftel1' ] ? $v[ 'ftel1' ] : ( $v[ 'fmobile2' ] ? $v[ 'fmobile2' ] : $v[ 'ftel2' ] ) )?( $v[ 'ftel1' ] ? $v[ 'ftel1' ] : ( $v[ 'fmobile2' ] ? $v[ 'fmobile2' ] : $v[ 'ftel2' ] ) ):'',
				'check_status'            => 1,
				'company_user_qq'         => $v[ 'fqq1' ]?$v[ 'fqq1' ]:'',
				'company_user_wechat'     => $v[ 'fmsn1' ]?$v[ 'fmsn1' ]:'',
				//企业人数
				'company_people_num'      => $v[ 'femplnum' ]?$v[ 'femplnum' ]:0,
				//企业所在城市
				'company_city'            => $v[ 'fcity' ]?$v[ 'fcity' ]:'',
				//年营业额
				'company_annual_turnover' => $v[ 'fyysummoney' ]?$v[ 'fyysummoney' ]:'',
				//企业销售渠道
				'company_sales_channel'   => $v[ 'fsalearea' ]?$v['fsalearea']:'',
				//企业营业性质
				'company_business_nature' => $v[ 'fyyxz' ]?$v[ 'fyyxz' ]:'',
				//企业经营品牌
				'company_business_brand'  => $v[ 'fjyxm' ]?$v[ 'fjyxm' ]:'',
			];
		} elseif ( (int)floatval( $v[ 'user_type' ] ) === 1 ) {
			$detail = [
				'address' => $v[ 'faddress' ],
			];
		}
		
		//用户账期信息
		//用户延期未付欠款金额
		$debt = $v[ 'debt' ]; //欠款金额
		//用户账期信用额度
		$credit = (int)$v[ 'creditInfo' ][ 'credit' ];
		//用户已用额度
		$used_quota = (int)$v[ 'creditInfo' ][ 'used_quota' ];
		//用户账期方式
		switch ( (int)$v[ 'creditInfo' ][ 'payment' ] ) {
			case 1: //月结 ( 当月在账款截止日之前的订单都在下单时间的后一月的 付款日+1天 付款)
				//月结天数
				$days = (int)$v[ 'creditInfo' ][ 'days' ];
				//账款截止日
				$dzclosedate = (int)$v[ 'creditInfo' ][ 'dzclosedate' ];
				//对账日
				$dzdate = (int)$v[ 'creditInfo' ][ 'dzdate' ];
				//付款日
				$fkdate = (int)$v[ 'creditInfo' ][ 'fkdate' ];
				break;
			case 2://数期(每个订单延期数期天数付款)
				//数期天数
				$days = (int)$v[ 'creditInfo' ][ 'days' ];
				break;
			case 3://周结(下单时间的后一周的星期一付款)
				//周结天数
				$days = (int)$v[ 'creditInfo' ][ 'days' ];
				break;
		}
		
		//账期信息组装 录入 erp_accounts 表
		$account = [
			'fcustno'      => $v[ 'fcustno' ],
			'quota'        => $credit,
			'used_quota'   => $used_quota,
			'used_debt'    => $debt,
			'status'       => 1,
			'account_type' => (int)$v[ 'creditInfo' ][ 'payment' ],
			'account_days' => $days,
			'since_name'   => $since_name,
		];
		
		if ( $account[ 'account_type' ] === 1 ) {
			$account[ 'month_dzclosedate' ] = $dzclosedate;
			$account[ 'month_dzdate' ] = $dzdate;
			$account[ 'month_fkdate' ] = $fkdate;
		}
		//执行写入
		//基础数据
		if( !$exists ){
			$insertRes = M( 'user' )->add( $baseInfo );
			if ( $insertRes === false ) {
				$error = [
					'no'    => $v[ 'fcustno' ],
					'error' => '数据写入user表失败',
				];
				$model->rollback();
				ajaxReturn(['error'=>1,'data'=>$error],'json');
			}else{
				$ret['customerId_arr']=[$insertRes];
				$repy=D('Admin/Customer')->updateCompanyNameFirstString($ret);
				if ($repy['error']==1){
					$model->rollback();
					ajaxReturn(['error'=>1,'data'=>$error],'json');
				}
			}
			//userId
			if ( $insertRes === 0 ) {
				$existsRes = M( 'user' )->field( 'id' )->where( [ 'user_name' => $baseInfo[ 'user_name' ] ] )->find();
				$id = $existsRes[ 'id' ];
			} else {
				$id = $insertRes;
			}
		}else{
			$id = $exists['id'];
			$ret['customerId_arr']=[$id];
			$repy=D('Admin/Customer')->updateCompanyNameFirstString($ret);
			if ($repy['error']==1){
				$model->rollback();
				ajaxReturn(['error'=>1,'data'=>$error],'json');
			}
		}
		
		//用户详细信息
		if ( !empty( $detail ) ) {
			if ( (int)floatval( $v[ 'user_type' ] ) === 2 ) {
				$mod = M( 'user_company' );
			} else {
				$mod = M( 'user_normal' );
			}
			$detail[ 'user_id' ] = $id;
			$detailCount  = $mod->where( ['user_id'=>$id] )->count();
			if( $detailCount > 0 ){
				$detailRes = $mod->where( ['user_id'=>$id] )->data($detail)->save();
			}else{
				$detail[ 'user_id' ] = $id;
				$detailRes = $mod->data($detail)->add();
			}
			if ( $detailRes === false ) {
				$error = [
					'no'    => $v[ 'fcustno' ],
					'error' => '数据写入用户详情表失败',
				];
				$model->rollback();
				ajaxReturn(['error'=>1,'data'=>$error],'json');
			}
		}
		//ERP用户账期信息
		if ( isset( $account ) && $account ) {
			$account[ 'user_id' ] = $id;
			$erp_acc =  M( '', 'erp_accounts' );
			$ex = $erp_acc->where([ 'fcustno'=>$v['fcustno'] ])->find();
			if( $ex ){
				$accountRes = $erp_acc->where([ 'fcustno'=>$v['fcustno'] ])->data($account)->save();
			}else{
				$accountRes = $erp_acc->add( $account );
			}
			if ( $accountRes === false ) {
				$error = [
					'no'    => $v[ 'fcustno' ],
					'error' => '账期数据写入account表失败',
				];
				$model->rollback();
				ajaxReturn(['error'=>1,'data'=>$error],'json');
			}
			//dx_account 表数据写入
			$add = [
				'user_id' => $id,
				'quota'   => $credit,
				'type'    => (int)$v[ 'user_type' ],
				'status'  => 1,
			];
			//如果欠款大于0
			if ( (int)$debt > 0 ) {
				$add[ 'status' ] = 2;//停用
			}
			//用户账期停用
			$isEx = M( 'user_account' )->where( [ 'user_id' => $id ] )->find();
			if ( $isEx ){
				unset( $add[ 'user_id' ] );
				$res = M( 'user_account' )->where( [ 'user_id' => $id ] )->save( $add );
			}else{
				$res = M( 'user_account' )->add( $add );
			}
			if ( $res === false ) {
				Log::write( 'ERP客户' . $custNo . $custName . '在dx_user_account表中的用户id为' . $id . '的账期状态改为停用失败' );
				$error = [
					'no'    => $v[ 'fcustno' ],
					'error' => 'account表账期状态改为停用失败',
					'data'=>json_encode($add)
				];
				$model->rollback();
				ajaxReturn(['error'=>1,'data'=>$error],'json');
			}
			
		}
		//收货地址写入
		$shAddress = [
			'user_id'   => $id,
			'consignee' => $v[ 'fshcontactor' ]?$v[ 'fshcontactor' ]:'',
			//收货联系人
			'area_code' => 0,
			'address'   => $v[ 'fshaddress' ]?$v[ 'fshaddress' ]:'',
			//收货地址
			'mobile'    => $v[ 'fshtel' ]?$v[ 'fshtel' ]:'0',
			//收货联系电话
			'zipcode'   => 0,
			'status'    => 1,
		];
		//检查是否存在
		$ex = M( 'user_order_address' )->where( $shAddress )->find();
		if( !$ex ){
			$addressRes = M( 'user_order_address' )->add( $shAddress );
			if ( $addressRes === false ) {
				$error = [
					'no'    => $v[ 'fcustno' ],
					'error' => '收货信息数据写入dx_user_order_address表失败',
				];
				$model->rollback();
				ajaxReturn(['error'=>1,'data'=>$error],'json');
			}
		}

		
		$model->commit();
		$success = [
			'user_id'   => $id,
			'user_name' => $baseInfo[ 'user_name' ],
			'user_pass' => '123456',
			'cust_no'   => $custNo,
			'cust_name' => $custName,
		];
		
		ajaxReturn(['error'=>0,'data'=>$success],'json');
	}
}