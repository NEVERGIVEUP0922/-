<?php
// +----------------------------------------------------------------------
// | FileName:   KnotSyncLogModel.php
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date:  2018-05-14 11:31
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace Admin\Model;

use Think\Model;

class KnotSyncLogModel extends BaseModel
{
	
//	public function getAllLog( $order_sn )
//	{
//		return M('order_sync_log')->where( ['order_sn'=>$order_sn] )->select();
//	}
//
//	public function getMaxLindId( $order_sn )
//	{
//		$id = M('order_sync_log')->where(['order_sn'=>$order_sn])->count();
//		return $id+1;
//	}
	
	public function getAllSyncFailList()
	{
		$list = $failList = [];
		//所有同步失败有问题的订单(除了同步成功,同步)
		$allList = M('knot_order')->field('order_sn,sync_status,create_at,update_at,contenx as fail_desc')->where(['sync_status'=>['NOT IN',[1,3]]])->order('update_at desc')->select();
		foreach( $allList as $k=>$v ){
			//查询订单数据
			$user = M('order')->field('user_id')->where(['order_sn'=>$v['order_sn']])->find();
			if( empty($user) && $user !== false ){
				M('knot_order')->where(['order_sn'=>$v['order_sn']])->data(['sync_status'=>3])->save();
				continue;//该订单已删除/不存在数据
			}
			
			//查询用户信息数据
			$v['sync_status'] = (int)$v['sync_status'];
			if( $v['sync_status'] === 0 ){
				$v['fail_desc'] = '订单结单状态同步中';
			}
			$v = array_merge( $v,$this->getOrderUserInfo($user['user_id']));
			$list[] = $v;
		}
		
		return $list;
	}
	
	protected function getOrderUserInfo( $user_id )
	{
		$userInfo = M('user')->field('user_name,user_type,nick_name,fcustno,fcustjc,sys_uid')->where(['id'=>(int)$user_id])->find();
		if($userInfo&&$userInfo['user_type']==20){
			$pid=M('user_son')->field('p_id')->where(['user_id'=> $user_id ])->find();
			$userInfo1 = M('user')->field('user_name,user_type,nick_name,fcustno,fcustjc,sys_uid')->where(['id'=>(int)$pid['p_id']])->find();
			$userInfo['user_type']=$userInfo1['user_type'];
			$userInfo['fcustno']=$userInfo1['fcustno'];
			$userInfo['fcustjc']=$userInfo1['fcustjc'];
			$userInfo['sys_uid']=$userInfo1['sys_uid'];
		}
		if( !$userInfo ){
			return [];
		}else{
			if( (int)$userInfo['user_type'] === 2 ){
				$company = M('user_company')->field('company_name')->where(['user_id'=>(int)$user_id])->find();
				if( $company ){
					$userInfo['company_name'] = $company['company_name'];
				}
			}elseif( (int)$userInfo['user_type'] === 1  ){
				$userInfo['company_name'] = $userInfo['fcustjc']?$userInfo['fcustjc']:$userInfo['nick_name'];
			}
			$saleInfo = M('','sys_user')->field('FEmplNo,fullname')->where(['uid'=>$userInfo['sys_uid']])->find();
			return array_merge( $userInfo, $saleInfo );
		}
	}
}