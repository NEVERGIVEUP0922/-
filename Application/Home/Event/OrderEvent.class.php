<?php
// +----------------------------------------------------------------------
// | FileName:   OrderEvent.class.php
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date:  2018-02-02 10:43
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Home\Event;

use Think\Log;

class OrderEvent
{
    /**
     * 订单查询物流 若已签收 则记录时间 延后时间后自动收货
     * 延后时间: 快递 签收时间延后5天
     * 延后时间: 物流 签收时间延后1天
     *
     */
    public function kdStateComplateLog( $order_no='', $kd_num='', $sign_time='' )
    {
        if( empty( $order_no ) || empty($kd_num) || empty($sign_time) ){
            return false;
        }
        //检查 数据
        $syncHy = M('order_sync_hy')->where(['order_no'=>$order_no, 'hy_num'=>$kd_num])->find();
        if( !$syncHy ){
            return false;
        }else{
            if( $syncHy['is_recive'] ){
                return false;
            }
        }
        //查询订单状态
        $orderInfo = M('order')->where(['order_sn'=>$order_no])->find();
        //订单已完成 || 订单已支付已全部收货 ||  分批出货时 其中的快递已收货的
        if( (int)$orderInfo['order_status'] === 3 || ( (int)$orderInfo['pay_status'] === 2 || (int)$orderInfo['ship_status'] === 4 )
        ){
            return false;
        }
        $isEx = M('order_kd_auto')->where(['order_no'=>$order_no, 'kd_num'=>$kd_num])->find();
        if( $isEx ){
            return false;
        }else{
            $add = [
                'order_no'=>$order_no,
                'kd_num'=>$kd_num,
                'partid'=>$this->getMaxLindId( $order_no ),
                'sign_time'=>date('Y-m-d',strtotime($sign_time)),
            ];
            $res = M('order_kd_auto')->add($add);
            if( $res === false ){
                return false;
            }else{
                return true;
            }
        }

    }

    protected function getMaxLindId( $order_sn )
    {
        $id = M('order_kd_auto')->where(['order_no'=>$order_sn])->count();
        return $id+1;
    }
}