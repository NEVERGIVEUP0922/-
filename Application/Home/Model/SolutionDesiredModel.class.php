<?php
// +----------------------------------------------------------------------
// | FileName:   
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date: 
// +----------------------------------------------------------------------
// | Author: kk <1343188487@qq.com>
// +----------------------------------------------------------------------
namespace Home\Model;
use Think\Model;
use Home\Model\SolutionProviderModel;

class SolutionDesiredModel extends SolutionProviderModel
{
	//	我的需求方案
	/*
	 * 参数分别是,用户id,查找字段,查找条数,排序条件,补充where条件
	 * */
	public function myDesiredInfo($uid,$field,$where,$limit,$order="update_time desc")
	{ 	$userId =$uid;
		$wheres  =$where;//有传uid就按uid来,没有就用登录者的id
		if(empty($uid)){
			$userId = $_SESSION['userId'];
		}
		if(empty($userId)){
			return false;
		}
		if(empty($wheres)){
			$wheres = ['uid'=>$userId];
		}
		if(empty($field)){
//			$field = ['D.id,D.jl_self,D.desir_sn,D.desir_name,D.desir_desc,D.types,D.delivery,D.budget,D.browse,D.collection,D.linkman,D.mobile,D.qq,D.wechat,D.company,D.check_status,D.publish_status,D.pubtime,D.uid,D.draft'];
			$field = '*,D.id,D.create_time,D.update_time,T.types';
		}
		$res  = M('SolutionDesired')->alias("D")->join("left join dx_solution_type as T on D.types = T.id")->field($field)->order('D.'.$order)->where($wheres)->limit($limit)->select();//方案信息
		return $res;
	}

}