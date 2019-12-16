<?php
// +----------------------------------------------------------------------
// | FileName:   CustinforEvent.class.php
// +----------------------------------------------------------------------
// | Dscription:   ERP客户基础资料变更监控事件处理
// +----------------------------------------------------------------------
// | Date:  2018-01-12 14:26
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace EES\Event;

use EES\Controller\OrderController;
use EES\Model\UserModel;
use Think\Log;

class CustinforEvent extends BaseEvent
{
	/*
	 * ERP客户数据Update事件处理
	 */
	public function updateStore( $data )
	{
		$change = $data[ 'change' ];
		$custNo = $change[ 'fcustno' ];
		$custName = $change[ 'fcustname' ];
		$erpCustModel = M( '', 'erp_customer' );
		$erps = $erpCustModel->where( [ 'fcustno' => $custNo ] )->find();
		$debt = $change[ 'debt' ]; //欠款额度
		$erpCustModel->startTrans();

		$credit = (int)$change[ 'account' ][ 'credit' ];
		if ( (int)$debt === 0 ) {
			OrderController::storeCustNoSyncOrder( $custNo );
		}
		
		//erp_customer 表存在客户信息
		if ( $erps ) {
			$erpSaveRes = $erpCustModel->where( [ 'fcustno' => $custNo ] )->save( [
				'fcustname' => $custName,
				'fcustjc'   => $change[ 'fcustjc' ],
				'fsale'     => $change[ 'fsale' ],
				'fsalename' => $change[ 'fsalename' ],
			] );
          	$modelR = M('','sys_user');
			$vno=$change[ 'fsale' ]?$change[ 'fsale' ]:'';
			$resR = $modelR->field('uid')->where(['FEmplNo'=>$vno])->find();
			if($resR){
				M('user')->where(['fcustno'=>$custNo])->save(['sys_uid'=>$resR['uid']]);
			}
          //file_put_contents('tttt.txt','8888');
			if ( $erpSaveRes === false ) {
				
				Log::write( 'ERP客户' . $custNo . $custName . '在erp_customer表的数据更新失败' );
				$erpCustModel->rollback();
				
				return false;
			}
		} else {
			//如果未找到则添加客户
			$erpInsertRes = $erpCustModel->add( [
				'fcustno'   => $custNo,
				'fcustname' => $custName,
				'fcustjc'   => $change[ 'fcustjc' ],
				'fsale'     => $change[ 'fsale' ],
				'fsalename' => $change[ 'fsalename' ],
			] );
			if ( !$erpInsertRes ) {
				Log::write( 'ERP客户' . $custNo . $custName . '在erp_customer表添加失败' );
				$erpCustModel->rollback();
				
				return false;
			}
		}
		//更新客户信息表 (有绑定ERP账户)
		$userModel = new UserModel();
		$userWhere['fcustno'] = $custNo;
		$userWhere['user_type'] = ['in',[1,2]];
		$user = $userModel->where( $userWhere )->find();
		if ( $user ) {
			$id = $user[ 'id' ];
			$userSaveRes = $userModel->where( [ 'id' => $id ] )->save( [
				'fcustjc' => $change[ 'fcustjc' ],
			] );
			if ( $userSaveRes === false ) {
				Log::write( 'ERP客户' . $custNo . $custName . '在dx_user表中的用户id为' . $id . '的数据更新失败' );
				$erpCustModel->rollback();
				
				return false;
			}
			//有绑定ERP账户
			$accountData[ 'user_id' ] = $user[ 'id' ];
		}
		
		//更新ERP客户账期表
		$accountData[ 'quota' ] = $credit;
		$accountData[ 'used_debt' ] = $debt;
		$accountData['used_quota'] = floatval($change[ 'account' ][ 'used_quota' ]);
        	switch ( (int)$change[ 'account' ][ 'payment' ] ) {
                      case 1: //月结 ( 当月在账款截止日之前的订单都在下单时间的后一月的 付款日+1天 付款)
                          //月结天数
                          $accountData[ 'account_type' ] = 1;
                          $accountData[ 'account_days' ] = (int)$change[ 'account' ][ 'days' ];
                          //账款截止日
                          $accountData[ 'month_dzclosedate' ] = (int)$change[ 'account' ][ 'dzclosedate' ];
                          //对账日
                          $accountData[ 'month_dzdate' ] = (int)$change[ 'account' ][ 'dzdate' ];
                          //付款日
                          $accountData[ 'month_fkdate' ] = (int)$change[ 'account' ][ 'fkdate' ];
                          break;
                      case 2://数期(每个订单延期数期天数付款)
                          $accountData[ 'account_type' ] = 2;
                          //数期天数
                          $accountData[ 'account_days' ] = (int)$change[ 'account' ][ 'days' ];
                          break;
                      case 3://周结(下单时间的后一周的星期一付款)
                          $accountData[ 'account_type' ] = 3;
                          //周结天数
                          $accountData[ 'account_days' ] = (int)$change[ 'account' ][ 'days' ];
                          break;
			}
		$acc = M( '', 'erp_accounts' );
		$cust = $acc->where( [ 'fcustno' => $custNo ] )->find();
		if ( $cust ) { //表中存在纪录 那么更新
          if($cust['since_name']=='现金'|| !$cust['since_name']){
           		$accountData['quota']=0;
                $accountData['since_name']='现金';
          }
          $accountData[ 'status' ] = 1;
			//如果欠款大于0
			if ( (int)$debt > 0 ) {
				$accountData[ 'status' ] = 2;
			}
			$accSaveRes = $acc->where( [ 'fcustno' => $custNo ] )->save( $accountData );
			if ( $accSaveRes === false ) {
				Log::write( 'ERP客户' . $custNo . $custName . '在erp_accounts表更新失败' );
				$erpCustModel->rollback();
				
				return false;
			}
		} else { //否则新增数据
			$accountData[ 'fcustno' ] = $custNo;
			$accountData[ 'status' ] = 1;
			//如果欠款大于0
			if ( (int)$debt > 0 ) {
				$accountData[ 'status' ] = 2;
			}
			$accAddRes = $acc->add( $accountData );
			if ( $accAddRes === false ) {
				Log::write( 'ERP客户' . $custNo . $custName . '在erp_accounts表添加失败' );
				$erpCustModel->rollback();
				
				return false;
			}
		}
		if ( $user ) {
			//检查是否存在user_account中
			$userAcc = M( 'user_account' )->where( [ 'user_id' => $user[ 'id' ] ] )->find();
			$userAccData[ 'quota' ] = $credit;
            if($cust['since_name']=='现金'|| !$cust['since_name']){
                 $userAccData[ 'quota' ]=0;
            }
			if ( $userAcc ) {
              $userAccData[ 'status' ] = 1;
				//如果欠款大于0
				if ( (int)$debt > 0 ) {
					$userAccData[ 'status' ] = 2;
				}
				$userAccRes = M( 'user_account' )->where( [ 'user_id' => $user[ 'id' ] ] )->save( $userAccData );
				if ( $userAccRes === false ) {
					Log::write( 'ERP客户' . $custNo . $custName . '商城客户id' . $user[ 'id' ] . '在user_account表的账期更新失败' );
					$erpCustModel->rollback();
					
					return false;
				}
			} else {
				$userAccData[ 'status' ] = 1;
				//如果欠款大于0
				if ( (int)$debt > 0 ) {
					$userAccData[ 'status' ] = 2;
				}
				$userAccData[ 'user_id' ] = $user[ 'id' ];
				$userAccData[ 'type' ] = $user[ 'user_type' ] === 1 ? 1 : ( $user[ 'user_type' ] === 2 ? 2 : 0 );
				$userAccRes = M( 'user_account' )->add( $userAccData );
				if ( $userAccRes === false ) {
					Log::write( 'ERP客户' . $custNo . $custName . '商城客户id' . $user[ 'id' ] . '在user_account表的账期更新失败' );
					$erpCustModel->rollback();
					
					return false;
				}
			}
		}
		
		$erpCustModel->commit();
		
		return true;
		
	}
	
	/*
	 * ERP客户数据Insert事件处理
	 */
	public function insertStore( $data )
	{
		return $this->updateStore( $data );
	}
	
	/*
	 * ERP客户数据Delete事件处理
	 */
	public function deleteStore( $data )
	{
		$change = $data[ 'change' ];
		if ( $change[ 'isDel' ] ) {
			$custNo = $change[ 'fcustno' ];
			$erpCustModel = M( '', 'erp_customer' );
			$erps = $erpCustModel->where( [ 'fcustno' => $custNo ] )->find();
			if ( $erps ) {
				$erpCustModel->startTrans();
				$erpDel = $erpCustModel->where( [ 'fcustno' => $custNo ] )->delete();
				if ( $erpDel === false ) {
					$erpCustModel->rollback();
					Log::write( 'ERP客户' . $custNo . '在erp_customer表的数据删除失败' );
					
					return false;
				}
				//更新客户表
				$userModel = new UserModel();
				$users = $userModel->where( [ 'fcustno' => $custNo ] )->select();
				foreach ( $users as $user ) {
					$id = $user[ 'id' ];
					$userSaveRes = $userModel->where( [ 'id' => $id ] )->save( [
						'fcustno' => '',
						'fcustjc' => '',
					] );
					if ( $userSaveRes === false ) {
						$erpCustModel->rollback();
						Log::write( 'ERP客户' . $custNo . '在dx_user表中的用户id为' . $id . '的数据更新失败' );
						
						return false;
					}
				}
				
				//更新客户账期表
				$accModel = M( '', 'erp_accounts' );
				$accDelRes = $accModel->where( [ 'fcustno' => $custNo ] )->delete();
				if ( $accDelRes === false ) {
					$erpCustModel->rollback();
					Log::write( 'ERP客户' . $custNo . '在erp_accounts表中的数据删除失败' );
					
					return false;
				}
				
				$erpCustModel->commit();
			}
		}
		
		return true;
	}
	
	
}