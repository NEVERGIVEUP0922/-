<?php
namespace Home\Model;
use Think\Model;
use Home\Model\SolutionProviderModel;

class SolutionConverseModel extends SolutionProviderModel
{
//	别人向我发起的洽谈
	//($where,$field是公共参数)
	//($order,$group是展示全部洽谈信息参数)
	public function myConverse($where,$field,$order,$group,$fenye)
	{
		if(empty($where)){
			return false;
		}
		$or = $order?'C.'.$order:'C.update_time desc';
		$gr = $group?'C.'.$group:'';
		$page 	 = $fenye['page'] ?$fenye['page'] : 1;
		$pageSize= $fenye['pageSize']?$fenye['pageSize']:6;
		$pageNum = $pageSize*($page-1);
		if(empty($field)){
			$field = '*,C.pro_id,C.need_id,C.desir_id,C.create_time,C.update_time';
		}
		if($group) $field .= ',group_concat(desir_sn)';
		$isChain = 'left join dx_solution_vip as V on V.uid = C.pro_id ';
		$res = M('solution_converse')->alias('C')->join($isChain)->field($field)->where($where)->limit($pageNum,$pageSize)->order($or)->group($gr)->select();
//		getsql();print_e($res);die;
		$num = M('solution_converse')->alias('C')->join($isChain)->where($where)->count();

		// 实例化分页类 传入总记录数和每页显示的记录
		$Pages = new \Think\Page( $num, $pageSize );
		$show = $Pages->show();
		if(!$res || !$num) return false;
		return ['res'=>$res,'page'=>$show];
	}

//	我向别人发起的洽谈
	public function conversing($where,$limit)
	{
		if(empty($where)){
			return false;
		}
		$field = '*,D.id,C.create_time';
//		$res = M('solution_converse')->alias('C')
//			->join('left join dx_solution_vip as V on V.uid = C.pro_id ')
//			->join('left join dx_solution_desired as D on D.id = C.desir_id')
//			->where($where)->field('*,C.create_time')->order('C.update_time desc')->select();
		$res = M('solution_desired')->alias('D')
			->join('left join dx_solution_converse as C on C.desir_id = D.id')
			->join('left join dx_solution_vip as V on V.uid = C.need_id ')
			->join('left join dx_solution_type as T on D.types = T.id')
			->where($where)->field($field)->order('C.update_time desc')->limit($limit)->select();
		return $res;
	}

}
?>