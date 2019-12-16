<?php

// +----------------------------------------------------------------------
// | FileName:   ProductController.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2018/5/09 10:00
// +----------------------------------------------------------------------
// | Author: kelly <466395102@qq.com>
// +----------------------------------------------------------------------
namespace  Admin\Event;



class OrderEvent
{
    /**
     * @定单的可换erp型号列表
     * @param string $order_sn 订单编号
     * @return array  数据列表
     *
     */
    public function orderFitemnoList($order_sn){
        $Table=new \Admin\Event\ActionEvent();
        $where="p_sign in (select p_name from dx_order_goods where order_sn = $order_sn)";
        $Table->addObTable((new \Admin\Model\ProductfitemnoModel()),$where,'p_sign,fitemno',['index_arr','p_sign']);
        $Table->toLists()->toTypes();
        return $Table->lists;
    }

    /**
     * @定单是否可换fitemno列表
     * @param string $orderSn_array 订单编号
     * @return array  数据列表
     *
     */
    public function ordersInfo($orderSn_arr,$table_arr=['dx_product_ftimeno'],$table_arr_field=[]){
        $Table=new \Admin\Event\ActionEvent();
        if(in_array('dx_product_ftimeno',$table_arr)){
            $where=[
                'og.order_sn'=>['in',$orderSn_arr]
            ];
            $Table->addObTable((new \Admin\Model\ProductfitemnoModel()),$where,'og.order_sn,pf.p_sign,pf.fitemno',['fitemnoIsChange','order_sn']);
            $Table->addObTable((new \Admin\Model\OrdersyncModel()),['order_sn'=>['in',$orderSn_arr]],'order_sn,sync_status',['index_arr','order_sn']);
            $Table->addObTable((new \Admin\Model\OrderknotModel()),['order_sn'=>['in',$orderSn_arr]],'knot_no,order_sn,check_status',['index_arr','order_sn']);
            $Table->toOrdersInfo()->toOrdersType(false);
        }
        return $Table->lists;
    }

    /**
     * @结单申请
     * @param string $orderSn_array 订单编号
     * @return array  数据列表
     *
     */
    public function updateOrder($list,$where=''){
        $Table=new \Admin\Event\ActionEvent();
        $field='order_sn,customer_account_type,customer_account,notify_mobile,account_name,money';
        $Table->addObTableAction((new \Admin\Model\OrderknotModel()),$where,$list,$field,'insert');
        $Table->toUpdates();
        return $Table->results;
    }

    /**
     * @返差额审核
     * @param string $orderSn_array 订单编号
     * @return array  数据列表
     *
     */
    public function orderCheck($list,$where=''){
        $Table=new \Admin\Event\ActionEvent();
        $field='check_status,check_time,sys_uid';
        $where=[
            'knot_no'=>$list['knot_no'],
            'check_status'=>0,
        ];
        $list['sys_uid']=session('adminId');
        $Table->addObTableAction((new \Admin\Model\OrderknotModel()),$where,$list,$field);
        $Table->toUpdates('toCheck');
        return $Table->results;
    }

    /**
     * @返差额审核
     * @param string $orderSn_array 订单编号
     * @return array  数据列表
     *
     */
    public function accountantCheckKnotOrder($list,$where=''){
        $Table=new \Admin\Event\ActionEvent();
        $field='check_status,accountant_check_time,accountant';
        $where=[
            'knot_no'=>$list['knot_no'],
            'check_status'=>1,
        ];
        $list['accountant']=session('adminId');
        $list['check_status']=20;
        $Table->addObTableAction((new \Admin\Model\OrderknotModel()),$where,$list,$field);
        $Table->toUpdates('toCheck');
        return $Table->results;
    }

    /**
     * @结单列表
     * @param string $orderSn_array 订单编号
     * @return array  数据列表
     *
     */
    public function knotOrderList($where='',$page='',$pageSize=''){
        $Table=new \Admin\Event\ActionEvent();
        $field='*';
        $Table->addObTable((new \Admin\Model\OrderknotModel()),$where,$field,'',[$page,$pageSize],'knot_no desc');
        $Table->toLists();
        $list=$Table->lists[0];
        $orderSn_arr=$sysUid_arr=$accountants_arr=[];
        if($list){
            foreach($list as $k=>$v){
                $orderSn_arr[]=$v['order_sn'];
                $sysUid_arr[]=$v['sys_uid'];
                $sysUid_arr[]=$v['accountant'];
            }
            $orderSn_arr=array_unique($orderSn_arr);
            $sysUid_arr=array_unique($sysUid_arr);

            $Table2=new \Admin\Event\ActionEvent();
            $Table2->addObTable((new \Admin\Model\OrderModel()),['o.order_sn'=>['in',$orderSn_arr]],'u.sys_uid,o.pay_type,o.order_sn,o.user_id,u.user_name,u.fcustjc,su.fullname',['index_arr','order_sn']);
            $Table2->addObTable((new \Admin\Model\UserModel()),['uid'=>['in',$sysUid_arr]],'uid,fullname',['index_arr','uid']);
            $Table2->addObTable((new \Admin\Model\OrderpayhistoryModel()),['order_sn'=>['in',$orderSn_arr]],'order_sn,id,type,pay_name,pay_amount',['index_arr','order_sn']);
            $Table2->toOrdersInfo()->toOrdersType();
            $list2=$Table2->lists;

            foreach($Table->lists[0] as $k=>$v){
                $payName='';
                if(isset($list2[2][$v['order_sn']])){
                    foreach($list2[2][$v['order_sn']] as $k2=>$v2){
                        $payName.='['.$v2['pay_name'].":$v2[pay_amount]".']';
                    }
                }
                $temp=[
                    'customerName'=>$list2[0][$v['order_sn']]['fcustjc'],
                    'pay_type'=>$list2[0][$v['order_sn']]['pay_type'],
                    'pay_name'=>$list2[2][$v['order_sn']][0]['pay_name'],
                    'pay_name'=>$payName,
                    'saleName'=>$list2[1][$list2[0][$v['order_sn']]['sys_uid']]['fullname'],
                    'saleCheckName'=>$list2[1][$v['sys_uid']]['fullname'],
                    'accountantCheckName'=>$list2[1][$v['accountant']]['fullname'],
                ];
                $Table->lists[0][$k]=array_merge($v,$temp);
            }
        }
        return ['lists'=>$Table->lists,'counts'=>$Table->lists_count];
    }


}