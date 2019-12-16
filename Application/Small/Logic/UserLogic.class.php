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

class UserLogic extends BaseLogic
{
    public $limit='0,10';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 用户评论
     *
     */
    public function userComment($request){
        $request['comment_type']=$request['comment_type']?:'order';
        $list=[];
        switch ($request['comment_type']){
            case 'order':
                $list=D('Small/Order','Logic')->orderComment($request);
                return $list;
        }
        return ['error'=>-1,'msg'=>'参数错误'];
    }

    /**
     * @desc 发票列表
     *
     */
    public function userInvoiceList($request){
        $field='id,invoice_header,invoice_owner,company_area_code,company_address,company_tax_code,company_bank_name,company_bank_acount,company_phone,address,area_code,mobile,invoice_type,invoice_price';
        $list=M('user_invoice')->field($field)->where(['user_id'=>$request['user_id'],'implment_status'=>1])->select();
        if(!$list) return ['error'=>-400,'data'=>['list'=>$list]];

        $invoiceId_arr=[];
        foreach($list as $k=>$v){
            $invoiceId_arr[]=$v['id'];
        }

        $orderField='id,order_sn,is_comment,is_retreat,total,ship_status,pay_status,order_status,pay_type,ship_type,delivery_price,knot,is_retreat,user_invoice_id,create_at';
        $orderList=D('Small/Order','Logic')->orderList(['user_invoice_id'=>['in',$invoiceId_arr]],'',$orderField);

        if($orderList['error']<0) return ['error'=>-1,'msg'=>'定单信息错误'];
        foreach ($orderList['data']['list'] as &$order_v){
            $has_money=hasOrderPay([$order_v['order_sn']]);
            $order_v['order_has_pay']=$has_money[0];
        }
        $orderList_index=[];
        foreach($orderList['data']['list'] as $k=>$v){
            $orderList_index[$v['user_invoice_id']][]=$v;
        }

        foreach($list as $k=>$v){
            $list[$k]['invoiceOrderList']=$orderList_index[$v['id']];
        }

        return ['error'=>0,'data'=>['list'=>$list]];
    }

    /**
     * @desc 发票抬头
     *
     */
    public function userInvoiceHeader($request){
        $request['invoice_id']=$request['invoiceId'];
        if(in_array($request['action'],['update','add','delete'])){//编辑
            $result=$this->userInvoiceHeaderActionCheck($request);
            if($result['error']<0) return $result;
            $m=M('user_order_invoice');
            $field_update='user_id,invoice_status,invoice_header,invoice_owner,company_area_code,company_address,company_tax_code,company_bank_name,company_bank_acount,company_phone,address,mobile,area_code,create_time,update_time';
            if($request['action']=='update'){
                if($request['invoice_default']==1){//设置为默认发票抬头
                    $field_update='invoice_status';
                    $request['invoice_status']=1;
                }
                $result=M('user_order_invoice')->field($field_update)->where(['user_id'=>$request['user_id'],'id'=>['neq',$request['invoice_id']]])->save(['invoice_status'=>0]);
                $result=M('user_order_invoice')->field($field_update)->where(['id'=>$request['invoice_id']])->save($request);
            }else if($request['action']=='add'){
                $field_update.=',id';
                if($request['invoice_status']==1){
                    $result=M('user_order_invoice')->field($field_update)->where(['user_id'=>$request['user_id']])->save(['invoice_status'=>0]);
                }
                $result=M('user_order_invoice')->field($field_update)->add($request);
            }else if($request['action']=='delete'){
                $result=M('user_order_invoice')->where(['id'=>$request['invoice_id'],'user_id'=>$request['user_id']])->delete();
            }
            if(!$result) $result=['error'=>-1,'msg'=>'failed'];

            $result=['error'=>0,'msg'=>'success'];
        }else{
            $result=$this->userInvoiceHeaderList($request);
        }
        return $result;
    }

    /**
     * @desc 发票抬头列表
     *
     */
    public function userInvoiceHeaderList($request){
        $where=['user_id'=>$request['user_id']];
        if(isset($request['invoiceId'])&&$request['invoiceId']) $where['id']=$request['invoiceId'];
        $list=M('user_order_invoice')->where($where)->select();
        if(!$list) return ['error'=>-400,'msg'=>'没有发票抬头数据','data'=>[]];
        return ['error'=>0,'data'=>['list'=>$list]];
    }

    /**
     * @desc 发票抬头编辑
     *
     */
    public function userInvoiceHeaderActionCheck($request){
        switch($request['action']){
            case 'update':
                $oneInvoice=M('user_order_invoice')->field('id')->where(['user_id'=>$request['user_id'],'id'=>$request['invoiceId']])->find();
                if(!$oneInvoice) return ['error'=>-1,'msg'=>'发票信息错误'];
                break;
            case 'add':
                $count=M('user_order_invoice')->where(['user_id'=>$request['user_id']])->count();
                if($count>=5) return ['error'=>-1,'msg'=>'发票抬头已经超过5个'];
                break;
        }
        return ['error'=>0,'msg'=>'pass'];
    }

    /**
     * @desc 用户分享二维码信息，记录
     *
     */
    public function userQRcodeShareSave($request){
        //解析场景码
        $scene_info=D('Small/XCXAPI','Logic')->xcxQRcodeScenePare($request['scene']);
        if($scene_info['error']<0) return $scene_info;

        //保存扫码记录
        $shareSaveData=[
            'share_user_id'=>$scene_info['data']['userId'],//谁分享的
            'share_detail_index'=>$scene_info['data']['pId'],//详情id
            'scan_user_id'=>$request['xcx_user_id'],//谁扫的
            'share_type'=>$scene_info['data']['share_type'],//分享类型
        ];

       switch ($scene_info['data']['share_type']){
           case '01'://产品分享
               return D('Small/Product','Logic')->shareSave($shareSaveData);
               break;
       }

       return ['error'=>-1,'msg'=>'分享保存失败'];
    }


}