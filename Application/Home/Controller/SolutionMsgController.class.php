<?php
/**
 * Created by PhpStorm.
 * User: dxsj009
 * Date: 2018\12\21 0021
 * Time: 14:32
 */

namespace Home\Controller;
use Home\Model\SolutionNotifyModel;

class SolutionMsgController extends SolutionController
{
	protected $msg_type ;
	protected function _initialize()
	{
		parent::_initialize();
		$this->msg_type = ['audit','conversing','conversed'];
	}
	//获取消息
	public function conversing_msg()
	{
		$uid  = session('userId');
		$data = D('solution_notify')->get_msg($uid,$this->msg_type[2],'conversed');
		dump( $this -> msg_type);
		print_e($data);die;
	}
	public function all_msg()
	{

	}
}