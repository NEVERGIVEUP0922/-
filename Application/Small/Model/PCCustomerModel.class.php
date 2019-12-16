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

class PCCustomerModel extends Model
{

    /**
     * @desc 获取商城用户信息
     *
     */
    public function getPCCustomer($openid,$field=''){
        $return_data=[];
        $customer=M('user','xcx_')->field($field['xcx_user']['field']?:'*')->where(['openid'=>$openid])->find();
        $return_data['user_weChat']=$customer;
        if(!$customer) return ['error'=>-1,'msg'=>'用户信息错误'];

        if(isset($field['dx_user'])){
            $return_data['user_pc']= M('user')->field($field['dx_user']['field']?:'*')->where(['id'=>$customer['user_id']])->find();
        }

        if(isset($field['dx_user_company'])){
            $return_data['user_company']= M('user_company')->field($field['dx_user_company']['field']?:'*')->where(['user_id'=>$customer['user_id']])->find();
        }

        if(isset($field['dx_user_order_address'])){
            $return_data['order_address']= M('user_order_address')->field($field['dx_user_order_address']['field']?:'*')->where(['user_id'=>$customer['user_id']])->find();
        }

        return ['error'=>1,'data'=>$return_data];
    }

}