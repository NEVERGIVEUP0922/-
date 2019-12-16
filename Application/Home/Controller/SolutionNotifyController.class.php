<?php
/**
 * Created by PhpStorm.
 * User: dxsj009
 * Date: 2018\12\21 0021
 * Time: 14:32
 */

namespace Home\Controller;
use Home\Model\SolutionNotifyModel;

class SolutionNotifyController extends SolutionController
{
	protected $msg_type ;
	protected function _initialize()
	{
		parent::_initialize();
		$this->msg_type = ['audit','conversing','conversed'];
		//占时没用
	}
	//	未读的消息
	public function all_notify()
	{
		$uid  = $_SESSION['userId'];
		$is_read = I('get.is_read')?1:0;
		$data = D('Home/solution_notify')->get_msg($uid,'',true,$is_read);
		//print_e($data);die;
		$res  = $data['msg'];
		$this-> assign('res',$res);
		$this-> assign('sol_position','4');
		$this-> assign('had_read',$is_read);
		$this-> display('Solution/all_notify');
	}
	//	标记阅读状态
	public function setMsgReaded()
	{
		$user_id = session('userId');
		$msg	 = I( 'get.');
		if( empty($msg['id'])|| !isset($msg['is_read'])){
			$this->ajaxReturn( ['error'=>1,'msg'=>'参数不正确'] );
		}
		if($msg['is_read']==1) $data['is_read'] = 1;
		if($msg['is_read']==2) $data['is_read'] = 0;
		$data['id'] = $msg['id'];
		$rez = D('SolutionNotify')->setMsgReaded($user_id,$data);
		$this->ajaxReturn($rez);
	}
	//	删除消息
	public function deleteMSg()
	{
		$user_id = session('userId');
		$msg	 = I('get.');
		if(empty($msg['deleted'])||empty($msg['id'])||empty($msg['notify_id'])){
			$this->ajaxReturn( ['error'=>1,'msg'=>'参数不正确'] );
		}else{
			$msg['uid'] = $user_id;
			$rez = D('SolutionNotify')->deleteMsg($msg);
			$this->ajaxReturn($rez);
		}
	}

	/*------------------------------以下是客户消息的功能参考----------------------------*/
	//	检查用户是否存在
	protected function checkUserIsExists( $user_id, $is_admin )
	{
		if ( $is_admin ) {
			//检查后台用户是否存在
			$adminIsEx = ( new \Admin\Model\UserModel() )->checkUserExists( (int)$user_id );

			return $adminIsEx ? true : false;
		} else {
			//检查前台用户是否存在
			$homeIsEx = ( new \Home\Model\UserModel() )->checkUserExists( (int)$user_id );

			return $homeIsEx ? true : false;
		}

	}
/*
 * 获取所有未读消息列表
 */
	public function getAllUnReadMsgList( )
	{
		$page = I('page', 1);
		$limit = I( 'limit', 10 );
		$user_id = session('adminId');
		$this->ajaxReturn(MQ::getInstance()->getMsgList( 1,$user_id, true, $page, $limit));
	}

	/*
	 * 获取所有消息列表(包括 未读, 已读)
	 */
	public function getAllMsgList()
	{
		$page = I('page', 1);
		$limit = I( 'limit', 10 );
		$user_id = session('adminId');
		$res = MQ::getInstance()->getMsgList( 0, $user_id, true, $page, $limit,false,true );
		if(IS_AJAX){
			$this->ajaxReturn($res);
		}
		$res['data']['limit'] = $limit;
		$this->assign('msgList',$res['data']);
		$this->display();
	}

	/*
	 * 获取新消息
	 *
	**/
	public function pullMsg()
	{
		$user_id = session('adminId');
		$time = I('time', 40);
		$i = 0;
		session_write_close();//关闭session锁 否则造成其他页面阻塞
		//while( true ){
		$res = MQ::getInstance()->getNewList( $user_id, true );
		//print_r($res);
		if( $res['error'] === 0 ){
			$this->ajaxReturn( ['new'=>$res['data']['count'],'data'=>$res['data']] );
		}else{
			$this->ajaxReturn( ['new'=>0,'data'=>[]] );
		}
		$i++;
		usleep(500000);//0.5秒 
		if( $i === (int)$time ){
			$this->ajaxReturn( ['new'=>0] );
		}
		// }
	}


	//获取信息内容
	public function getMsgContent()
	{
		$msg_id = I('msg_id',0);
		$user_id = session('adminId');
		if( $msg_id === 0 ){
			$this->ajaxReturn(['error'=>1, 'msg'=>'缺少参数!']);
		}
		$this->ajaxReturn(MQ::getInstance()->getMsgByMsgId( $user_id,true, $msg_id ));
	}

	/*
	 * 发送一条消息
	 *
	 */
	protected static function writeMsgToOne($recive_id, $msg_type, $msg_title, $msg_content, $user_id)
	{
		$dplist_id = '';
		if( $msg_type === 0 ){
			$sys = new SysModel();
			$dpList = $sys->getUserUpDepartment( (int)$recive_id );
			if( $dpList['error'] !== 0 ){
				return $dpList;
			}else{
				if( !empty( $dpList['data'] ) ){
					$dplist_id = ','.implode($dpList['data'],',');
				}
			}
		}else{
			$dplist_id = '';
		}
		$res = MQ::getInstance()->writeMsg( $user_id,false,0, $msg_type, $recive_id, $dplist_id,$msg_title,$msg_content);
		if( $res['error'] === 0 ){
			return true;
		}else{
			return $res;
		}
	}

	/*
	 *
	 * 发送一条消息到客户的业务员
	 */
	public static function writeMsgToUserSale( $user_id, $msg_title, $msg_content )
	{
		$userInfo = M('user')->where(['id'=>$user_id])->find();
		if(!$userInfo){
			return  false;
		}
		if($userInfo['user_type']==20){
			$pid=M('user_son')->field('p_id')->where(['user_id'=> $user_id ])->find();
			$userInfo1 = M('user')->field('sys_uid')->where(['id'=>(int)$pid['p_id']])->find();
			if($userInfo1){
				$userInfo['sys_uid']=$userInfo1['sys_uid'];
			}
		}

		$sale = $userInfo['sys_uid'];
		if( empty( $sale ) ){
			return null;
		}

		$msg = '{}{}{}客户信息{}客户id:'.$user_id.'{}用户名:'.$userInfo['user_name'].'{}ERP编码:'.$userInfo['fcustno'].'{}ERP名称:'.$userInfo['fcustjc'];
		$msg_content .= $msg;
		return  self::writeMsgToOne( $sale, 0,$msg_title,$msg_content,$user_id);
	}

	/*
	 *
	 * 发送一条系统消息到前台会员
	 */
	public static function writeMsgToHomeUser( $user_id, $msg_title, $msg_content )
	{
		return  MQ::getInstance()->writeMsg( 0,true,1, 0, $user_id, '',$msg_title,$msg_content);
	}


	/*
	 * 发送一条系统消息给前台会员(ajax)
	 */
	public function writeMsgToHomeUserByAjax()
	{
		$user_id = I( 'user_id' );
		$msg_title = I( 'msg_title' );
		$msg_content = I( 'msg_content' );
		$this->ajaxReturn( self::writeMsgToHomeUser( $user_id, $msg_title, $msg_content ) );
	}

	/**
	 *
	 * 设置消息已删除(ajax)
	 */
	public function setMsgDel( )
	{
		$user_id = session('adminId');
		$msg_id = I( 'msg_id', 0 );
		if( $msg_id === 0 ){
			$this->ajaxReturn( ['error'=>1, '参数为空或不正确'] );
		}
		$this->ajaxReturn(MQ::getInstance()->setMsgDel( $user_id, true, $msg_id ));
	}

	public function test()
	{
		$this->display('pullMsg');
	}

	public function demo()
	{
		de( $this->writeMsgToOne( 10439,0,'新消息123', '消息内容消息内容消息内容'  ) );
	}
}