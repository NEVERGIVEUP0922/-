<?php

// +----------------------------------------------------------------------
// | FileName:   UserModel.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/7/31 20:05
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace Home\Model;

use Think\Model;

class UserModel extends Model
{
	//定义数据库名 不带前缀
	protected $tableName = 'user';
	
	//根据user_id查询用户信息
	public function getUserInfo($user_id){
		if(empty($user_id)) {
			$user_id = $_SESSION['userId'];
		}
		if (empty($user_id)) {
			return false;
		}
		$where['id'] = $user_id;
		$field = 'id,user_name,nick_name,user_mobile,user_type,avator,last_time';
		$res = M('user')->field($field)->where($where)->find();
		return $res;
	}
	
	/**
	 * @desc 账期是否执行
	 *
	 */
	public function userAccountIsPass($userId){
		$userAccount=M('accounts','erp_')->where(['user_id'=>$userId])->find();
        if(!$userAccount||!$userAccount['since_name']||trim($userAccount['since_name'])=='现金'){
            return ['error'=>1,'msg'=>'没有可用账期','one'=>$userAccount];
        }
		if((int)($userAccount['used_debt']*100)>0) return ['error'=>2,'msg'=>'账期有预期未还','data'=>$userAccount['used_debt'],'one'=>$userAccount];
		if($userAccount['used_quota']<0){
			return ['error'=>0,'data'=>$userAccount['quota']-$userAccount['used_quota'],'data1'=>$userAccount['quota'],'used_quota'=>$userAccount['used_quota'],'one'=>$userAccount];
		}else{
			return ['error'=>0,'data'=>$userAccount['quota']-$userAccount['used_quota'],'data1'=>$userAccount['quota']-$userAccount['used_quota'],'used_quota'=>$userAccount['used_quota'],'one'=>$userAccount];
		}
		
	}
	
	/**
	 * @desc 勾选订单去账期还款订单信息
	 *
	 */
	public function orderToAccountInfo($orderSn_arr,$pay_type=1){
		if(!is_array($orderSn_arr)) return ['error'=>1,'msg'=>'参数错误'];
		$where=[
			'user_id'=>session('userId'),
			'order_sn'=>['in',$orderSn_arr],
			'is_invoice'=>in_array($pay_type,[1,5])?1:0,
			'pay_type'=>2,
			'pay_status'=>['neq',2],
			'ship_status'=>['neq',0]
		];
		$orders=M('order')->where($where)->select();
		if(!$orders||count($orders)!=count($orderSn_arr))  return ['error'=>1,'msg'=>'参数错误2'];
		
		$pays=M('order_pay_history')->where(['order_sn'=>['in',$orderSn_arr],'type'=>2])->select();
		if(!$pays||count($pays)!=count($orderSn_arr)) return ['error'=>1,'msg'=>'订单信息错误'];
		$surplus_total=0;
		foreach($pays as $k=>$v){
			$surplus_total+=$v['pay_amount'];
		}

        $knot=$this->orderKnotTotal($orderSn_arr);//退贷退款总额
        if($knot['error']!==0) return $knot;
        $surplus_total=$surplus_total-$knot['data']['total'];

        $Hy=$this->orderHyTotal($orderSn_arr);//已还款
        if($Hy['error']!==0) return $Hy;
        $surplus_total=$surplus_total-$Hy['data']['total'];

		return ['error'=>0,'data'=>['total'=>$surplus_total,'list'=>$pays]];
	}
	
	/**
	 * @desc 勾选订单去账期还款
	 *
	 */
	public function orderToAccountPay($orderSn_arr,$pay_type=1,$is_delete=0){
		$accountInfo=$this->orderToAccountInfo($orderSn_arr,$pay_type);
		if($accountInfo['error']!=0) return $accountInfo;
		$user_id=session('userId');
		$add=[
			'user_id'=>$user_id,
			'total'=>$accountInfo['data']['total'],
			'pay_type'=>$pay_type,
			'step'=>1,
			'is_del'=>1,
		];
		$m=M('account_pay_history');
		$payHistoryM=M('order_pay_history');
		$one=$m->where(['user_id'=>session('userId'),'pay_type'=>$pay_type,'step'=>1])->find();
		//        if($pay_type==1&&$one){//线上支付,2个小时后才可再次修改
		//            $lockTime=time()-strtotime($one['update_at']);
		//            if($lockTime<7200) return ['error'=>1,'msg'=>'锁定时间'.$lockTime.'秒'];
		//        }
		
		//判断之前勾选的支付方式是否已确认付款
		//        if($pay_type==1){
		//            $has_bank=$m->where(['user_id'=>session('userId'),'pay_type'=>5,'step'=>1,'is_del'=>1])->find();
		//            if($has_bank){
		//                $img_has_check=M('order_pay_img')->where(['account_pay_history_id'=>$has_bank['id'],'status'=>2])->find();
		//                if($img_has_check) return ['error'=>1,'msg'=>'银行转账:已有确认收款，请继续还款'];
		//            }
		//        }
		
		$m->startTrans();
		$isDel_where=[
			'user_id'=>$user_id,
			'step'=>1
		];
		if(in_array($pay_type,['1','5'])){//之前选中的归零
			$isDel_where['pay_type']=['in',['1','5']];
			$m->where($isDel_where)->save(['is_del'=>0]);
			M('order_pay_img')->where(['user_id'=>$user_id,'account_pay_history_id in (select id from dx_account_pay_history where is_del=0)'])->save(['is_del'=>0]);
		}
		if($one){//更新
			$account_pay_id=$m->where(['id'=>$one['id']])->save($add);
			if($pay_type==5){
				M('order_pay_img')->where(['user_id'=>$user_id,'account_pay_history_id in (select id from dx_account_pay_history where is_del=1)'])->save(['is_del'=>1]);
			}
			$accountId=$one['id'];
		}else{//添加
			$account_pay_sn=substr(date('ymd',time()).md5($add['user_id'].$add['pay_type']),0,16);
			$add['account_pay_sn']=$account_pay_sn;
			$account_pay_id=$m->add($add);
			$accountId=$account_pay_id;
		}
		if($is_delete==1){
			$result2=$payHistoryM->where(['account_pay_selected'=>['in',$accountId]])->save(['account_pay_selected'=>0]);
			if($result2!==false){
				$m->commit();
				return ['error'=>0,'msg'=>'取消成功','data'=>$accountInfo['data']];
			}else{
				$m->rollback();
				return ['error'=>1,'msg'=>'取消失败'];
			}
		}else{
			$result2=$payHistoryM->where(['account_pay_selected'=>$accountId])->save(['account_pay_selected'=>0]);
		}
		$result=$payHistoryM->where(['order_sn'=>['in',$orderSn_arr],'type'=>2])->save(['account_pay_selected'=>$accountId]);
		$accountInfo['data']['accountId']=$accountId;
		if($result2!==false&&$account_pay_id!==false&&$result!==false){
			$m->commit();
			return ['error'=>0,'msg'=>'选中成功','data'=>$accountInfo['data']];
		}else{
			$m->rollback();
			return ['error'=>1,'msg'=>'选中失败'];
		}
	}
	
	/**
	 * @desc 根据账期还款id取出数据
	 *
	 */
	public function accountPayInfo($accountId,$key){
		$account=M('account_pay_history')->where(['id'=>$accountId])->find();
		if(!$account) return ['error'=>1,'msg'=>'账期信息错误'];
		$list=M('order_pay_history')->where([$key=>$accountId])->select();
		if(!$list) return ['error'=>1,'msg'=>'订单信息错误'];
		$total=0;
		foreach($list as $k=>$v){
			$total+=$v['pay_amount'];
		}
		
		if((int)($account['total']*100)!=(int)($total*100)) return ['error'=>1,'msg'=>'账期金额不对1'];
		return ['error'=>0,'data'=>['account'=>$account,'list'=>$list]];
	}
	
	
	/*
	 * 检查前台用户是否存在
	 */
	public function checkUserExists( $userId )
	{
		return M('user')->where(['id'=>(int)$userId])->count() > 0 ? true:false;
	}


    /**
     *
     * @desc 计算订单退贷退款总额
     * @parameter:$orderSn_arr=[1806111008277,1806111008272,1806111008271];//定单编号数组
     *
     */
    public function orderKnotTotal($orderSn_arr){
        if(!$orderSn_arr||!is_array($orderSn_arr)) return ['error'=>1,'msg'=>'订单编号错误'];
        $where=[
            'order_sn'=>['in',$orderSn_arr],
            'handle_status'=>6,
        ];
        $retreat=M('order_retreat')->field('sum(retreat_money) as total')->where($where)->find();

        return ['error'=>0,'data'=>['total'=>(float)$retreat['total']]];
    }

    /**
     *
     * @desc 计算订单货运已付总额
     * @parameter:$orderSn_arr=[1806111008277,1806111008272,1806111008271];//定单编号数组
     *
     */
    public function orderHyTotal($orderSn_arr){
        if(!$orderSn_arr||!is_array($orderSn_arr)) return ['error'=>1,'msg'=>'订单编号错误'];
        $where=[
            'order_no'=>['in',$orderSn_arr],
        ];

        $hy=M('order_sync_hy')->field('sum(fcxacount) as total')->where($where)->find();

        return ['error'=>0,'data'=>['total'=>(float)$hy['total']]];
    }

    /**
     *
     * @desc 用户子帐号
     * @param 用户id
     *
     */
    public function userSonInfo($userId){
        $list=M('user_son')->alias('us')->field('us.user_id,u.nick_name')
            ->join('left join dx_user as u on u.id=us.user_id')
            ->where(['us.p_id'=>$userId])->select();
        if(!$list) return ['error'=>1,'msg'=>'没有子帐号'];

        return ['error'=>0,'data'=>['list'=>$list]];
    }

}