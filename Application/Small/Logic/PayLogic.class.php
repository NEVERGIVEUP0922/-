<?php

// +----------------------------------------------------------------------
// | FileName:   OauthModel.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/10 15:51
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Small\Logic;

use Small\Logic\BaseLogic;

class PayLogic extends BaseLogic
{
    public $limit='0,10';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 客户支付
     * @param user_id:客户id
     * @param index:支付索引
     * @param amount:支付金额，100倍了
     * @param type:支付类型，1定单支付
     *
     */
    public function userPayAdd($user_id,$index,$amount,$type=1){
        if(!$user_id||!$index||!$amount) return ['error'=>-1,'msg'=>'参数错误'];
        $result=D('Small/UserPay')->userPayAdd($user_id,$index,$amount,$type);
        return $result;
    }

    /**
     * @desc 客户去支付
     *
     */
    public function toUserPay($user_id,$type,$index){
        switch ($type){
            case 1:
                return D('order','Logic')->orderPay($user_id,$index);
            default: return ['error'=>-1,'msg'=>'参数错误3'];
        }
    }

    /**
     * @desc 添加订单支付回调支付记录
     *
     */
    public function notifyOrder($user_id='',$index,$amount,$type='order_pay'){
        if(!$user_id&&$type=='order_pay'){
            $order=M('order')->field('user_id')->where(['order_sn'=>$index])->find();
            if(!$order) return ['error'=>-1,'msg'=>'定单信息错误'];
            $user_id=$order['user_id'];
        }

        $result=$this->userPayAdd($user_id,$index,$amount,$type);
        return $result;
    }


}