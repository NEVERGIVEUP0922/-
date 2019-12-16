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

namespace  Admin\Model;

use Small\Controller\BaseController;
use Think\Controller;
use Think\Model;

class OrderknotModel extends BaseModel{

    public $list;
    public $count;

    /**
     * 订单数据update,申请返差额
     * @param array $orderSn_arr 订单编号数组
     * @return array
     */
    public function toUpdate($where='',$data='',$field='',$action='insert'){
        $data['money']=0;
        $this->result=['error'=>1,'msg'=>'fail'];
        $param=explode(',',$field);
        $data['customer_account_type']=$data['customer_account_type']?$data['customer_account_type']:1;
        if($data['customer_account_type']==1){
            $callback=function($param) use($data){
                if(!isset($data[$param])){
                    $this->result=['error'=>2,'msg'=>'参数错误'];
                }
            };
            array_walk($param,$callback);
            if($this->result['error']===2) return $this;

            if(preg_match('/^1[\d]{10}$/',$data['notify_mobile'])===0){
                $this->result=['error'=>1,'msg'=>'手机号码格式错误'];
                return $this;
            }
        }
        $order_sn=$data['order_sn'];
//        $next=M('order_knot')->field('knot_no')->where(['order_sn'=>$order_sn,'check_status'=>['in',[1,20]]])->find();
//        if($next){
//            $this->result=['error'=>1,'msg'=>'申请已受理，请不要重复提交'];
//            return $this;
//        }
        $next=M('order_knot')->field('knot_no,check_status')->where(['order_sn'=>$order_sn])->find();
        if($next&&in_array($next['check_status'],[0,1,20])){
            $this->result=['error'=>1,'msg'=>'申请已受理或申请审核中，请不要重复提交'];
            return $this;
        }
        if(session('userId')){
            //PC端
            $order_where=['user_id'=>session('userId'),'order_sn'=>$order_sn,'order_status'=>['in',[2,3]]];
        }else{
            //小程序
            $baseModel=new \Small\Controller\BaseController;
            $userInfo=$baseModel->getUserInfo();
            $order_where=['user_id'=>$userInfo['id'],'order_sn'=>$order_sn,'order_status'=>['in',[2,3]]];
        }
        $hasOrder=D('Admin/Order')->orderlist($order_where);
        if($hasOrder['error']!==0){
            $this->result=['error'=>1,'msg'=>'订单错误'];
            return $this;
        }
        $goodsList=$hasOrder['data']['list'][0]['goodsList'];
//        if($action=='insert'){
//            array_walk($goodsList,function($goods) use(&$data){
//                $data['money']+=$goods['pay_subtotal']*($goods['p_num']-$goods['knot_num'])/$goods['p_num'];//实际发贷数量
//            });
//            $has_pay=M('order_pay_history')->field('sum(pay_amount) as pay_amount_total')->where(['order_sn'=>$order_sn])->find();//已付款总额
//            $data['money']=$has_pay?$has_pay['pay_amount_total']-$data['money']:0;//已付款总额-实际发贷数量金额总额
//            if(M('order_knot')->field($field)->add($data)){
//                $this->result=['error'=>0,'msg'=>'success'];
//            }
//        }else{
//            $where=[
//                'order_sn'=>$data['order_sn'],
//                'check_status'=>0
//            ];
//            if(M('order_knot')->where($where)->field($field)->save($data)!==false){
//                $this->result=['error'=>0,'msg'=>'success'];
//            }
//        }

        if(!$next){
            array_walk($goodsList,function($goods) use(&$data){
                $data['money']+=$goods['pay_subtotal']*($goods['p_num']-$goods['knot_num'])/$goods['p_num'];//实际发贷数量
            });
            $has_pay=hasOrderPay([$order_sn]);
           // $has_pay=M('order_pay_history')->field('sum(pay_amount) as pay_amount_total')->where(['order_sn'=>$order_sn])->find();//已付款总额

                $data['money']=$has_pay?$has_pay[0]-$data['money']-$hasOrder['data']['list'][0]['total_deposits']:0;//已付款总额-实际发贷数量金额总额

            if($data['money']<0) $data['money']=0;
            if(M('order_knot')->field($field)->add($data)){
                $this->result=['error'=>0,'msg'=>'success'];
            }
        }else{
            array_walk($goodsList,function($goods) use(&$data){
                $data['money']+=$goods['pay_subtotal']*($goods['p_num']-$goods['knot_num'])/$goods['p_num'];//实际发贷数量
            });
            $has_pay=hasOrderPay([$order_sn]);
            //$has_pay=M('order_pay_history')->field('sum(pay_amount) as pay_amount_total')->where(['order_sn'=>$order_sn])->find();//已付款总额

                $data['money']=$has_pay?$has_pay[0]-$data['money']-$hasOrder['data']['list'][0]['total_deposits']:0;//已付款总额-实际发贷数量金额总额


            if($data['money']<0) $data['money']=0;
             unset($data['knot_no']);
             $data['check_status']=0;

            $where=[
                'order_sn'=>$data['order_sn'],
                'knot_no'=>$next['knot_no']
            ];
            if(M('order_knot')->where($where)->save($data)!==false){
                $this->result=['error'=>0,'msg'=>'success'];
            }
        }

        return $this;
    }

    /**
     * 订单数据update,审核返差额
     * @param array $orderSn_arr 订单编号数组
     * @return array
     */
    public function toCheck($where='',$data='',$field='',$action='insert'){
        $knot_where=[
            'knot_no'=> $data['knot_no']
        ];
        $orderInfo=M('order_knot')->where($knot_where)->find();
        if(!$orderInfo){
            $this->result=['error'=>1,'msg'=>'参数错误'];
            return $this;
        }
        $order_where=[
            'order_sn'=>$orderInfo['order_sn']
        ];
        $hasOrder=D('Admin/Order')->orderlist($order_where);
        if($hasOrder['error']!==0){
            $this->result=['error'=>1,'msg'=>'订单错误'];
            return $this;
        }

        $data['check_time']=date('Y-m-d H:i:s',time());
        $result=M('order_knot')->field($field)->where($where)->save($data);
        if($result===false){
            $this->result=['error'=>1,'msg'=>'fail'];
        }else{
            $this->result=['error'=>0,'msg'=>'success'];
        }

        return $this;
    }

    /**
     * 订单数据,返差额列表
     * @param array $orderSn_arr 订单编号数组
     * @return array
     */
    public function toList($where='',$field='',$limit=[0,10],$sort=''){
        $Power=D('Admin/Poweraction','Event');
        $Power->setPower(D('Admin/Powerorderknot','Event'));
        $Power->powerWhere();
        $powerWhere=$Power->where;
        if($powerWhere['user_id'][1]){
            $powerWhere_str='';
            array_walk($powerWhere['user_id'][1],function($v) use(&$powerWhere_str){
                $powerWhere_str.='"'.$v.'",';
            });
            $powerWhere_str=substr($powerWhere_str,0,-1);
            $where[]="order_sn in (select order_sn from dx_order where user_id in ($powerWhere_str))";
        }

        $page=(int)(($limit[0]-1)*$limit[1]);
        $pageSize=(int)$limit[1];
        $list=M('order_knot')
            ->field($field)
            ->where($where)
            ->limit($page,$pageSize)
            ->order($sort)
            ->select();

        $count=M('order_knot')
            ->where($where)
            ->count();
        $this->list=$list;
        $this->count=$count;
        return $list;
    }

    public function toOrderInfo($where='',$field='',$limit=[0,10]){
        $this->toList($where,$field,$limit);
        return $this;
    }





}