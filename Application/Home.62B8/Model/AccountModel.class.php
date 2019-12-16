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

use EES\System\Redis;
use Think\Model;

class AccountModel
{

        /*
     *订单列表
     *
     */
    public function orderList_admin($where='',$page='',$pageSize='',$order='',$slave=true){
        $m=new \Admin\Model\OrderModel;
        $orderList=$m->orderList($where,$page,$pageSize,$order,$slave);
        return $orderList;
    }

    /*
     *最近一次账期信息
     */
    public function lastAccountInfo($user_id=''){
        $session=session();
        if(!$user_id) $user_id=$session['userId'];
        if( isset( $session['userInfo'] ) && $session['userInfo']['user_type']==20){
            $user_p=M('user_son')->where(['user_id'=>$user_id])->find();
            $user_id=$user_p['p_id'];
        }
        $user_account=M('user_account')->where(['user_id'=>$user_id])->find();
        if(!$user_account) return ['error'=>1,'msg'=>'用户没有账期'];
        $last_account=M('user_order_account')->where(['user_account_id'=>$user_account['id']])->order('id desc')->find();
        return $last_account;
    }




}