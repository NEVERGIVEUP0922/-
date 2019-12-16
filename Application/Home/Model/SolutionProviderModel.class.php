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

class SolutionProviderModel extends SolutionModel
{
	/**
	 * @param $orderby 排序的字段
	 * @param $desc  排序方式,正排还是倒排,
	 * @return mixed 排序方式的组合结果
	 */
	public function solOrder($orderby,$desc='desc')
	{
		if(!$orderby){
			return $this->order("browse ".$desc);
		}else{
			return $this->order("$orderby $desc");
		}
	}

	//	我的供应方案
	/**
	 *参数分别是,用户id,查找字段,查找条数,排序条件,补充where条件
	 */
	public function myProviderInfo($uid,$field,$where,$limit,$order="update_time desc")
	{ 	$userId =$uid;
		$wheres =$where;
		if(empty($uid)){
			$userId = $_SESSION['userId'];
		}
		if(empty($userId)){
			return false;
		}
		if(empty($wheres)){
			$wheres= ['uid'=>$userId];
		}
		if(empty($field)){
//			$field = ['P.id,P.jl_self,P.pro_sn,P.pro_name,P.synopsis,P.pro_desc,P.types,P.pcba,P.pcbas,P.conver_image,P.bom_id,P.bom,P.code,P.files,P.browse,P.collection,P.volume,P.check_status,P.uid,P.publish_status,P.draft,P.pubtime,T.types'];
			$field = ['*,P.id,P.uid,P.create_time,P.update_time,T.types'];
		}
		/*$res  = M('SolutionProvider')->alias("P")->join("left join dx_solution_type as T on P.types = T.id")->field($field)->order('P.update_time desc')->where($addwhere)->where(['uid'=>$userId])->limit($limit)->order($order)->select();//方案信息*/
		$res  = M('SolutionProvider')->alias("P")->join("left join dx_solution_type as T on P.types = T.id")->field($field)->order('P.update_time desc')->where($wheres)->limit($limit)->order($order)->select();//方案信息*/
//		print_e($res);die;
		return $res;
	}
	//	我的浏览历史(混合排序的浏览记录)
	/**
	 *param:用户id,可选
	 *param:请求数据,必选,包含分页信息
	 *param:,行为,必选,1(我的浏览历史),2(项目管理首页我的浏览历史)
	 *param:$limit,可选,显示的条数
	 */
	public function myBrowseHistory($uid,$request,$action,$limit)
	{
		$res  = [];
		$arr1 = [];
		$arr2 = [];
		$arr3 = [];
		$arr4 = [];
		//分页限制
		$page = $request['page'] ?$request['page'] : 1;
		$pageSize= $request['pageSize']?$request['pageSize']:4;
//		$numPro  = count($arr1);
//		$numDire = count($arr2);
		$pageNum = $pageSize;/*表示检索结果某一行开始计算多少行*/

		$uid  = $uid?$uid:I('session.userId');
		$limit=$limit?$limit:($page-1)*$pageSize.','.$pageSize;
		$time = date('Y-m-d H:i:s',(time()-7*86400));
		//查浏览记录
		$temp = M('solution_browse')->field('sol_id,sol_sn,sol_type,update_time')->order('update_time desc')->where(['uid'=>$uid,'update_time'=>['gt',$time]])->limit($limit)->select();
//		getsql();
		$count= M('solution_browse')->where(['uid'=>$uid,'update_time'=>['gt',$time]])->count();
//		print_e($limit);
//		print_e($request);
//		print_e($temp);die;
		if(!$temp) return false;

		foreach($temp as $v){/*数据处理*/
			if($v['sol_type'] == 1){
				$v['id']	=$v['sol_id'];
				$v['pro_sn']=$v['sol_sn'];
				$arr1[]     =['P.id'=>$v['sol_id'],'P.pro_sn'=>$v['sol_sn']];
			}elseif($v['sol_type'] == 2){
				$v['id'] 	  = $v['sol_id'];
				$v['desir_sn']= $v['sol_sn'];
				$arr2[]       =['D.id'=>$v['sol_id'],'D.desir_sn'=>$v['sol_sn']];
			}else{
//				$this->error('浏览记录加载失败!');
				return false;
			}
		}

		if(!empty($arr1) && !empty($arr2)){
			$arr1['_logic'] = 'or';
			$arr2['_logic'] = 'or';
			$arr3[] = M('solution_provider')->alias("P")
				->join('left join dx_solution_type as T on T.id = P.types')
				->join('left join dx_solution_vip as V on V.uid = P.uid')
				->where($arr1)->field('*,P.id')->select();
			$arr3[] = M('solution_desired')->alias("D")
				->join('left join dx_solution_type as T on T.id = D.types')
				->join('left join dx_solution_vip as V on V.uid = D.uid')
				->where($arr2)->field('*,D.id')->select();
//			print_e($temp);
			foreach($arr3 as $v){
				foreach($v as $value){
					$res[] = $value;
				}}
//			print_e($temp);

		}elseif(!empty($arr1) && empty($arr2)){
			$arr1['_logic'] = 'or';
			$res = M('solution_provider')->alias("P")
				->join('left join dx_solution_vip as V on V.uid = P.uid')
				->join('left join dx_solution_type as T on T.id = P.types')
				->where($arr1)->field('*,P.id')->limit(($page-1)*$pageSize,$pageNum)->select();

		}elseif(!empty($arr2) && empty($arr1)){
			$arr2['_logic'] = 'or';
			$res = M('solution_desired')->alias("D")
				->join('left join dx_solution_vip as V on V.uid = D.uid')
				->join('left join dx_solution_type as T on T.id = D.types')
				->where($arr1)->field('*,D.id')->limit(($page-1)*$pageSize,$pageNum)->select();
		}
		//排序

		$final_res=[];
		foreach($temp as $k=>$v2){
			foreach($res as $k1=>$v3){
				if( $v2['sol_sn']==$v3['pro_sn'] ){
					$final_res[] = $v3;
					break;
				}
				if( $v2['sol_sn']==$v3['desir_sn'] ){
					$final_res[] = $v3;
					break;
				}
			}
		}
		// 实例化分页类 传入总记录数和每页显示的记录
		$Page = new \Think\Page( $count, 6 );
		$show = $Page->show();

		switch($action){
			case 1:return ['res'=>$final_res,'show'=>$show];break;
			case 2:return $final_res;break;
			default:return false;
		}
	}
	//分开的计算的浏览记录
	public function myBrowseHistory2($uid,$request,$action,$limit,$solType)
	{
		$res  = [];
		$arr1 = [];
		$arr2 = [];
		$arr3 = [];
		$arr4 = [];
		//分页限制
		$page = $request['page'] ?$request['page'] : 1;
		$pageSize= $request['pageSize']?$request['pageSize']:4;
		//		$numPro  = count($arr1);
		//		$numDire = count($arr2);
		$pageNum = $pageSize;/*表示检索结果某一行开始计算多少行*/

		$uid  = $uid?$uid:I('session.userId');
		$limit= $limit?$limit:($page-1)*$pageSize.','.$pageSize;
		$time = date('Y-m-d H:i:s',(time()-7*86400));
		//查浏览记录
		$temp = M('solution_browse')->field('sol_id,sol_sn,sol_type,update_time')->order('update_time desc')->where(['uid'=>$uid,'update_time'=>['gt',$time]])->select();
		if(!$temp) return false;

		foreach($temp as $v){/*数据处理*/
			if($v['sol_type'] == 1){
				$v['id']	=$v['sol_id'];
				$v['pro_sn']=$v['sol_sn'];
				$arr1[]     =['P.id'=>$v['sol_id'],'P.pro_sn'=>$v['sol_sn']];
			}elseif($v['sol_type'] == 2){
				$v['id'] 	  = $v['sol_id'];
				$v['desir_sn']= $v['sol_sn'];
				$arr2[]       =['D.id'=>$v['sol_id'],'D.desir_sn'=>$v['sol_sn']];
			}else{
				//$this->error('浏览记录加载失败!');
				return false;
			}
		}
		if($solType == 1){
			$arr1['_logic'] = 'or';
			$res = M('solution_provider')->alias("P")
				->join('left join dx_solution_vip as V on V.uid = P.uid')
				->join('left join dx_solution_type as T on T.id = P.types')
				->where($arr1)->field('*,P.id')->limit(($page-1)*$pageSize,$pageNum)->select();
			$count= M('solution_provider')->alias("P")->where($arr1)->count();
//			print_e($temp);
			foreach($arr3 as $v){
				foreach($v as $value){
					$res[] = $value;
				}}
//			print_e($temp);
		}elseif($solType == 2){
			$arr2['_logic'] = 'or';
			$res = M('solution_desired')->alias("D")
				->join('left join dx_solution_vip as V on V.uid = D.uid')
				->join('left join dx_solution_type as T on T.id = D.types')
				->where($arr1)->field('*,D.id')->limit(($page-1)*$pageSize,$pageNum)->select();
			$count = M('solution_desired')->where($arr1)->count();
		}
		$final_res=$res;
//		foreach($res as $k=>$v2){
//			foreach($res as $k1=>$v3){
//				if( $v2['sol_sn']==$v3['pro_sn'] ){
//					$final_res[] = $v3;
//					break;
//				}
//				if( $v2['sol_sn']==$v3['desir_sn'] ){
//					$final_res[] = $v3;
//					break;
//				}
//			}
//		}
//		print_e($arr1);
//		print_e($count);die;
		// 实例化分页类 传入总记录数和每页显示的记录
		$Page = new \Think\Page( $count, 4 );
		$show = $Page->show();

		switch($action){
			case 1:return ['res'=>$final_res,'show'=>$show];break;
			case 2:return $final_res;break;
			default:return false;
		}
	}
	//分页时计算条数
	public function pageCount($uid,$where,$type=1)
	{
		$userId =$uid;
		$wheres =$where;
		if(empty($uid)){
			$userId = $_SESSION['userId'];
		}
		if(empty($userId)){
			return false;
		}
//		if(empty($wheres)){
//			$wheres= ['uid'=>$userId];
//		}
		$wheres['uid']=$userId;
		if($type==1){
			$res = M('SolutionProvider')->where($wheres)->count();
		}elseif($type==2){
			$res = M('SolutionDesired')->where($wheres)->count();

		}
		return $res;
	}
}