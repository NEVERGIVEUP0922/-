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

use Think\Model;

class PCOrderModel extends Model
{

    /**
     * @desc 获取商城用户订单信息
     *
     */
    public function orderList($openid,$field=''){
        $return_data=[];
        $orderList=M('order')->field($field['dx_order']['field']?:'*')
            ->where(['user_id=(select user_id from xcx_user where openid="'.$openid.'")'])
            ->limit(($field['dx_order']['page']-1)*$field['dx_order']['pageSize'],$field['dx_order']['pageSize'])
            ->select();
        if(!$orderList) return ['error'=>-1,'msg'=>'定单信息错误'];
        $return_data['orderList']=$orderList;

        if(isset($field['dx_order_goods'])){
            $return_data['user_pc']= M('user')->field($field['dx_user']['field']?:'*')->where(['id'=>$customer['user_id']])->find();
        }

        return ['error'=>1,'data'=>$return_data];
    }

}