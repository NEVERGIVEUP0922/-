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

class OrderModel extends Model
{
    public $result;

    /**
     *
     * @desc 更改订单状态
     *
     */
    public function changeOrderStatus($where,$order_status){
        $result=M('order')->where($where)->save(['order_status'=>$order_status]);
        if(!$result) return ['error'=>1,'msg'=>'faild'];
        else return ['error'=>0,'msg'=>'success'];
    }


    /**
     *
     * 订单确认收货操作
     *
     **/
    public function confirmOrderShip( $order_sn )
    {
        return M('order')->where(['order_sn'=>$order_sn])->save([
            'order_status'=>3, //订单状态改为完成
            'ship_status'=>4,//收货状态改为 已全部收货
        ]);
    }


    /**
     *
     * @desc 支付水单上传
     * 帐期还款
     *
     */
    public function accountPayImg($request){
        $id=M('account_pay_history')->field('id')->where(['account_pay_sn'=>$request['account_pay_sn']])->find();
        if(!$id) return ['error'=>1,'msg'=>'参数错误'];

        if(!isset($request['pay_img'])||!$request['pay_img']) return ['error'=>1,'msg'=>'参数错误2'];

        $user_id=session('userId');
        $request['user_id']=$user_id;
        $request['pay_type']=1;
        $request['order_sn']=$request['account_pay_sn'];
        $request['account_pay_history_id']=$id['id'];
        $field='user_id,pay_type,pay_img,account_pay_history_id,order_sn';
        $m=M('order_pay_img');
//        $has=$m->field('id')->where(['user_id'=>$user_id,'pay_type'=>1])->find();
//        if($has){
//            $result=$m->where(['user_id'=>$user_id,'pay_type'=>1])->field($field)->save($request);
//        }else{
            $result=$m->field($field)->add($request);
//        }
        if(!$result){
            return ['error'=>1,'msg'=>'field'];
        }else{
            return ['error'=>0,'msg'=>'success'];
        }
    }

    /**
     *
     * @desc 订单的发货信息
     *
     */
    public function orderHy($orderSn_arr){
        $list=M('order_sync_hy')->where(['order_sn'=>['in',$orderSn_arr]])->select();
        print_r($list);
    }

    /**
     *
     * @desc 订单选中支付
     * param  $data=[
            'user_pay_step'=>[
                'table_prefix'=>'dx_',
                'field'=>'user_id,amount,index,type',
                'data'=>[
                    'user_id'=>'6',
                    'amount'=>'10000',
                    'index'=>'1806051767053',
                    'type'=>'1',
                ]
            ]
        ];
     *
     */
    public function orderPayStep($data='',$trans=true){
        $result_data=['error'=>0,'msg'=>'success','data'=>[]];
        if($trans) M()->startTrans();
        foreach($data as $k=>$v){
            $oneResult=['error'=>1,'msg'=>'failed'];
           if(M($k,$v['table_prefix'])->field($v[$k]['data'])->add($v['data'])){
               $oneResult=['error'=>0,'msg'=>'success'];
           }else{
               $oneResult=['error'=>1,'msg'=>'failed'];
               $result_data['error']=1;
               $result_data['msg']='failed';
           }
           $result_data['data'][]=$oneResult;
        }
        if($result_data['error']==0){
            if($trans) M()->commit();
        }else{
            if($trans) M()->rollback();
        }
        $this->result=$result_data;
        return $result_data;
    }


    /**
     *
     * @desc 首单减20,在线支付
     *
     */
    public function customerFirstOrder(){

        $userId=session('userId');
        $users=[];
        if(session('userType')==2){
            $users=M('user_son')->field('user_id')->where(['p_id'=>$userId])->select();
        }else if(session('userType')==20){
            $users=M('user_son')->field('user_id,p_id')->where(["user_id in (select user_id from dx_user_son where p_id = (select p_id from dx_user_son where user_id = $userId))"])->select();
        }
        $where_user=[$userId];
        if($users){
            foreach($users as $k=>$v){
                $where_user[]=$v['user_id'];
                if(session('userType')==20)$where_user[]=$v['p_id'];
            }
        }
        $where_user=array_unique($where_user);

        $where=[
            'user_id'=>['in',$where_user],
            'order_status'=>['not in',[100,101]]
        ];
        $pay_amount=20;

        $order=M('order')->field('count(id) order_num,order_sn,total,pay_type')->where($where)->find();
        if((int)$order['order_num']!==1) return ['error'=>1,'msg'=>'非首单'];
        if($order['pay_type']!=1) return ['error'=>1,'msg'=>'不是在线支付'];

        if($order['total']<=$pay_amount) return ['error'=>1,'msg'=>'定单总额要大于20'];

        $deposits_pay_type=0;
        if((int)$order['order_type']===1&&$order['total_deposits']<=$pay_amount){//定金
                $deposits_pay_type=1;
        }

        $order_data=[
            'already_paid'=>$pay_amount,
            'deposits_pay_type'=>$deposits_pay_type,
        ];
        $order_pay_history_data=[
            'order_sn'=>$order['order_sn'],
            'order_total'=>$order['total'],
            'pay_amount'=>$pay_amount,
            'type'=>'105',
            'pay_name'=>'首单减:'.$pay_amount,
        ];

        $result=M('order')->where(['order_sn'=>$order['order_sn']])->save($order_data);
        $result2=M('order_pay_history')->add($order_pay_history_data);

        if(!$result||!$result2){
            return ['error'=>1,'msg'=>'首单优惠失败'];
        }

        return ['error'=>0,'msg'=>'首单优惠成功'];
    }

    /**
     *
     * @desc 是否全款支付
     * @param $pay_type:定单支付方式
     * @param $total_deposits:定经总额
     *
     */
    public function orderIsAllPay($pay_type,$total_deposits,$order_type=0){
        if($total_deposits){
            if( in_array($pay_type,[2,3])){
                $order_type=1;
            }
        }
        return $order_type;
    }

    /**
     *
     * @desc 是否是自已的订单
     *
     */
    public function isMaster($order_sn){
        if(!$order_sn) return false;
        $return=false;
        $oneOrder=M('order')->field('id')->where(['order_sn'=>$order_sn,'user_id'=>session('userId')])->find();
        if($oneOrder) $return=true;
        return $return;
    }

    /**
     *
     * @desc 是否能下单
     *
     */
    public function isCreateOrder($pId_arr){
        $userId=session('userId');
        $pId_str=implode(',',$pId_arr);

        $Model = M();
        $sql="select dx_basket_detail.pid,dx_basket_detail.num,(erp_product.store-dx_basket_detail.num) as after_store,erp_product.store,dx_product.is_earnest,dx_product.earnest_scale from dx_basket_detail 
left join dx_product on dx_product.id=dx_basket_detail.pid 
left join erp_product on erp_product.fitemno=dx_product.fitemno 
 where basket_id = (select basket_id from dx_basket where user_id = $userId) and pid in($pId_str)";
        $list=$Model->query($sql);
        if(!$list) return ['error'=>1,'msg'=>'定单信息错误'];

        $has_earnest=[];//有定金
        $not_earnest=[];//没定金
        foreach($list as $k=>$v){
            if($v['after_store']<0){//库存不足
                if($v['is_earnest']&&$v['earnest_scale']>0){
                    $has_earnest[]=$v;
                }else{
                    $not_earnest[]=$v;
                }
            }else{
                $not_earnest[]=$v;
            }
        }

        if($not_earnest&&$has_earnest){
            return ['error'=>1,'msg'=>'定金和非定金商品不能同时下单'];
        }else{
            return ['error'=>0,'msg'=>'可以下单'];
        }
    }


}