<?php
/**
 * Created by PhpStorm.
 * User: dxsj009
 * Date: 2018\10\31 0031
 * Time: 9:41
 */

namespace Home\Model;
use Think\Model;

class SolutionModel extends Model
{
	//	编号工厂
	/*参数$type:1供应方案,2需求方案,3自营供应方案,4自营需求方案*/
	public function sn_factory($type)
	{
		if(!$type) return ['error'=>1,'msg'=>'方案类型错误'];
		$time=date('ymd',time());
		$sn  =M('solution_sn');//第一次获取
		M()->startTrans();
		$one = $sn->where(['is_lock'=>0,'sol_type'=>$type,'sol_sn'=>['like',"%$time%"]])->find();
		$one_sn=$sn->save(['sol_sn'=>$one['sol_sn'],'is_lock'=>1]);
		if($one&&$one_sn){
			M()->commit();
			return ['error'=>0,'data'=>['one'=>$one['sol_sn']]];
		}else{
			M()->rollback();
		}

		$result=$this->createSolSn($type);//生成新的一批订单编号

		$sn->startTrans();//第二次获取
		$one=$sn->where(['is_lock'=>0,'sol_type'=>$type])->find();
		$sn->save(['sol_sn'=>$one['sol_sn'],'is_lock'=>1]);
		if($one&&$sn){
			$sn->commit();
			return ['error'=>0,'data'=>['one'=>$one['sol_sn']]];
		}else{
			$sn->rollback();
			return ['error'=>1,'msg'=>'方案编号错误'];
		}
	}
	//	生成方案编号
	protected function createSolSn($type)
	{
		$time=date('ymd',time());
		$num=100;//一个类型的编号一次生成的条数(可以写在配置文件中)

		$solSn=M('solution_sn');
		$delete =$solSn->where(['sol_sn'=>['lt',$time*10000000]])->delete();

		$stop=1;
		$i=1;
		while($stop){
			$i++;
			if($i>5) break;
			$arr=$temp=[];
			$sol_type = '';
			switch($type){
				case 1:$sol_type='P';break;
				case 2:$sol_type='D';break;
				case 3:$sol_type='PS';break;
				case 4:$sol_type='DS';break;
			}
			for($i=0;$i<$num;$i++){
				$temp[]=$sol_type.$time.mt_rand(1000000,9999999);
			}
			$temp=array_unique($temp);
			foreach($temp as $k=>$v){
				$arr[$k]['sol_type'] = $type;
				$arr[$k]['sol_sn']=$v;
			}
			$result=$solSn->addAll($arr);
			if($result==$num)$stop=0;
		}
		if($result==$num){
			return ['error'=>0,'msg'=>$time.'----'.$num.'当日方案编号生成成功'];
		}else{
			return ['error'=>1,'msg'=>$time.'----'.$num.'当日方案编号生成失败'];
		}
	}

	//	检测会员的方案和洽谈次数
	/*$type:1是发布供应方案次数,2是发布需求方案次数,3是洽谈次数*/
	public function num_check($type)
	{
		$uid = session('userId');
		$vipInfo = M('solution_vip')->where(['uid'=>$uid])->find();
		if($type===1 && empty($vipInfo)){
			if($vipInfo['pro_num']>0){
				/*减少供应方案的次数*/
				$vipInfo['pro_num'] = $vipInfo['pro_num']-1;
				$rez = M('solution_vip')->where(['uid'=>$uid])->field('pro_num')->save($vipInfo['pro_num']);
				return $rez;
			}else{
				return ['error'=>1,'msg'=>'供应方案的次数为'.$vipInfo['pro_num']];
			}
		}elseif($type===2 && empty($vipInfo)){
			/*占位*/
		}elseif($type===3 && empty($vipInfo)){
			if($vipInfo['con_num']>0){
				/*减少洽谈方案的次数*/
				$vipInfo['con_num'] = $vipInfo['con_num']-1;
				$rez = M('solution_vip')->where(['uid'=>$uid])->field('con_num')->save($vipInfo['con_num']);
				return $rez;
			}else{
				return ['error'=>1,'msg'=>'洽谈的次数为'.$vipInfo['pro_num']];
			}
		}else{
			return false ;
		}
	}

	//	每日行为限制
	/*
	 * $action_name:要保存到session的name值,$num.限制的次数,$time限制的时间单位秒
	 * return :unlock代表解锁,lock代表锁定了
	 * */
	public function action_limit($action_name,$num=0,$time)
	{
		$val= session($action_name);

		if(empty($val)||(!empty($val)&&$val<$num)){
			session($action_name,$val+1);

		}elseif($val>=$num){
			$temp = M('solution_limit')->where(['uid' => $_SESSION['userId']])->find();

			if($temp){
				$lock_time = (time()-strtotime($temp['lock_time']));

				if($lock_time>$time &&$temp['is_lock']==0){
					$rez = M('solution_limit')->where(['uid' => $_SESSION['userId']])->save(['is_lock'=>1]);
					unset($_SESSION[$action_name]);
					return 'unlock';

				}else{
					$data = ['is_lock'=>0,'lock_time'=>date('Y-m-d H-i-s',time())];
					$rez = M('solution_limit')->where(['uid' => $_SESSION['userId']])->field('is_lock,lock_time')->save($data);
					return 'lock';
				}
			}else{
				$data = ['uid' => $_SESSION['userId'], 'action_name' => $action_name,'lock_time'=>date('Y-m-d H-i-s',time())];
				$rez = M('solution_limit')->where(['uid' => $_SESSION['userId']])->add($data);
				return 'lock';
			}
		}
	}

	//	分页(kstevenk)
	/*$count:记录总条数,$num每页显示条数*/
	public function pageLimit($count,$num){
		$Page = new \Think\Page( $count, $num );// 实例化分页类 传入总记录数和每页显示的记录
		$show = $Page->show();// 分页显示输出
		return $show;
	}
}