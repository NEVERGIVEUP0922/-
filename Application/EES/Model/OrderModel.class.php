<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-12-29
 * Time: 17:23
 */
namespace EES\Model;

use Think\Model\RelationModel;

class OrderModel extends RelationModel
{
    protected $_link = [
    ];

    public function getAllInfo( $orderNo )
    {
        $res = M('order')->where( ['order_sn'=>$orderNo] )->find();
        if( !$res ) return false;
        $res['detail'] = M('order_detail')->where(['order_sn'=>$orderNo])->find();
        $res['goods'] = M('order_goods')->where(['order_sn'=>$orderNo])->select();
        return $res;
    }
}