<?php
/**
 * Created by PhpStorm.
 * User: dxsj009
 * Date: 2018\11\27 0027
 * Time: 9:31
 */

namespace Admin\Model;
use Home\Model\SolutionDesiredModel;
use Home\Model\SolutionNotifyModel;
use Home\Model\SolutionModel as HomeSolutionModel;

class SolutionModel extends BaseModel
{
	//洽谈
	public function converseList($where,$field)
	{
		if(empty($where)){
			return false;
		}
		if(empty($field)){
			$field = ['C.pro_id,C.need_id,C.desir_id,C.desir_sn,C.price,C.con_time,C.con_idea,C.con_auth,V.nick_name'];
		}else{
			$field = ['C.pro_id,C.need_id,C.desir_id,C.desir_sn,C.price,C.con_time,C.con_idea,C.con_auth,V.nick_name'];
		}
		$res = M('solution_converse')->alias('C')->join('left join dx_solution_vip as V on V.uid = C.pro_id ')->field($field)->where($where)->select();
		if(!empty($res)){
			$all_name = M('solution_vip')->field('uid,nick_name')->select();
			foreach($all_name as $k=>&$v){
				foreach($res as $k2=>&$v2){
					if($v2['need_id']==$v['uid']){
						$v2['need_id'] = $v['nick_name'];
					}
				}
			}
		}
//		[0] => Array
//		(
//		[pro_id] => 1750
//      [need_id] => kk23
//		[desir_id] => 123
//      [desir_sn] => D1811141786176
//		[price] => 免费
//		[con_time] => 2018-11-08
//      [con_idea] => 我要洽谈
//		[con_auth] => 1
//      [nick_name] => kk23
//        )
//		getsql();print_e($res);die;
		return $res;
	}

	//方案类型($action操作:1查看,2添加,3修改,4删除)
	public function solTypes($action,$data,$where,$page,$pageSize,$limit)
	{
		$temp = M('solutionType')->where($where)->find();
		switch($action){
			case 1://类型查看
				$typeList = M('solutionType')->where($where)->limit($limit)->select();
//				print_e($limit);getsql();die;
				$count	  = M('solutionType')->count();
				if($typeList!==false){
					$temp_data['count']=$count;
					$temp_data['list'] =$typeList;
					$temp_data['page'] =$page;
					$temp_data['pageSize'] = $pageSize;
					return $temp_data;die;
				}
				break;
			case 2://类型增加
				if($temp === null){
					$rez = M('solutionType')->add($data);
				}
				break;
			case 3://类型删除
				if(!empty($temp)){
					$rez = M('solutionType')->where($where)->delete();
				}
				break;
			case 4://类型修改
				if(!empty($temp)){
					$rez = M('solutionType')->where($where)->save($data);
				}
				break;
			default:return false;
		}
		if(!empty($rez)){
			return $rez;
		}else{
			return false;
		}

	}

	//方案审核
	/*
	 * param:$data,包含审核人的sys_uid和审核所需的相关数据,
	 * $where:审核的必须条件
	 * $where_add:添加信息的必须条件;sol_id:操作对象的id(比如方案的id);sol_uid:消息接受人的id;
	 * */
	public function solAudit($data,$where,$type,$data_add)
	{

		if($type ==1){
			$model="solutionProvider";
			$target_type = '供应方案';
		}elseif($type==2){
			$model='solutionDesired';
			$target_type = '需求方案';
		}
//		print_e($data);	print_e($data_add);die;
		$msg_data = ['type'=>1,'target'=>$data_add['sol_id'],'target_type'=>$target_type,'action'=>$data_add['action']];
		M()->startTrans();
		if($where['jl_self']==1){//自营方案审核暂时不写入消息列表
			$data['create_time']=date('Y-m-d H:i:s',time());//自营修改等于更新时间
			$temp = D($model)->where($where)->save($data);
			if(!$temp){
				M()->rollback();
				return false;
			}else{
				M()->commit();
				return true;
			}
		}else{
			$temp = D($model)->where($where)->save($data);
			$msg  = D('Home/solution_notify')->add_msg($data['sys_uid'],$data_add['sol_uid'],$msg_data);
			if(!$temp || !$msg){
				return false;
			}else{
				return true;
			}
		}
	}

	//方案列表
	public function solution3($data,$solType)
	{
		//传入的数据格式(例子)
//		$where = [
//			'sol_name' =>[
//						[0] => 'like',
//						[1] => '%方案名%'
//			],
//			'nick_name' =>[
//							[0] => 'like',
//							[1] => ''%用户名%''
//			],
//			'types'=> 2,
//			'create_time' =>[
//							[0] => 'between',
//							[1] =>[
//									[0] => '2018-11-12',
//									[1] => '2018-11-13 00:00:00',
//							]
//			]
//		];
		$request = $data;
		$page	 =$request['page']?$request['page']:1;
		$pageSize=$request['pageSize']?$request['pageSize']:C('PAGE_PAGESIZE');
		$limit	 =($page-1)*$pageSize.','.$pageSize;
		$where	 =$request['where'];
//		print_e($limit);die;
		if($where){//搜索条件处理
			$whereRez = $whereResult=(new \Admin\model\BaseModel())->searchWhere($where);
			if($whereResult['error']!=0) die(json_encode($whereRez));
			$where	  =	$whereRez['data'];
			switch($where['sol_status']){//方案状态
				case 1:$where['publish_status']=1;$where['check_status']=1;$where['draft']=0;break;//发布中
				case 2:$where['publish_status']=2;$where['check_status']=1;$where['draft']=0;break;//已结束
				case 3:$where['check_status']=0;$where['draft']=0;$where['publish_status']=0;break;//审核中
				case 4:$where['check_status']=1;$where['draft']=0;break;//已通过
				case 5:$where['check_status']=2;$where['draft']=0;$where['publish_status']=0;break;//未通过
				//case 7:$where['check_status']=0;$where['draft']=1;$where['publish_status']=0;break;//草稿
				//case 9:unset($where['sol_status']);break;
				case 9:$where['draft'] = 0;break;//全部
				default:$where['check_status']=0;$where['draft']=0;$where['publish_status']=0;break;//审核中;
			}
		}else{
			$where['check_status']=0;$where['draft']=0;$where['publish_status']=0;//审核中;
		}
		$where['delete'] = 0;
		unset($where['sol_status']);
		if(isset($where['create_at_start'])||isset($where['create_at_end'])){//方案时间
			$create_at_start=$where['create_at_start']?:'0000-00-00 00:00:00';
			$create_at_end=$where['create_at_end']?date('Y-m-d 00:00:00',strtotime('+1 day',strtotime($where['create_at_end']))):'3000-00-00 00:00:00';
			$where['create_time']=['between',[$create_at_start,$create_at_end]];
			unset($where['create_at_start']);
			unset($where['create_at_end']);
		}
//		$where = $request;
//		$sork = $req['sork'] ? $req['sork']:'pubtime';//排序
//		$desc = $req['desc'] ? $req['desc']:'desc';
		if($solType==1||$solType==3){//供应
			$_model = 'solutionProvider';
			if(!empty($where['sol_name']))	{
				$where['pro_name'] = $where['sol_name'];
			}
		}elseif($solType==2||$solType==4){//需求
			$_model = 'solutionDesired';
			if(!empty($where['sol_name'])){
				$where['desir_name'] = $where['sol_name'];
			}
		}else{
			return false;
		}
		unset($where['sol_name']);

		if(!empty($req['types'])){//方案类型
			$where['types'] = $req['types'];
		}
		//判断大类型(供,需)
		if($solType==1||$solType==3){//供应
			if(isset($req['create_time']) && $req['create_time']){//时间区间
				$where['P.create_time']=$req['create_time'];
				unset($req['create_time']);
			}
			if($solType==1){//私营
				$where['jl_self']=0;
			}elseif($solType==3){//自营
				$where['jl_self']=1;
			}
			$field  = ['*,P.id,P.uid,P.create_time,P.update_time,V.nick_name,V.nick_picture'];//搜索的字段
			$temp	= $this ->sol_page($_model,$where,$field,$limit,$solType);//分页搜索

		}elseif($solType == 2||$solType==4){//需求
			if(isset($req['create_time']) && $req['create_time']){//时间区间
				$where['D.create_time']=$req['create_time'];
				unset($req['create_time']);
			}
			if($solType==2){//私营
				$where['jl_self']=0;
			}elseif($solType==4){//自营
				$where['jl_self']=1;
			}
//			if(isset($req['create_time']) && $req['create_time']){//时间区间
//				$where['D.create_time']=$req['create_time'];unset($req['create_time']);
//			}
//			if($req['start_time'] && !$req['end_time']){//时间区间
//				$where['P.create_time']=['egt',$req['create_time']];
//			}
//			if($req['end_time'] && !$req['start_time']){
//				$where['P.create_time']=['elt',$req['create_time']];
//			}
//			if($req['start_time'] && $req['end_time']){
//				$where['P.create_time']=['between',[$req['create_time'],$req['create_time']]];
//			}
//			$where['desir_name|nick_name|desir_sn'] = ['like','%'.$req['keyword'].'%'];//关键字
//			$where['publish_status']=$req['pub_status']?$req['pub_status']:1;
			$field  = ['*,D.id,D.uid,D.create_time,D.update_time,V.nick_name,V.nick_picture'];//字段
			$temp	= $this->sol_page($_model,$where,$field,$limit,$solType);
		}
		$temp['page'] = $page;
		$temp['pageSize'] = $pageSize;
//		print_e($where);getsql();print_e($temp);die;
		return $temp;
	}

	//方案中心分页方法(暂时只为列表展示使用)
	 /*$model (主)表名
	 $wherew:where查询条件条件
	 $page页数
	 $pageSize,每页的条数
	 $field ,搜索的字段;
	 $sork ,排序字段
	 $desc,倒排还是顺排,默认倒排
	 返回值,*/
	public function sol_page($model,$where,$field,$limit,$sol_type=1)
	{
		if($sol_type==1||$sol_type==3){//供应方案
			$count= D($model)->alias('P')->join("left join dx_solution_vip as V on P.uid = V.uid")->where($where)->count();//查记录总数
			$temp = D($model)->alias('P')->join("left join dx_solution_vip as V on P.uid = V.uid")->order('P.create_time desc')->where($where)->limit($limit)->field($field)->select();
//			getsql();print_e($temp);die;

		}elseif($sol_type==2||$sol_type==4){//需求方案
			$count= D($model)->alias('D')->join("left join dx_solution_vip as V on D.uid = V.uid")->where($where)->count();
			$temp = D($model)->alias('D')->join("left join dx_solution_vip as V on D.uid = V.uid")->order('D.create_time desc')->where($where)->limit($limit)->field($field)->select();
//			getsql();print_e($temp);die;
		}else{
			return null;
		}
		if($count!==false && $temp!==false){
			$Info['list'] = $temp;
			$Info['count']= $count;
			return $Info;
		}else{
			return false;
		}
	}

	//获取客服人员信息
	public function sysUserInfo($where,$field)
	{
		$Info = M('','sys_user')->field($field)->where($where)->select();
		if(!empty($Info))return $Info;
	}

	/*生成方案编号*/
	public function solSn($type)
	{
//		$sn = new  HomeSolutionModel();
//		$num= $sn->sn_factory($type);
		$num= D('Home/Solution')->sn_factory($type);
		if($num['error']==0){
			$sol_sn= $num['data']['one'];
			return $sol_sn;
		}else{
			return false;
		}
	}

}
