<?php

// +----------------------------------------------------------------------
// | FileName:   OrderController.class.bak.20170906.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/22 15:14
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Common\Controller\BaseController;
use Common\Controller\Category;
use Common\Controller\TreeController;
use EES\System\Redis;
use http\Env\Response;
use Common\Libs\Weixin\ComPay;//红包与企业支付
use Common\Libs\Weixin\WechatAuth;//JSSDK 需要用到accessToken
use Common\Libs\Weixin\JSSDK;//JSSDK
use alipay\Query;

class CustomerController extends AdminController
{
	
	use Category;
	
	public static $order;
	
	protected function _initialize()
	{
		parent::_initialize(); // TODO: Change the autogenerated stub
		self::$order = D( 'order' );
	}

    public function payOrder(){
        $request=I('get.');
        $page=$request['page']?$request['page']:1;

        $pageSize=$request['pageSize']?$request['pageSize']:10;
        $where['dwn.openid']=['neq',''];
        if ($request['out_trade_no']){
            $where['dwn.out_trade_no']=$request['out_trade_no'];
        }
        if ($request['order_sn']){
            $where['do.order_sn']=$request['order_sn'];
        }
        if ($request['user_id']){
            $where['do.user_id']=$request['user_id'];
        }
        if($request['create_at_start'] && !$request['create_at_end']){
            $where['dwn.create_at']=['egt',$request['create_at_start']];
        }
        if($request['create_at_end']&& !$request['create_at_start']){
            $where['dwn.create_at']=['elt',$request['create_at_end']];
        }
        if($request['create_at_end']&& $request['create_at_start']){
            $where['dwn.create_at']=['between',[$request['create_at_start'],$request['create_at_end']]];
        }

        $where['dwn.notify_code']=0;
        $notify=M('wechat_notify')->alias('dwn')->join("dx_order as do on do.order_sn=left(substring(dwn.out_trade_no,11), length(substring(dwn.out_trade_no,11))-1)")->join("dx_user as du on do.user_id=du.id")->field("dwn.out_trade_no,dwn.notify_type,dwn.notify_msg,dwn.notify_result_json,dwn.create_at,do.order_sn,do.total as order_total,du.user_name,du.fcustjc,du.user_type,du.id")->where($where)->order("dwn.create_at desc")->limit(($request['page']-1)*$request['pageSize'],$pageSize)->select();
        foreach($notify as  $k=>$v){
            $str=strpos($v['notify_msg'],'alipay');
            if($str===false){
                $str=strpos($v['notify_msg'],'wechat');
                if($str===false){
                    unset($notify[$k]);//先不添加银联的  后续开发
                }else{
                    $notify[$k]['origin']='2';//1代表微信
                    $res=json_decode($v['notify_result_json'],true);
                    $notify[$k]['time']=$v['create_at'];
                    $notify[$k]['trade_no']=$res['transaction_id']?$res['transaction_id']:'';//平台交易号
                    $notify[$k]['buyer_logon_id']='';//支付宝付款账号
                    $notify[$k]['pay_status']=2;//未付款
                    //					if($res['trade_state']=='NOTPAY'){
                    //						$notify[$k]['pay_status']=0;//未付款
                    //					}elseif ($res['trade_state']=='CLOSED'){
                    //						$notify[$k]['pay_status']=1;//支付超时
                    //					}elseif ($res['trade_state']=='SUCCESS'){
                    //						$notify[$k]['pay_status']=2;//支付成功(可退款)
                    //					}elseif ($res['trade_state']=='REFUND'){
                    //						$notify[$k]['pay_status']=4;//转入退款(微信退款)
                    //					}elseif ($res['trade_state']=='REVOKED'){
                    //						$notify[$k]['pay_status']=5;//已撤销(微信)
                    //					}elseif ($res['trade_state']=='USERPAYING'){
                    //						$notify[$k]['pay_status']=6;//支付中(微信)
                    //					}elseif ($res['trade_state']=='PAYERROR'){
                    //						$notify[$k]['pay_status']=7;//支付失败(微信)
                    //					}
                    $notify[$k]['total_amount']=$res['total_fee']?$res['total_fee']/100:0;
                }
            }else{
                $notify[$k]['origin']='1';//1代表支付宝
                $res=json_decode($v['notify_result_json'],true);
                $notify[$k]['trade_no']=$res['trade_no']?$res['trade_no']:'';//平台交易号
                $notify[$k]['time']=$v['create_at'];
                $notify[$k]['buyer_logon_id']=$res['buyer_email']?$res['buyer_email']:'';//支付宝付款账号
                $notify[$k]['pay_status']=2;//未付款
                //				if($res['trade_status']=='WAIT_BUYER_PAY'){
                //					$notify[$k]['pay_status']=0;//未付款
                //				}elseif ($res['trade_status']=='TRADE_CLOSED'){
                //					$notify[$k]['pay_status']=1;//支付超时
                //				}elseif ($res['trade_status']=='TRADE_SUCCESS'){
                //					$notify[$k]['pay_status']=2;//支付成功(可退款)
                //				}elseif ($res['trade_status']=='TRADE_FINISHED'){
                //					$notify[$k]['pay_status']=3;//支付成功(不可退款)
                //				}
                $notify[$k]['total_amount']=$res['total_fee']?$res['total_fee']:'';
                //$notify[$k]=array_merge($notify[$k],$res);
                //array_merge($notify[$k],$res);
            }

            if($v['user_type']==20){
                $pid=M('user_son')->field('p_id')->where(['user_id'=> $v['id'] ])->find();
                $userInfo1 = M('user')->field('user_name,user_type,nick_name,fcustno,fcustjc,sys_uid')->where(['id'=>(int)$pid['p_id']])->find();
                $notify[$k]['fcustno']=$userInfo1['fcustno'];
            }

        }
        $count=M('wechat_notify')->alias('dwn')->join("dx_order as do on do.order_sn=left(substring(dwn.out_trade_no,11), length(substring(dwn.out_trade_no,11))-1)")->join("dx_user as du on do.user_id=du.id")->field("count(*) as s")->where($where)->find();
        $request['count']=$count['s'];
        $request['page']=$page;
        $request['pageSize']=$pageSize;
        $this->assign('notify',$notify);
        $this->assign('request',$request);
        $this->display();
    }
	
	public function payDetail(){
		$request=I('post.');
		if($request['origin']==1){
			//支付宝
			$html=$this->payalipay($request['out_trade_no']);
			$this->ajaxReturn( ['html'=>$html] );
		}elseif($request['origin']==2){
			//微信
			$html=$this->payWechat($request['out_trade_no']);
			
			$this->ajaxReturn( ['html'=>$html] );
		}
	}
	public function payWechat($out_trade_no){
		import('Common.Libs.Weixin.JSAPI');
		$input = new \WxPayOrderQuery();
		$input->SetOut_trade_no($out_trade_no);
		$data = \WxPayApi::orderQuery($input);
		if($data['trade_state']=='NOTPAY'){
			$data['order_status']=0;//未付款
		}elseif ($data['trade_state']=='CLOSED'){
			$data['order_status']=1;//支付超时
		}elseif ($data['trade_state']=='SUCCESS'){
			$data['order_status']=2;//支付成功(可退款)
		}elseif ($data['trade_state']=='REFUND'){
			$data['order_status']=4;//转入退款(微信退款)
		}elseif ($data['trade_state']=='REVOKED'){
			$data['order_status']=5;//已撤销(微信)
		}elseif ($data['trade_state']=='USERPAYING'){
			$data['order_status']=6;//支付中(微信)
		}elseif ($data['trade_state']=='PAYERROR'){
			$data['order_status']=7;//支付失败(微信)
		}
		$this->assign('detail',$data);
		$html =$this->fetch('Customer/detail');
		return $html;
	}
	
	
	public function payalipay($out_trade_no){
		import('Common.Libs.alipay.Query');
		$data=Query::exec($out_trade_no);
		
		if($data['trade_status']=='WAIT_BUYER_PAY'){
			$data['order_status']=0;//未付款
		}elseif ($data['trade_status']=='TRADE_CLOSED'){
			$data['order_status']=1;//支付超时
		}elseif ($data['trade_status']=='TRADE_SUCCESS'){
			$data['order_status']=2;//支付成功(可退款)
		}elseif ($data['trade_status']=='TRADE_FINISHED'){
			$data['order_status']=3;//支付成功(不可退款)
		}
		
		$this->assign('detail',$data);
		$html =$this->fetch('Customer/detail');
		
		return $html;
	}
	
	public  function codeOne(){
		$id=I('id');
		$save['status']=I('status');
		$save['goods']=I('goods')?I('goods'):'';
		$save['remark']=I('remark')?I('remark'):'';
		$s=M('code_goods')->where(['id'=>$id])->save($save);
		if($s===false){
			die(json_encode(['error'=>1,'msg'=>'保存失败']));
		}else{
			die(json_encode(['error'=>0,'msg'=>'保存成功']));
		}
	}
	public  function codeTwo(){
		$id=I('id');
		$save['status']=I('status');
		$s=M('code_goods')->where(['id'=>$id])->save($save);
		if($s===false){
			die(json_encode(['error'=>1,'msg'=>'保存失败']));
		}else{
			die(json_encode(['error'=>0,'msg'=>'保存成功']));
		}
	}
	/**
	 * @desc 客户列表
	 */
	public function customerList()
	{
		$request = I( 'get.' );
		$page = $request[ 'page' ] ? $request[ 'page' ] : 1;
		$pageSize = $request[ 'pageSize' ] ? $request[ 'pageSize' ] : C( 'PAGE_PAGESIZE' );
		$where = '';
		if ( isset( $request[ 'fcustno' ] )&&$request[ 'fcustno' ] ) $where[ 'fcustno' ] = $request[ 'fcustno' ];//erp客户编号搜索
		if ( isset( $request[ 'user_mobile' ] )&&$request[ 'user_mobile' ] ) $where[ 'user_mobile' ] = $request['user_mobile'];
		if ( isset( $request[ 'user_name' ] )&&$request[ 'user_name' ] ) $where[ 'user_name' ] = ['like',"%$request[user_name]%"];
		
		if ( !$request[ 'user_id' ] && isset( $request[ 'user_id_name' ] )&&$request[ 'user_id_name' ] ){
			$where[ 'fcustjc' ] = ['like',"%$request[user_id_name]%"];
		}
		
		//        if(isset($request['name'])&&$request['name']){
		//            $name_where=D('user')->customerNameToWhere($request['name']);
		//            if($name_where['error']==0){
		//                $where['id']=['in',$name_where['data']];
		//            }
		//        }
		$display='';
		if(isset($request['customer_isLock'])&&$request['customer_isLock']=='customer_isLock'){//锁单客户
			$display='customerListLock';
			$where[]="id in (select user_id from erp_accounts where used_debt > 0)";
		}
		
		if(isset($request['name'])&&$request['name']){
			$request['name']=preg_replace('/([^\(]+).*/','${1}',$request['name']);
			$where=[
				'fcustno'=>['like','%%'.$request[ 'name' ].'%%'],
				'user_name'=>['like','%%'.$request[ 'name' ].'%%'],
				'nick_name'=>['like','%%'.$request[ 'name' ].'%%'],
				'fcustjc'=>['like','%%'.$request[ 'name' ].'%%'],
				'_logic'=>'or'
			];
			$where_userId='';
			$userIds=M('user_company')->field('user_id')->where(['company_name'=>['like',"%%$request[name]%%"]])->select();
			if($userIds){
				foreach($userIds as $k=>$v){
					$where_userId[]=$v['user_id'];
				}
				$where['id']=['in',$where_userId];
			}
		}
		if(isset($request['companyName'])&&$request['companyName']){//企业名搜素
			$userId_arr=[];
			$companys=M('user_company')->field('user_id')->where(['company_name'=>['like',"%$request[companyName]%"]])->select();
			if($companys){
				foreach($companys as $k=>$v){
					$userId_arr=$v['user_id'];
				}
			}
			$where['id']=['in',$userId_arr];
		}
		
		if(isset($request['user_id'])&&$request['user_id']){//搜素
			$where['id']=(int)$request['user_id'];
		}
		
		if($request['saleId']){//业务员搜素
			$users=M('user')->field('id')->where(['sys_uid'=>$request['saleId']])->select();
			$userId_arr=[];
			if($users){
				foreach($users as $k=>$v){
					$userId_arr[]=$v['id'];
				}
			}
			$where['id']=['in',$userId_arr];
		}
		
		$customerList = D( 'customer' )->customerList( $where, $page, $pageSize,'id desc' );
		
		if($customerList['data']['list']){
			if(isset($request['customer_isLock'])&&$request['customer_isLock']=='customer_isLock'){//锁单客户
				$customerId=[];
				foreach($customerList['data']['list'] as $k=>$v){
					if(!in_array($v['id'],$customerId)) $customerId[]=$v['id'];
				}
				if($customerId){
					$accounts=M('accounts','erp_')->field('user_id,used_debt')->where(['user_id'=>['in',$customerId]])->select();
					$accounts_arr=[];
					foreach($accounts as $k=>$v){
						$accounts_arr[$v['user_id']]=$v['used_debt'];
					}
					foreach($customerList['data']['list'] as $k=>$v){
						$customerList['data']['list'][$k]['customer_account']=$accounts_arr[$v['id']]?$accounts_arr[$v['id']]:0;
					}
				}
			}
		}
		//start:会员信息补充
		$customerInfo = $customerList['data']['list'];
 		$vipInfo  =  D('solution_vip')->field('uid,nick_name,vip_level,vip_time,mobile,qq,weichat,pro_num,con_num')->select();
		$vip_z    =	 ['非会员','月会员','季度会员','半年会员','年度会员'];
		if(!empty( $vipInfo )){
			foreach($vipInfo as $k=>&$v){
				switch($v['vip_level']){
					case 1:$v['vip_level'] = $vip_z[1];break;
					case 2:$v['vip_level'] = $vip_z[2];break;
					case 3:$v['vip_level'] = $vip_z[3];break;
					case 4:$v['vip_level'] = $vip_z[4];break;
					default:$v['vip_level']= $vip_z[0];
				}
				$v['vip_name'] = $v['nick_name'];unset($v['nick_name']);
				foreach( $customerInfo as $k2 => &$v2 ){
					if( $v['uid']==$v2['id'] ){
						$v2['vip']=$v;
					}
				}
			}
		}
		$customerList['data']['list'] = $customerInfo;
		//end:
		$customerList[ 'data' ][ 'page' ] = $page;
		$customerList[ 'data' ][ 'pageSize' ] = $pageSize;
		//ajax获取列表
		if ( IS_AJAX ) {
			die( json_encode( [ 'error' => 0, 'msg' => '操作成功', 'data' => $customerList[ 'data' ] ] ) );
		}
//		print_e($customerList);die;
		$this->assign( 'customerList', $customerList[ 'data' ] );
		$this->assign( 'request', $request );
		$this->display($display);
	}
	
	/**
	 * @desc 客户列表编辑
	 */
	public function customerAction()
	{
		if ( IS_AJAX ) {
			$request = I( 'post.' );
			$result = D( 'customer' )->customerAction( $request );
			die( json_encode( $result ) );
		}
	}
	
	/**
	 * @desc 客户产品议价列表
	 */
	public function customerProductBargainList()
	{
		$request=I('get.');
		$where = [ 'is_del' => 0 ];
		if($request['uid']) $where['uid']=$request['uid'];
		if($request['p_id']) $where['p_id']=$request['p_id'];
		$page = $request[ 'page' ] ? $request[ 'page' ] : 1;
		$pageSize = $request[ 'pageSize' ] ? $request[ 'pageSize' ] : C( 'PAGE_PAGESIZE' );
		if(isset($request['limit']))$pageSize=$request['limit'];
		
		if(!$request['uid']&&$request['uid_name']){
			$where[]="uid in (select id from dx_user where fcustjc like '"."%$request[uid_name]%'"." or nick_name like '"."%$request[uid_name]%')";
			unset($request['uid']);
		}
		if($request['sys_uid']){//业务员
			$where[]="uid in (select id from dx_user where sys_uid = $request[sys_uid])";
		}
		
		$result = D( 'productbargain' )->productBargainList( $where,$page,$pageSize,'id desc' );
		if(IS_AJAX){
			$ajaxReturn=[
				'code'=>$result['error'],
				'msg'=>$result['msg'],
				'count'=>$result['data']['count'],
				'data'=>$result['data']['list']
			];
			$this->ajaxReturn($ajaxReturn);
		}
		if ( $result[ 'error' ] != 0 ) $list = '';
		$list = $result[ 'data' ];
		$this->assign( 'request', $request );
		$this->assign( 'list', $list );
		$this->display();
	}
	
	/**
	 * @desc 客户产品议价新增
	 */
	public function oneBargain()
	{
		if ( IS_AJAX ) {//新增
			$request = I( 'post.' );
			if(isset($request['check_id'])&&$request['check_id']){//审核
				$actionResutl= D( 'productbargain' )->oneProductBargainAction($request);
			}else{
				$actionResutl = D( 'productbargain' )->productBargainActions( $request['data'] );
			}
			die( json_encode( $actionResutl ) );
		}
		$request = I( 'get.' );
		$where = [ 'id' => $request[ 'id' ] ];

		$result = D( 'productbargain' )->productBargainList( $where ,'','','',['tableName'=>['dx_product_ftiemno']]);
		if ( $result[ 'error' ] == 0 ) {
			$this->assign( 'bargain', $result[ 'data' ][ 'list' ][ 0 ] );
		};
		$this->assign( 'request', $request );
		$this->display();
	}
	
	/**
	 * @desc 客户议价产品删除
	 */
	public function oneBargainDelete()
	{
		if ( IS_AJAX ) {
			$request = I( 'post.' );
			$result = D( 'productbargain' )->oneBargainDelete( $request );
			die( json_encode( $result ) );
		}
	}
	
	/**
	 * @desc 客户帐期列表
	 */
	public function customerAccountList()
	{
		$request = I( 'get.' );
		$page = $request[ 'page' ] ? $request[ 'page' ] : 1;
		$pageSize = $request[ 'pageSize' ] ? $request[ 'pageSize' ] : C( 'PAGE_PAGESIZE' );
		$where = '';
		if(isset($request['mobile'])&&$request['mobile']) $where['mobile']=$request['mobile'];
		
		$userId_arr=[];
		if(isset($request['saleId'])){//业务员搜索
			$userList=M('user')->field('id')->where(['sys_uid'=>$request['saleId']])->select();
			foreach($userList as $k=>$v){
				$userId_arr[]=$v['id'];
			}
		}
		
		if(isset($request['user_name'])&&$request['user_name']){
			if((int)$request['user_name']!==0&&(int)($request['user_name'])==$request['user_name']){
				$where[]=['user_id'=>$request['user_name']];
			}else{
				$userList2=M('user')->field('id')->where(['user_name'=>['like',"%$request[user_name_name]%"],'fcustjc'=>['like',"%$request[user_name_name]%"],'_logic'=>'or'])->select();
				foreach($userList2 as $k=>$v){
					$userId_arr[]=$v['id'];
				}
			}
		}
		if($userId_arr){
			$userId_arr=array_unique($userId_arr);
			if($userId_arr){
				$where['user_id']=['in',$userId_arr];
			}
		}
		$field='*,(select update_at from dx_user_account_temp where dx_user_account_temp.user_id=dx_user_account.user_id) as temp_update';
		$list = D( 'customeraccount' )->customerAccountList( $where, $page, $pageSize,'temp_update desc,id desc',$field );
		$list[ 'data' ][ 'page' ] = $page;
		$list[ 'data' ][ 'pageSize' ] = $pageSize;
		$this->assign( 'list', $list[ 'data' ] );
		$this->assign( 'request', $request );
		$this->display();
	}
	
	/**
	 * @desc 客户账期使用列表
	 */
	public function customerAccountUseList()
	{
		$request = I( 'get.' );
		$page = $request[ 'page' ] ? $request[ 'page' ] : 1;
		$pageSize = $request[ 'pageSize' ] ? $request[ 'pageSize' ] : C( 'PAGE_PAGESIZE' );
		$where = '';
		$list = D( 'customeraccount' )->customerAccountUseList( $where, $page, $pageSize );
		$list[ 'data' ][ 'page' ] = $page;
		$list[ 'data' ][ 'pageSize' ] = $pageSize;
		$this->assign( 'list', $list[ 'data' ] );
		$this->assign( 'request', $request );
		//        $this->display();
	}
	
	/**
	 * @desc 客户账期审核
	 */
	public function customerAccountCheck()
	{
		if ( IS_AJAX ) {
			$request = I( 'post.' );
			$result = D( 'customeraccount' )->customerAccountCheck( $request );
			die( json_encode( $result ) );
		}
	}
	
	/**
	 * @desc erp客户列表
	 */
	public function erpCustomerList()
	{
		if ( IS_AJAX ) {
			$request = I( 'get.' );
			$page = $request[ 'page' ] ? $request[ 'page' ] : 1;
			$pageSize = $request[ 'pageSize' ] ? $request[ 'pageSize' ] : C( 'PAGE_PAGESIZE' );
			$where = '';
			if(isset($request['fcustno'])&&$request['fcustno']) $where['fcustno']=$request['fcustno'];
			if(isset($request['fcustname'])&&$request['fcustname']) $where['fcustname']=['like',"%$request[fcustname]%"];
			$list = D( 'erpcustomer' )->erpCustomerList( $where, $page, $pageSize );
			$list[ 'data' ][ 'page' ] = $page;
			$list[ 'data' ][ 'pageSize' ] = $pageSize;
			die( json_encode( $list ) );
		}
	}
	
	/**
	 * @desc 报备信息
	 */
	public function reportMessage()
	{
		$request=I('get.');
		$page=$request['page']?$request['page']:1;
		$pageSize=$request['pageSize']?$request['pageSize']:C('PAGE_PAGESIZE');
		$where='';
		if(isset($request['p_sign'])&&$request['p_sign']) $where['p_sign']=['like',"%$request[p_sign]%"];
		if(isset($request['name'])&&$request['name']) $where['name']=['like',"%$request[name]%"];
		
		$field='*';
		$list=(new \Admin\Model\ProductbargainModel())->productReportList($where,$page,$pageSize,'id desc',$field);
		$list['data']['page'] = $page;
		$list['data']['pageSize'] = $pageSize;
		$this->assign('list',$list['data']);
		$this->assign('request',$request);
		$this->display();
	}
	
	
	/**
	 * @desc 商城客户相似ERP客户列表
	 */
	public function customerSameErpCustList(  )
	{
		$request = I( 'get.' );
		$result['page'] = $request[ 'page' ] ? $request[ 'page' ] : 1;
		$result['pageSize'] = $request[ 'pageSize' ] ? $request[ 'pageSize' ] : C( 'PAGE_PAGESIZE' );
		$model = M('user');
		$result['count'] = $model->field('id,user_name,user_mobile,user_type,user_email,nick_name,avator,sys_uid')->where(['fcustno'=>'', 'fcustjc'=>''])->count();
		$userList = $model->field('id,user_name,user_mobile,user_type,user_email,nick_name,avator,sys_uid')->where(['fcustno'=>'', 'fcustjc'=>''])->select();
		foreach( $userList as $value ){
			$value['sameList'] = '';
			$result['data'][] = $value;
		}
		//        de( $result );
		$this->assign( 'list', $result );
		$this->display();
	}
	
	
	/*
	 * 获取与ERP客户相似客户
	 */
	public function getSameErpCust( $userId )
	{
		//查询用户是否存在
		$user = M('user')->where(['id'=>$userId])->count();
		if( !$userId || !$user ){
			if(IS_AJAX){
				$this->ajaxReturn(['error' => 1, 'msg' => '参数为空或不正确' ],'EVAL');
			}else{
				return false;
			}
		}
		$redis = Redis::getInstance();
		$redis->sAdd( 'SelectSameCustList', $userId );
		$key = 'res_' . $userId;
		$i = 0;
		while ( $i < 4 ) {
			$result = $redis->hGet( 'SelectSameCustRes', $key );
			if ( $result ) {
				//取成功就删除
				$redis->hDel( 'SelectSameCustRes', $key );
				$res = json_decode( $result, true );
				if( $res['data'] === false ){
					$this->ajaxReturn(['error' => 1, 'msg' => '系统繁忙!获取失败!请重试' ],'EVAL');
				}
				if( IS_AJAX ){
					$this->ajaxReturn(['error' => 0, 'data' => $res['data']],'EVAL');
				}else{
					return $res;
				}
			}
			$i++;
			usleep(400000);
		}
		$this->ajaxReturn(['error' => 1, 'msg' => '系统繁忙!获取失败!请重试' ],'EVAL');
	}
	
	/**
	 * @desc 可开票的订单
	 */
	public function orderToInvoiceList(){
		$request = I( 'get.' );
		$page = $request[ 'page' ] ? $request[ 'page' ] : 1;
		$pageSize = $request[ 'pageSize' ] ? $request[ 'pageSize' ] : C( 'PAGE_PAGESIZE' );
		$where=[
			'pay_status'=>2,
			'is_invoice'=>1,
			'user_invoice_id'=>0,
			'order_sn not in (
                    select order_sn from (
	                    select order_sn,sum((p_num-knot_num-retreat_num)*pay_subtotal/p_num) as current_total from dx_order_goods group by order_sn
                    ) as ot where ot.current_total=0
                )',
			'user_invoice_id not in (select id from dx_user_invoice where implment_status=1)'
		];
		
		if(isset($request['user_id'])){
			$where=[
				[
					$where,
					"user_id in (select user_id from dx_user_son where p_id = $request[user_id])",
				],
				[
					$where,
					'user_id'=>$request['user_id'],
				],
				'_logic'=>'or'
			];
			
			$whereTitle=[
				'user_id'=>$request['user_id'],
				"user_id in (select user_id from dx_user_son where p_id = $request[user_id])",
				"user_id in (select p_id from dx_user_son where user_id = $request[user_id])",
				'_logic'=>'or'
			];
			$invoiceTitle=D('order')->userInvoiceTitle($whereTitle);
			$this->assign('invoiceTitle',$invoiceTitle['data']['list']);
		}
		
		if(isset($request['saleId'])){//业务员搜素
			$users=M('user')->field('id')->where(['sys_uid'=>$request['saleId']])->select();
			$userId_arr=[];
			if($users){
				foreach($users as $k=>$v){
					$userId_arr[]=$v['id'];
				}
			}
			$where['user_id']=['in',$userId_arr];
		}
		
		$list=D('order')->orderList($where,$page,$pageSize);
		$list['data']['page'] = $page;
		$list['data']['pageSize'] = $pageSize;
		$this->assign('list',$list['data']);
		$this->assign('request',$request);
		$this->display();
	}
	
	/**
	 * @desc 发票添加,编辑
	 */
	public function invoiceAction(){
		if(IS_AJAX){
			$request=I('post.');
			$result=D('order')->invoiceAction($request);
			die(json_encode($result));
		}
	}
	
	/**
	 * @desc 发票列表
	 */
	public function invoiceList(){
		$request = I( 'get.' );
		$page = $request[ 'page' ] ? $request[ 'page' ] : 1;
		$pageSize = $request[ 'pageSize' ] ? $request[ 'pageSize' ] : C( 'PAGE_PAGESIZE' );
		$where = '';
		if(isset($request['user_id'])&&$request['user_id']){
			$where['user_id']=$request['user_id'];
		}
		if(isset($request['sys_uid'])&&$request['sys_uid']){
			$sysUids=M('user')->field('id')->where(['sys_uid'=>$request['sys_uid']])->select();
			$sysUid_arr=[];
			if($sysUids){
				foreach($sysUids as $k=>$v){
					$sysUid_arr[]=$v['id'];
				}
				$where['user_id']=['in',$sysUid_arr];
			}
		}
		$list = D( 'order' )->invoiceList( $where, $page, $pageSize );
		$list['data']['page'] = $page;
		$list['data']['pageSize'] = $pageSize;
		$this->assign('list',$list['data']);
		$this->assign('request',$request);
		$this->display();
	}
	
	/**
	 * @desc 发票处理
	 */
	public function invoiceImplement(){
		if(IS_AJAX){
			$request=I('post.');
			$result=D('order')->invoiceImplement($request);
			die(json_encode($result));
		}
	}
	
	/**
	 * @desc 开票人的开票抬头
	 */
	public function userInvoiceTitle(){
		if(IS_AJAX){
			$request=I('post.');
			$result=D('order')->userInvoiceTitle($request);
			die(json_encode($result));
		}
	}
	
	/**
	 *
	 *  @desc 客户企业信息更改
	 */
	public function companyInfoAction(){
		if(IS_AJAX){
			$request=I('post.');
			$result=D('customer')->companyInfoAction($request);
			die(json_encode($result));
		}
	}
	
	/**
	 * @desc 议价的报备抛砖
	 */
	public function reportChangeSale(){
		if(IS_AJAX){
			$request=I('post.');
			$result=D('productbargain')->reportChangeSale($request);
			die(json_encode($result));
		}
	}
	
	/**
	 *
	 *  @desc 客户需求列表
	 */
	public function releaseList(){
		$request = I( 'get.' );
		$page = $request[ 'page' ] ? $request[ 'page' ] : 1;
		$pageSize = $request[ 'pageSize' ] ? $request[ 'pageSize' ] : C( 'PAGE_PAGESIZE' );
		$where = '';
		if(isset($request['user_id'])&&$request['user_id']){
			$where['user_id']=$request['user_id'];
		}
		$list=D('customer')->releaseList($where,$page,$pageSize,'id desc');
		$list['data']['page'] = $page;
		$list['data']['pageSize'] = $pageSize;
		$this->assign('request',$request);
		$this->assign('list',$list['data']);
		$this->display();
	}
	
	/**
	 *
	 *  @desc 客户需求处理
	 */
	public function releaseHandle(){
		if(IS_AJAX){
			$request = I( 'post.' );
			$result=D('customer')->releaseHandle($request);
			die(json_encode($result));
		}
	}
	
	/**
	 *
	 *  @desc 更新顾客拼音检索
	 * @param       $request['customerId_arr']=[
	1543,1542
	];
	 *
	 */
	public function updateCompanyNameFirstString($request){
		return D('Admin/Customer')->updateCompanyNameFirstString($request);
	}
	
	/**
	 *
	 *  @desc 更新管理员拼音检索
	 *
	 */
	public function updateAdminFirstString($request){
		$active='';
		
		$field='femplname,fullname,nickname,femplno,uid';
		$where=[];
		if(isset($request['userId_arr'])&&$request['userId_arr']&&is_array($request['userId_arr'])){
			$where['uid']=['in',$request['userId_arr']];
			$active='update';
		}
		
		if($request['active']=='delete'){
			$delete_result=M('user_pinyin','sys_')->where($where)->delete();
			
			if($delete_result===false) return ['error'=>1,'msg'=>'failed'];
			else return ['error'=>0,'msg'=>'success'];
		}
		
		$list=M('user','sys_')->field($field)->where($where)->select();
		if(!$list) return ['error'=>1,'msg'=>'客户信息错误pinyin'];
		
		$save_data=[];
		if($active=='all') M('user_pinyin','sys_')->query('truncate sys_user_pinyin');//清空数据
		
		$result=true;
		
		foreach($list as $k=>$v){
			$one_fullname=$v['femplname'];
			$one_femplno=$v['femplno'];
			$one_save=[];
			foreach($v as $k2=>$v2){
				if($k2=='uid'){
					$one_save[$k2]=$v2;
					continue;
				}
				if($v2){
					$v2=trim($v2);
					$one_one=pinyin($v2);
					if($one_one['error']===0){
						$one_save[$k2]=$one_one['data'];
					}
				}else{
					$one_save[$k2]=$v2;
				}
			}
			$one_save['return']=$one_fullname?$one_fullname:$one_femplno;
			$save_data[]=$one_save;
			
			if($active=='update'){
				if(M('user_pinyin','sys_')->where(['uid'=>$one_save['uid']])->find()){
					$one_result=M('user_pinyin','sys_')->where(['uid'=>$one_save['uid']])->save($one_save);
				}else{
					$one_result=M('user_pinyin','sys_')->add($one_save);
				}
				if($one_result===false) $result=false;
			}
		}
		
		if($active=='all') $result=M('user_pinyin','sys_')->addAll($save_data);
		
		if(!$result) return ['error'=>1,'msg'=>'failed'];
		else return ['error'=>0,'msg'=>'success'];
	}
	
	/**
	 *
	 *  @desc 客户拼音搜索
	 */
	public function companyPinyinSearch(){
		if(IS_AJAX){
			$request=I('get.');
			$string=$request['term'];
			if(preg_match("/[\x7f-\xff]/",$string)){//中文
				$where=[
					'return'=>['like',"%$string%"],
				];
			}else{
				$where=[
					'user_name'=>['like',"%$string%"],
					'company_name'=>['like',"%$string%"],
					'nick_name'=>['like',"%$string%"],
					'fcustno'=>['like',"%$string%"],
					'fcustjc'=>['like',"%$string%"],
					'_logic'=>'or'
				];
			}
			
			if($request['disable20']=='disable20'){
				$where=[
					$where,
					['user_id not in (select id from dx_user where user_type=20)']
				];
			}
			
			$count=M('user_pinyin')->where($where)->count();
			$count= (int)$count>200?200:$count;
			$list=M('user_pinyin')->field('return,user_id')->where($where)->limit(0,$count)->select();
			$resultList=$index=[];
			if(!$list){//拼音检索没有， 查找原始数据
				$where=[
					'user_name'=>['like',"%$string%"],
					'nick_name'=>['like',"%$string%"],
					'fcustjc'=>['like',"%$string%"],
					'_logic'=>'or'
				];
				if($request['disable20']=='disable20'){
					$where=[
						$where,
						['id not in (select id from dx_user where user_type=20)']
					];
				}
				$list=M('user')->field('id as user_id,nick_name,fcustjc')->where($where)->select();
			}
			if($list){
				foreach($list as $k=>$v){
					$index[]=$v['user_id'];
				}
				
				$customer_where['id']=['in',$index];
				$result=D('customer')->customerList($customer_where,0,$count);
				if($result['data']['list']){
					foreach($result['data']['list'] as $k=>$v){
						$resultList['data'][]=$v['nick_name']."($v[fcustjc])";
						$resultList['index'][]=$v['id'];
					}
				}else{
					$resultList['data'][]='没有数据1';
					$resultList['index'][]=0;
				}
			}else{
				$resultList['data'][]='没有数据';
				$resultList['index'][]=0;
			}
			
			die(json_encode($resultList));
		}
	}
	
	/**
	 *
	 *  @desc 管理员拼音搜索
	 */
	public function adminPinyinSearch(){
		if(IS_AJAX){
			$request=I('get.');
			$string=$request['term'];
			if(preg_match("/[\x7f-\xff]/",$string)){//中文
				$where=[
					'return'=>['like',"%$string%"],
				];
			}else{
				$where=[
					'femplname'=>['like',"%$string%"],
					'fullname'=>['like',"%$string%"],
					'nickname'=>['like',"%$string%"],
					'femplno'=>['like',"%$string%"],
					'_logic'=>'or'
				];
			}
			
			$list=M('user_pinyin','sys_')->field('return,uid')->where($where)->limit(0,200)->select();
			$resultList=[];
			if($list){
				$index=[];
				foreach($list as $k=>$v){
					$index[]=$v['uid'];
				}
				$adminList=D('user')->adminList(['uid'=>['in',$index]]);
				if($adminList['data']['list']){
					foreach($adminList['data']['list'] as $k=>$v){
						$resultList['data'][]=$v['femplname'];
						$resultList['index'][]=$v['uid'];
					}
				}else{
					$resultList['data'][]='没有数据';
					$resultList['index'][]=0;
				}
			}else{
				$resultList['data'][]='没有数据';
				$resultList['index'][]=0;
			}
			die(json_encode($resultList));
		}
	}
	
	/**
	 *
	 *  @desc 样品管理
	 *
	$request=[
	[
	'uid'=>6,
	'pid'=>1,
	]
	];
	 */
	public function customerProductSampleActions(){
		if(IS_AJAX){
			$request=I('post.');
			if($request['delete']==1){
				$sys_uid=session('adminId');
				$result=D('productbargain')->customerProductSampleAction($request['action_arr'][0],$sys_uid,1);
			}else{
				$result=D('productbargain')->customerProductSampleActions($request['action_arr']);
			}
			die(json_encode($result));
		}
		$this->display();
	}
	
	/**
	 *
	 *  @desc 样品列表
	 */
	public function customerProductSampleList(){
		$request = I( 'get.' );
		$page = $request[ 'page' ] ? $request[ 'page' ] : 1;
		$pageSize = $request[ 'pageSize' ] ? $request[ 'pageSize' ] : C( 'PAGE_PAGESIZE' );
		$where = '';
		
		if(isset($request['sale_id'])&&$request['sale_id']){//业务员搜索
			$where[]="uid in (select id from dx_user where sys_uid = $request[sale_id])";
		}
		
		if(isset($request['uid'])&&$request['uid']) $where=['uid'=>$request['uid']];
		if(isset($request['pid'])&&$request['pid']) $where=['pid'=>$request['pid']];
		$where['origin']=0;
		$example = D( 'productbargain' )->customerProductSampleList( $where, $page, $pageSize );
		
		$customerList[ 'data' ][ 'page' ] = $page;
		$customerList[ 'data' ][ 'pageSize' ] = $pageSize;
		$this->assign( 'list', $example[ 'data' ]?$example[ 'data' ]:[] );
		$this->assign( 'request', $request );
		$this->display();
	}
	
	
	/**
	 *
	 *  @desc 价格还原dx_product_price
	 */
	public function product_price_init(){
		set_time_limit(300);
		
		$sql='select id_f.*,f_f.*,pr.* from
(select id,fitemno from dx_product) as id_f,
(select ftem as fitemno,fstcb from erp_product) as f_f,
(select id as price_id,p_id,price_ratio,unit_price from dx_product_price) as pr
 where id_f.fitemno=f_f.fitemno and pr.p_id = id_f.id and 2=unit_price/price_ratio limit';
		
		//        $sql_count='select count(pr.price_id) from
		//(select id,fitemno from dx_product) as id_f,
		//(select ftem as fitemno,fstcb from erp_product) as f_f,
		//(select id as price_id,p_id,price_ratio,unit_price from dx_product_price) as pr
		// where id_f.fitemno=f_f.fitemno and pr.p_id = id_f.id';
		
		//        $count=D()->query($sql_count);
		$count=10338;
		$length=1000;
		$i_count=ceil($count/$length);
		for($i=0;$i<$i_count;$i++){
			$start=$i*$length;
			$list=D()->query($sql." $start,$length");
			if(!$list) break;
			$callback=function($val){
				$unit_price=$val['fstcb']*$val['price_ratio'];
				$result=D()->execute("update dx_product_price set unit_price=$unit_price where id=$val[price_id]");
				var_dump($result);echo '-------<br/>';
			};
			array_walk($list,$callback);
			//            die();
		}
	}
	
	/**
	 *
	 *  @desc 返点单审核
	 * @param
	$request=[
	'knot_no'=>14,
	'check_status'=>5,
	];
	 */
	public function checkKnotOrder(){
		if(IS_AJAX){
			$request=I('post.');
			$result=D('Admin/Order','Event')->orderCheck($request);
			die(json_encode($result[0]));
		}
	}
	
	/**
	 *
	 *  @desc 返点单财务审核
	 * @param
	$request=[
	'knot_no'=>14,
	];
	 */
	public function accountantCheckKnotOrder(){
		if(IS_AJAX){
			$request=I('post.');
			$result=D('Admin/Order','Event')->accountantCheckKnotOrder($request);
			die(json_encode($result[0]));
		}
	}
	
	/**
	 * @返差额列表
	 * @param string $orderSn_array 订单编号
	 * @return array  数据列表
	 *
	 */
	public function knotOrderList(){
		$request = I( 'get.' );
		$page = $request[ 'page' ] ? $request[ 'page' ] : 1;
		$pageSize = $request[ 'pageSize' ] ? $request[ 'pageSize' ] : C( 'PAGE_PAGESIZE' );
		$where = '';
		if(isset($request['customerId'])&&$request['customerId']){
			$where[]="order_sn in (select order_sn from dx_order where user_id = $request[customerId])";
		}else if(isset($request['customerId_name'])&&$request['customerId_name']){
			$where[]="order_sn in (select o.order_sn from dx_order as o left join dx_user as du on du.id=o.user_id where du.fcustjc like '%".$request[customerId_name]."%')";
		}
		if(isset($request['saleId'])&&$request['saleId']){
			$where[]="order_sn in (select o.order_sn from dx_order as o left join dx_user u on u.id=o.user_id where o.user_id = $request[saleId])";
		}
		
		$knotList=D('Admin/Order','Event')->knotOrderList($where,$page,$pageSize);
		
		$list['list']=$knotList['lists'][0];
		$list['count'] = $knotList['counts'][0];
		$list[ 'page' ] = $page;
		$list[ 'pageSize' ] = $pageSize;
		$this->assign( 'list', $list );
		$this->assign( 'request', $request );
		$this->display();
	}
	
	/**
	 *
	 *  @desc 路人的需求抛砖
	 * @param  [   id,  sale_id  ]
	 */
	public function releaseToSale(){
		$request=I('post.');
		$one=M('user','sys_')->field('uid')->where(['uid'=>$request['sale_id']])->find();
		if(!$one){
			die(json_encode(['error'=>1,'msg'=>'业务员信息错误']));
		}
		$result=M('release')->field('sale_id')->where(['id'=>$request['id'],'user_id'=>0,'handle_status'=>0])->save($request);
		if(!$result){
			die(json_encode(['error'=>1,'msg'=>'field']));
		}
		die(json_encode(['error'=>0,'msg'=>'success']));
	}
	
	/**
	 * @desc erp客户列表
	 */
	public function erpCustomerAll()
	{
		$request = I( 'get.' );
		
		$page = $request[ 'page' ] ? $request[ 'page' ] : 1;
		$pageSize = $request[ 'pageSize' ] ? $request[ 'pageSize' ] : C( 'PAGE_PAGESIZE' );
		$where = '';
		
		if(isset($request['fcustname'])&&$request['fcustname']){
			$where[]=[
				'fcustname'=> ['like',"%$request[fcustname]%"],
				'fcustjc'=> ['like',"%$request[fcustname]%"],
				'_logic'=>'or'
			];
		}
		if(isset($request['fsalename'])&&$request['fsalename']) $where['fsalename']=['like',"%$request[fsalename]%"];
		$list = D( 'customer' )->erpCustomerAll( $where ,$page,$pageSize);
		$list[ 'page' ] = $page;
		$list[ 'pageSize' ] = $pageSize;
		$this->assign( 'list', $list );
		$this->assign( 'request', $request );
		$this->display();
		
	}
	
	
}