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

class ErpModel
{

    /**
     *
     * @desc 可发货订单写入等待同步到erp的表
     * dx_order_sync
     *
     */
    public function orderToErp($order_sn){
        $orderSync = M('order_sync');
        if(!($orderSync->where(['order_sn'=>$order_sn])->find())){
            $res = $orderSync->add([ 'order_sn'=>$order_sn ]);
            if($res === false ) return ['error'=>1,'msg'=>$order_sn.'----订单信息同步写入数据库失败'];
        }
        $redis = Redis::getInstance();
        $rs = $redis->sAdd( 'shopOrderSyncList', $order_sn );
        if($rs === false ) return ['error'=>0,'msg'=>$order_sn.'----订单信息带同步失败!请检查缓存'];
        else return ['error'=>0,'msg'=>$order_sn.'----订单信息带同步成功'];
    }

    /**
     *
     * @desc erp发货shop账期信息更新
     *
     */
    public function orderAccountUpdate($order_sn){
        $list=(new \Home\Model\AccountModel())->orderList_admin(['order_sn'=>$order_sn]);
        if($list['error']!=0) return $list;
        $order=$list['data']['list'][0];
        $lastAccount=(new \Home\Model\AccountModel())->lastAccountInfo($order['user_id']);//最近一次账期
        if(isset( $lastAccount['error'] ) && $lastAccount['error']!=0) return $lastAccount;

        $accountPay=M('order_pay_history')->where(['order_sn'=>$order['order_sn'],'type'=>2])->find(); //支付历史
        if(!$accountPay) return ['error'=>1,'msg'=>'支付历史信息错误'];

        $payHistoryData=[ //dx_order_pay_history表数据
            'user_order_account_id_true'=>$lastAccount['id'],
            'id'=>$accountPay['id'],
        ];
        $accountData=[  //dx_user_order_account表数据
            'quota_used_true'=>$accountPay['pay_amount']+$lastAccount['quota_used_true'],
            'id'=>$lastAccount['id']
        ];

        $m=M('order_pay_history');
        $m->startTrans();
        $result=$m->save($payHistoryData);
        $result2=M('user_order_account')->save($accountData);
        if(!$result||!$result2){
            $m->rollback();
            return ['error'=>1,'msg'=>'faild'];
        }else{
            $m->commit();
            return ['error'=>0,'msg'=>'success'];
        }

    }




}