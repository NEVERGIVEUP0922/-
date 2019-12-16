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
namespace Small\Model;


class UserPayModel extends LinkModel
{

    protected $_link = array(
    );

    /**
     * @desc 客户支付
     * @param user_id:客户id
     * @param index:支付索引
     * @param amount:支付金额，100倍了
     * @param type:支付类型，1定单支付
     *
     */
    public function userPayAdd($user_id,$index,$amount,$type=1){
        $field='user_id,index,amount,type,name';
        switch ($type){
            case 'order_pay':
                $name='微信支付';
                break;
            default:$name='下单';$amount=(int)($amount*100)*(-1);break;
        }
        $data=[
            'user_id'=>$user_id,
            'index'=>$index,
            'amount'=>$amount,//100倍
            'type'=>$type,
            'name'=>$name,
        ];
        $result=M('user_pay_account','xcx_')->field($field)->add($data);
        if(!$result) return ['error'=>-1,'msg'=>'支付信息错误'];

        return ['error'=>0,'msg'=>'success'];
    }


}