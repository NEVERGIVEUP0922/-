<?php

// +----------------------------------------------------------------------
// | FileName:   ProductModel.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/7 12:47
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace  Wallet\Model;


class IntegralModel extends EntryModel
{
    protected function _initialize(){
        parent::_initialize();
    }

    protected $trueTableName='wa_integral';
    protected $fields='id,user_id,amount_only_add,amount_has_pay,amount_forzen,amount_allow_pay,update_at,create_at,status';


    /**$integral
     * @desc 客户积分列表
     *
     */
    public function integralList($where='',$page='',$pageSize='',$order=''){
        $list=$this->baseList(M('integral','wa_'),$where,$page,$pageSize,$order,$this->fields);
        return $list;
    }

    /**
     * @desc 添加积分
     *
     */
    public function addIntegral($type='111',$project='',$orderData=''){
        $where=[
            'type'=>$type,
            'status'=>1
        ];

        $integralNum=0;
        $rule='';
        if($type==111){//固定积分
            $cell_code=MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
            $where['cell_code']= $cell_code;
            $rule=M('integral_rule','wa_')->where($where)->find();//固定积分
            $integralNum=$rule['num'];
        }else if($type==101){//下单商品积分
            $orderIntegral=$this->orderIntegral($orderData);
            if($orderIntegral['error']!=0) return $orderIntegral;
            $rule=$orderIntegral['rule'];
            $project=$orderData[0]['order_sn'];
            $integralNum=$orderIntegral['data'];
            $forzen=1;
        }

        $session=session();
        $result=$this->addIntegralTo($session['userId'],$integralNum,$rule,$project,$type,$forzen);
        return $result;
    }

    /**
     * @desc 计算订单积分
     *
     */
    public function orderIntegral($orderData){
        $pId_str='(';
        foreach($orderData as $k=>$v){
            $pId_str.=$v['p_id'].',';
        }
        $pId_str=substr($pId_str,0,-1).')';
        $field='type,min_amount,cell_code,max,scale,scale_step';
        $current_time=date('Y-m-d H:i:s');
        $ruleList=M('integral_rule','wa_')->field($field)->where("status=1 and start_time<='".$current_time."' and end_time >='".$current_time."' and (type=1 or (type=101 and cell_code in $pId_str))")->select();
        if(!$ruleList) return ['error'=>1,'data'=>0,'msg'=>'没有可用的积分规则','rule'=>$ruleList];
        $pId_scale_arr=[];
        foreach($ruleList as $k=>$v){
            if(!$v['cell_code'])$v['cell_code']='default';
            $pId_scale_arr[$v['cell_code']]=$v;
        }
        $integral=$integral_default=$sale_mun=0;
        foreach($orderData as $k=>$v){
            if($oneRule=$pId_scale_arr[$v['p_id']]){//特殊积分
                if($v['pay_subtotal']<$oneRule['min_amount']) continue;//最小积分金额
                $integral+=$this->integralScale($oneRule,$v['pay_subtotal']);
            }else if($oneRule=$pId_scale_arr['default']){//公共积分
                $sale_mun+=$v['pay_subtotal'];
            }else{//没有可用积分规则
                break;
            }
        }
        //公共积分是否生效
        if($sale_mun&&$sale_mun>=$pId_scale_arr['default']['min_amount']){
            $integral+=$this->integralScale($pId_scale_arr['default'],$sale_mun);
        }

        return ['error'=>0,'data'=>$integral,'rule'=>$ruleList];
    }

    /**
     * @desc 单个商品积分计算
     *
     */
    public function integralScale($oneRule,$pay_subtotal){
        $cell=10000;
        $scale=$oneRule['scale']/$cell;
        if($oneRule['scale_step']!=0){
            $scale=ceil($pay_subtotal/$oneRule['scale_step'])*$scale;
        }
        $total=$pay_subtotal*$scale;
        $total=((int)$oneRule['max']!=0&&$total>=$oneRule['max'])?$oneRule['max']:(int)$total;
        return $total;
    }

    /**
     * @desc 执行添加积分
     *
     */
    public function addIntegralTo($user_id,$num,$rule,$project,$rule_type,$forzen=0,$starTrans=false){
        $m=M('integral','wa_');
        if($starTrans) $m->startTrans();
        $integralId='';
        $userIntegral=$m->where(['user_id'=>$user_id])->find();
        $integralId=$userIntegral['id'];

        if($userIntegral['status']==20) return ['error'=>0,'msg'=>'客户积分被禁用'];
        if($forzen){//冻结积分
            $data=[
                'user_id'=>$user_id,
                'amount_forzen'=>(int)$userIntegral['amount_forzen']+$num,
            ];
        }else{
            $data=[
                'user_id'=>$user_id,
                'amount_only_add'=>(int)$userIntegral['amount_only_add']+($num<0?0:(int)$num),
                'amount_allow_pay'=>(int)$userIntegral['amount_allow_pay']+$num,
            ];
            if($num<0){
                $data['amount_has_pay']=-$num;
            }
        }
        if($userIntegral){
            $result=$m->where(['id'=>$userIntegral['id']])->save($data);
        }else{
            $result=$m->add($data);
            $integralId=$result;
        }

        if($result===false){
            if($starTrans) $m->rollback();
            return ['error'=>1,'msg'=>'failed'];
        }

        $data2=[
            'integral_id'=>$integralId,
            'rule_type'=>$rule_type,
            'project'=>$project,
            'amount_start'=>(int)$userIntegral['amount_allow_pay'],
            'amount_end'=>$forzen?(int)$userIntegral['amount_allow_pay']:(int)$userIntegral['amount_allow_pay']+$num,
            'amount'=>$num,
            'detail'=>json_encode($rule),
        ];

        $result2=M('integral_account','wa_')->add($data2);
        if(!$result2){
            if($starTrans) $m->rollback();
            return ['error'=>1,'msg'=>'failed2'];
        }else{
            if($starTrans) $m->commit();
            return ['error'=>0,'msg'=>'success','data'=>['integral_account_id'=>$result2,'integral_amount'=>(int)$data2['amount']]];
        }
    }

    /**
     * @desc 解冻定单的积分,定单退款减积分
     * $status=40 定单积分退还
     *
     */
    public function integralOrderThaw($orderSn,$status=10){
        $orderM=M('order');
        $orderWhere=['order_sn'=>$orderSn,'integral_status'=>10];
        if($status==40){
            $orderWhere['integral_status']=10;
        }else{
            $orderWhere['integral_status']=1;
        }
        $order=$orderM->field('user_id')->where($orderWhere)->find();
        if(!$order) return ['error'=>1,'msg'=>'积分信息不对'];
        $one=M('integral_account','wa_')->field('amount')->where(['rule_type'=>101,'project'=>$orderSn])->find();
        if(!$one) return ['error'=>1,'msg'=>'定单没有可解冻积分'];
        $m=M('integral','wa_');
        $integral=$m->field('id,amount_forzen,amount_allow_pay')->where(['user_id'=>$order['user_id'],'status'=>1])->find();
        if(!$integral) return ['error'=>1,'msg'=>'用户积分帐本信息不对'];

        $m->startTrans();

        if($status==40){//定单积分退还
            $integralData=[
                'amount_allow_pay'=>$integral['amount_allow_pay']-$one['amount']
            ];

            $integral_account=[
                'integral_id'=>$integral['id'],
                'rule_type'=>203,
                'project'=>$orderSn,
                'amount_start'=>(int)$integral['amount_allow_pay'],
                'amount_end'=>(int)$integral['amount_allow_pay']-$one['amount'],
                'amount'=>0-(int)$one['amount'],
            ];
        }else{
            $integralData=[
                'amount_forzen'=>$integral['amount_forzen']-$one['amount'],
                'amount_allow_pay'=>$integral['amount_allow_pay']+$one['amount']
            ];
        }

        $orderData=[
            'integral_status'=>$status,
            'integral'=>$one['amount'],
        ];

        if(isset($integral_account)){
            $result3=M('integral_account','wa_')->add($integral_account);
            if(!$result3){
                $m->rollback();
                return ['error'=>1,'msg'=>'积分记录错误'];
            }
        }

        $result=$m->where(['user_id'=>$order['user_id']])->save($integralData);
        if(!$result){
            $m->rollback();
            return ['error'=>1,'msg'=>'积分更新错误'];
        }
        $result2=$orderM->where(['order_sn'=>$orderSn])->save($orderData);
        if(!$result2){
            $m->rollback();
            return ['error'=>1,'msg'=>'定单更新错误'];
        }else{
            $m->commit();
            return ['error'=>0,'msg'=>'success'];
        }
    }

    /**
     * @desc 积分提现
     *
     */
    public function integralToWallet($userId,$integral){
        $currentTime=Date('Y-m-d H:i:s',time());
        $rule_type=205;
        $ruleWhere=[
            'type'=>$rule_type,
            'status'=>1,
            'start_time'=>['elt',$currentTime],
            'end_time'=>['egt',$currentTime],
        ];
        $rule=M('integral_rule','wa_')->field('min_amount,scale')->where($ruleWhere)->find();
        if(!$rule) return ['error'=>1,'msg'=>'没有设置提取规则'];
        $rule['scale']=$rule['scale']/10000;

        $min_integral=(int)($rule['min_amount']/$rule['scale']);
        $integralWhere=[
            'user_id'=>$userId,
            'status'=>1,
        ];
        $list=M('integral','wa_')->field('amount_forzen,amount_allow_pay')->where($integralWhere)->find();
        if(!$list) return ['error'=>1,'msg'=>'没有可提现积分'];
        if($list['amount_allow_pay']<$min_integral||$integral<$min_integral) return ['error'=>1,'msg'=>'积分不够'.$min_integral.'最低提现要求'];

        $this->startTrans();
        $result1=$this->addIntegralTo($userId,-$integral,$rule,$userId,$rule_type);
        if($result1['error']!=0){
            $this->rollback();
            return $result1;
        }
        $data=[
            'user_id'=>$userId,
            'type'=>1,
            'amount'=>(int)($integral*$rule['scale']),
            'wallet_account_type'=>12,
            'wallet_type'=>12,
            'id'=>$result1['data']['integral_account_id']
        ];

        $result2=D('Wallet/Wallet')->walletAccountAdd($data);
        if($result2['error']!=0){
            $this->rollback();
            return $result2;
        }else{
            $this->commit();
            return ['error'=>0,'msg'=>'提现成功'];
        }
    }

    /**
     * @desc 月度积分，年度积分
     * $type=月度21,年度41
     *
     */
    public function integralCrontab($type){
        $current_month='';
        $current_time=date('Y-m-d h:i:s');
        $where=[
            'status'=>1,
            'type'=>$type,
            'start_time'=>['elt',$current_time],
            'end_time'=>['egt',$current_time],
        ];
        $rule=M('integral_rule','wa_')->field('min_amount,start_time,end_time,max,scale,scale_step')->where($where)->find();
        if(!$rule) return ['error'=>1,'msg'=>'没有可执行的积分规则'];

        if($type==21){//月度
            $start_time=date('Y-m-00',strtotime('-1 month'));
            $end_time=date('Y-m-00');
            $current_month=date('Y-m-00');
        }else{//年度
            $start_time=date('Y-00-00',strtotime('-1 year'));
            $end_time=date('Y-00-00');
            $current_month=date('Y-00-00');
        }
        $whereUser=[
            'integral_status'=>10,
            'integral'=>['gt',0],
            'create_at'=>['between',[$start_time,$end_time]],
        ];
        $user=M('order')->field('user_id,sum(total) as total_sum')->where($whereUser)->group('user_id')->select();
        if(!$user) return ['error'=>1,'msg'=>'没有可执行积分的定单'];
        $userId_arr=[];
        foreach($user as $k=>$v){
            $userId_arr[]=$v['user_id'];
        }
        $account=M('integral_account','wa_')->field('project,id')->where(['project'=>['in',$userId_arr],'rule_type'=>$type,'create_at'=>['gt',$current_month]])->select();
        $has_account=[];
        foreach($account as $k=>$v){
            $has_account[$v['project']]=$v['project'];
        }

        //积分添加
        $error='';
        $error_code=0;
        foreach($user as $k=>$v){
            if($has_account[$v['user_id']]){
                $error['failed'][]=$v['user_id'].'已经积过分了';
                $error_code=1;
                continue;
            }
            if($rule['min_amount']&&$v['total_sum']<$rule['min_amount']){
                $error['failed'][]=$v['user_id'].'少于最小交易金额';
                $error_code=2;
                continue;
            }
            $integral=$this->integralScale($rule,$v['total_sum']);
            if(!(int)$integral){
                $error['failed'][]=$v['user_id'].'积分为0';
                $error_code=3;
                continue;
            }
            $result=$this->addIntegralTo($v['user_id'],$integral,$rule,$v['user_id'],$type,0);
            if($result['error']!=0){
                $error['failed'][]=$result;
                $error_code=4;
            }else{
                $error['success'][]=$result;
            }
        }
        $error['error']=$error_code;
        return $error;
    }

    /**
     * @desc 个人积分
     *
     */
    public function customerIntegral($user_id){
        $integral=M('integral','wa_')->field('amount_allow_pay as integral')->where(['user_id'=>$user_id,'status'=>1])->find();
        $integral_rule=M('integral_rule','wa_')->field('scale')->where(['type'=>205,'status'=>1])->find();
        $integral_RMB=0;
        if($integral_rule){
            $integral_rule['scale']=$integral_rule['scale']/10000;
            $integral_RMB=$integral['integral']*$integral_rule['scale'];
        }
        $integral['integral_RMB']=$integral_RMB;
        return ['error'=>0,'data'=>$integral];
    }










}