<?php
/**
 * Created by PhpStorm.
 * User: dxsj009
 * Date: 2018\12\21 0021
 * Time: 15:07
 */

namespace Home\Model;
use Think\Model;

class SolutionNotifyModel extends Model
{
	//	查看所有消息+洽谈消息条数
	/*
	 * $uid.用户id
	 * $type:具体到某一个类型消息的数量统计,(暂时为洽谈消息专用,用不到则留空)
	 * $isInfo:是否查询详细的消息信息,true or false
	 * */
	public function get_msg($uid,$type=0,$isInfo,$is_read=0)
	{
		$arr = [];
		$userId = $uid?$uid:session('userId');
		$isChain= 'left join dx_solution_notify as N on U.notify_id = N.id';
		$_notifyUser = M('solution_notify_user');
		if($type){
			$where0 = ['U.uid'=>$userId,'U.is_read'=>0,'type'=>$type,'U.deleted'=>0,'N.deleted'=>0];
			$count_one   = $_notifyUser ->alias('U')->join($isChain)->where($where0)->count();
			//getsql();print_e($count_one);
			if( $count_one ===false) {
				return false;die;
			}else{
				$arr['count_one'] = $count_one;
			}
		}
		$where1 = ['U.uid'=>$userId,'U.is_read'=>$is_read,'U.deleted'=>0,'N.deleted'=>0];
		$where2 = [	 'uid'=>$userId,  'is_read'=>$is_read,'deleted'=>0];
		$count_all = $_notifyUser -> where($where2) -> count();
		if($isInfo==true){
			$order = 'create_time desc';
			$field = '*,U.id';
			$msg= $_notifyUser->alias('U')->join($isChain)->where($where1)->field($field)->order($order)->select();
			if( $msg !== false || $count_all !== false ){
				$arr['count_all']= $count_all;
				$arr['msg'] = $msg;
				//getsql(); print_e($arr);die;
				return $arr;
			}else{
				return false ;
			}
		}else{
			if($count_all !==false){
				$arr['count_all']=$count_all;
				return $arr;
			}else{
				return false;
			}
		}
	}
	//	添加消息
	/*
	 * $uid:接受消息的用户id
	 * $sendId:发起消息的用户id
	 * $data,消息的实体(包含多个内容)
	 * */
	public function add_msg($sendId,$getId,$data)
	{
		$type 	= $data['type'];
		$target = $data['target'];
		$msg_data1 = ['send_id'=>$sendId,'type'=>$type,'target'=>$target];
		if($data['target_type'])$msg_data1['target_type'] = $data['target_type'];
		if($data['content']) 	 $msg_data1['content'] 	   = $data['content'];
		if($data['action'] )	 $msg_data1['action']	   = $data['action'];

		M()->startTrans();
			$field1 = 'send_id,type,target,target_type,action,content';
//			print_e($msg_data1);die;
			$temp1     = M('solution_notify')->field($field1)->add($msg_data1);
//			getsql();print_e($msg_data1);dump($temp1);
		if($temp1){
			$field2 = 'notify_id,uid';
			$msg_data2  = ['notify_id'=>$temp1,'uid'=>$getId];
			$temp2  	= M('solution_notify_user')->field($field2)->add($msg_data2);
//			getsql();print_e($msg_data2);dump($temp2);die;
			if($temp2){
				M()->commit();
				return true;
			}else{
				M()->rollback();
				return false;
			}
		}else{
			M()->rollback();
			return false;
		}
	}
	//	修改消息状态(参数:用户id 和 是否阅读)
	public function setMsgReaded($uid,$data)
	{
		$read = ['is_read'=>$data['is_read']];
		$where=	['uid'=>$uid,'id'=>$data['id']];
		$res  = M('solution_notify_user')->where($where)->save($read);
		if($res){
			return ['error'=>0,'msg'=>'修改成功'];
		}else{
			return ['error'=>1,'msg'=>'修改失败'];
		}
	}
	//删除消息
	public function deleteMsg($data)
	{
		$where1 = ['uid'=>$data['uid'],'id'=>$data['id']];
		$where2 = ['id'=>$data['notify_id']];
		M()->startTrans();
		$rez1 	= M('solution_notify_user')->where($where1)->save(['deleted'=>1]);
		if(!$rez1){
			M()->rollback();
			return ['error'=>1,'msg'=>'删除失败'];
		}
		$rez2 = M('solution_notify')->where($where2)->save(['deleted'=>1]);
		if(!$rez2){
			M()->rollback();
			return ['error'=>1,'msg'=>'删除失败'];
		}
		M()->commit();
		return ['error'=>0,'msg'=>'删除成功'];

	}
}