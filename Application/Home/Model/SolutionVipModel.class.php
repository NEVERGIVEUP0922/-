<?php
namespace Home\Model;

use Think\Model;

class SolutionVipModel extends SolutionModel
{
	//定义数据库名 不带前缀
	protected $tableName = 'solution_vip';
	//	获取会员信息
	public function getVipInfo($uid)
	{
		if(empty($uid)){
			$uid = $_SESSION['userId'];
		}
		if(empty($uid)){
			return false;
		}
		$info= M($this->tableName)->where(['uid'=>$uid])->find();
		return $info;
	}
}