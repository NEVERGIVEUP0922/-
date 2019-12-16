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

use Think\Model;

abstract class BaseLogic extends Model
{

    /**
     * @desc 定单状态
     * 1-----待付款
     * 11-----待发贷
     * 12-----待收贷
     * 21-----待评价
     * 31-----退换贷
     */
    public function orderStatus($order){
        $status=[];
        if(in_array($order['order_status'],[0,1,2])&&$order['pay_status']==0){//待付款
            $status['pay_num']=1;
        }
        if(in_array((int)$order['order_status'],[0,1,2])&&(int)$order['ship_status']==0){//待发货
            $status['ship_num11']=1;
        }
        if(in_array((int)$order['order_status'],[0,1,2])&&in_array((int)$order['ship_status'],[1,2,3])){//待收货
            $status['ship_num12']=1;
        }
        if($order['order_status']==3&&$order['is_comment']==1){//待评价
            $status['comment_num']=1;
        }
        if ($order['is_retreat'] == 1) {//退贷退款
            $status['retreat_num']=1;
        }
        return $status;
    }

    /**
     * @desc 数据格式检测
     */
    public function checkFormat($data){
        switch ($data['type']){
            case 'mobile':
                return preg_match('/^1\d{10}$/',$data['value'])!==false?1:false;
            case 'password':
                return preg_match('/^\w{6,}$/',$data['value'])!==false?1:false;
        }
        return false;
    }

}