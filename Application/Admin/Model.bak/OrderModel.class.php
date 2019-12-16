<?php

// +----------------------------------------------------------------------
// | FileName:   ProductModel.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/7 12:47
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace  Admin\Model;

use Think\Model;

class OrderModel extends BaseModel
{
    protected $tableName = 'order';
    protected $pk='id';

    /*
     * 当日订单编号生成
     *
     */
    public function createOrderSn(){
        $time=date('ymd',time());
        $num=C('ORDER_CREATE_NUM');

        $orderSn=M('order_sn');
        $delete=$orderSn->where(['order_sn'=>['lt',$time*10000000]])->delete();

        $stop=1;
        $i=1;
        while($stop){
            $i++;
            if($i>5) break;
            $arr=$temp=[];
            for($i=0;$i<$num;$i++){
                $temp[]=$time.mt_rand(1000000,9999999);
            }
            $temp=array_unique($temp);
            foreach($temp as $k=>$v){ $arr[]['order_sn']=$v; }
            $result=$orderSn->addAll($arr);
            if($result==$num)$stop=0;
        }
        if($result==$num){
            return ['error'=>0,'msg'=>$time.'----'.$num.'当日定单编号生成成功'];
        }else{
            return ['error'=>1,'msg'=>$time.'----'.$num.'当日定单编号生成失败'];
        }
    }

    /*
     * 获取一个可用的订单编号
     *
     */
    public function orderSn(){
        $time=date('ymd',time());
       $order=M('order_sn');//第一次获取
       $order->startTrans();
       $one=$order->where(['is_lock'=>0,'order_sn'=>['like',"%$time%"]])->find();
       $order->save(['order_sn'=>$one['order_sn'],'is_lock'=>1]);
       if($one&&$order){
           $order->commit();
           return ['error'=>0,'data'=>['one'=>$one['order_sn']]];
       }else{
           $order->rollback();
       }

        $result=$this->createOrderSn();//生成新的一批订单编号

        $order->startTrans();//第二次获取
        $one=$order->where(['is_lock'=>0])->find();
        $order->save(['order_sn'=>$one['order_sn'],'is_lock'=>1]);
        if($one&&$order){
            $order->commit();
            return ['error'=>0,'data'=>['one'=>$one['order_sn']]];
        }else{
            $order->rollback();
            return ['error'=>1,'msg'=>'订单编号错误'];
        }
    }

    /**
     * @desc 订单列表
     *
     */
    public function orderList($where='',$page='',$pageSize='',$order='',$slave=true,$product_where=''){
        $must_where=[];
        $method_name=MODULE_NAME;
        $key_method=strtolower($method_name);
        $session=session();
        if($key_method=='admin'){
            $userM=new UserModel();
            //业务部门
            $productPowers=$userM->departmentDataPower('order',$session['adminInfo']['department_id'],$where,'sys_uid');
            if($productPowers['error']!=0) return $productPowers;
            if(isset($productPowers['data']['must_where'])){
                $customers=$userM->adminSaleCustomerPower($productPowers['data']['must_where'],'id');
                if($customers['error']!=0) return $customers;
                $must_where['user_id']=['in',$customers['data']];
            }
            //财务部门
            $productPowers=$userM->departmentDataPower('money',$session['adminInfo']['department_id'],$where,'sys_uid');
            if($productPowers['error']!=0) return $productPowers;
            if(isset($productPowers['data']['must_where'])){
                $customers=$userM->adminSaleCustomerPower($productPowers['data']['must_where'],'id');
                if($customers['error']!=0) return $customers;
                $must_where['user_id']=['in',$customers['data']];
            }
        }else{
            if(!(isset($where['order_status'])&&(int)$where['order_status']==101)) $must_where['order_status']=['lt',100];
        }
        if($where){
            if($must_where){
                $where=[$where,$must_where];
            }
        }else if($must_where){
            $where=$must_where;
        }

        if(!$order) $order='create_at desc';
        $model = new \Home\Model\OrderModel();
        $order=$this->baseList($model,$where,$page,$pageSize,$order);
        if($order['error']!=0) return $order;

        $allTotal=$this->orderTotal($where);//统计信息
        if($allTotal['error']!=0) return $allTotal;
        $order['total']=$allTotal['data']['one'];

        if($slave){
            $orderSn_arr=$customerId_arr=[];
            foreach($order['data']['list'] as $k=>$v){
                $orderSn_arr[]=$v['order_sn'];
                $customerId_arr[]=$v['user_id'];
            }

            $goods=$this->orderGoodsList($orderSn_arr,$product_where);
            $productId_arr=[];
            if($goods['error']==0){
                foreach($goods['data']['list'] as $k=>$v){
                    foreach($v as $k2=>$v2){
                        $productId_arr[]=$v2['p_id'];
                    }
                }
                $productList=$this->baseListType(M('product'),$productId_arr,'id',['id','cover_image']);//产品信息
                foreach($goods['data']['list'] as $k=>$v){
                    foreach($v as $k2=>$v2){
                        $goods['data']['list'][$k][$k2]['cover_image']=$productList['data']['list'][$v2['p_id']]['cover_image'];
                    }
                }
            }

            $listCompany=$this->baseListType(M('user_company'),$customerId_arr,'user_id');

            $payImgList=(new OrderpayhistoryModel)->payImgList(['order_sn'=>['in',$orderSn_arr]]);//水单
            $orderDetail=$this->baseListType(M('order_detail'),$orderSn_arr,'order_sn');//订单地址
            $customerList=$this->baseListType(M('user'),$customerId_arr,'id');//用户
            $adminList_arr=[];
            if($customerList['error']==0){
                foreach($customerList['data']['list'] as $k=>$v){
                    $adminList_arr[]=$v['sys_uid'];
                }
            }
            $adminList=$this->baseListType(M('user','sys_'),$adminList_arr,'uid');//业务员
            $payImg=[];
            if($payImgList['error']==0){
                foreach($payImgList['data']['list'] as $k=>$v) {
                    $payImg[$v['order_sn']][] = $v;
                }
            }

            if($goods['error']!=0) return $goods;
            $goods_orderSn=$goods['data']['list'];

            foreach($order['data']['list'] as $k=>$v){
               $order['data']['list'][$k]['goodsList']=$goods_orderSn[$v['order_sn']];
               if($payImg) $order['data']['list'][$k]['payImg']=$payImg[$v['order_sn']];
               if($orderDetail['error']==0) $order['data']['list'][$k]['orderDetail']=$orderDetail['data']['list'][$v['order_sn']];
               if($customerList['error']==0){
                   $uid=$customerList['data']['list'][$v['user_id']]['sys_uid'];
                   $order['data']['list'][$k]['sale']=$adminList['data']['list'][$uid]['fullname'];
                   $order['data']['list'][$k]['customerName']=$customerList['data']['list'][$v['user_id']]['user_name'];
                   $order['data']['list'][$k]['company']=$listCompany['data']['list'][$v['user_id']];
                   $order['data']['list'][$k]['hyInfo'] = M('order_sync_hy')->where(['order_no'=>$v['order_sn']])->select();
                   $order['data']['list'][$k]['isPart'] = $order['data']['list'][$k]['hyInfo'][0]['is_part'] > 0 ? 1:0;
                   $order['data']['list'][$k]['orderKdList'] =M('order_sync_hy')->where(['order_no'=>$v['order_sn'], 'is_kd'=>1])->select();
               }
            }
        }
        return $order;
    }


    /**
     * @desc 订单统计信息
     *
     */
    public function orderTotal($where){
        $list=M('order')->field('sum(total) as allTotal,sum(already_paid) as allPaid')->where($where)->find();
        if(!$list) return ['error'=>1,'msg'=>'统计信息错误'];
        return ['error'=>0,'data'=>['one'=>$list]];
    }

    /**
     * @desc 产品销售信息统计
     *
     */
    public function productSaleCountInfo($where){
        $goods=M('order_goods')->field('sum(p_num) as allNum,sum(pay_subtotal) as paidTotal')->where($where)->find();
        if(!$goods) return ['error'=>1,'msg'=>'产品信息统计错误'];
        else return ['error'=>0,'data'=>['one'=>$goods]];
    }

    /*
     * 订单商品列表
     *
     */
    public function orderGoodsList($orderSn_arr,$product_where=''){
        $goods_where=$product_where?['order_sn'=>['in',$orderSn_arr],$product_where]:['order_sn'=>['in',$orderSn_arr]];
        $goods=M('order_goods')->where($goods_where)->select();
        if(!$goods) return ['error'=>1,'msg'=>'商品信息错误'];
        $goods_orderSn=$retreatGoodsWhere=[];

        $retreatGoodsList=$this->orderGoodsRetreatList(['order_sn'=>['in',$orderSn_arr]],'','','create_by asc');//退货退款商品
        if($retreatGoodsList['error']==0){
            $retreatGoodsData=$retreatGoodsList['data']['list'];
            $reSn_arr=$reSn_type=$fitemno=[];
            foreach($retreatGoodsData as $k=>$v){
                $reSn_arr[]=$v['re_sn'];
                $fitemno[]=$v['fitemno'];
            }
            $reSnList=$this->baseListType(M('order_retreat'),$reSn_arr,'re_sn');
            if($reSnList['error']==0){
                $reSn_data=$reSnList['data']['list'];

                foreach($retreatGoodsData as $k=>$v){
                    if(is_array($reSn_data[$v['re_sn']])) $v=array_merge($v,$reSn_data[$v['re_sn']]);
                    $reSn_type[$v['order_sn']][$v['p_id']][]=$v;
                }
            }
        }

        $fitemno=[];
        foreach($goods as $k=>$v){
            $v['retreat']=$reSn_type[$v['order_sn']][$v['p_id']];
            $goods_orderSn[$v['order_sn']][]=$v;
            $fitemno[]=$v['fitemno'];
        }
        $fitemno=array_unique($fitemno);
        $fitemnoList=$this->baseListType(M('product','erp_'),$fitemno,'FItemNo');
        if($fitemnoList['error']==0){//库存
            foreach($goods_orderSn as $k=>$v){
                foreach($v as $k2=>$v2){
                    $goods_orderSn[$k][$k2]['store']=$fitemnoList['data']['list'][$v2['fitemno']]['store'];
                }
            }
        }

        return ['error'=>0,'data'=>['list'=>$goods_orderSn]];
    }

    /**
     * 订单商品退货列表
     * 一个订单里的一个商品可以多次退货，数量不同
     *
     */
    public function orderGoodsRetreatList($where='',$page='',$pageSize='',$order='')
    {
        $bargain = M('order_retreat_goods');
        $list = $this->baseList($bargain, $where, $page, $pageSize,$order);
        return $list;
    }

    /**
     *
     * @desc 更改订单状态
     *
     */
    public function changeOrderStatus($where,$order_status){
        $result=M('order')->where($where)->save(['order_status'=>$order_status]);
        if($result===false) return ['error'=>1,'msg'=>'faild'];
        else return ['error'=>0,'msg'=>'success'];
    }

    /**
     * @desc 发票添加,编辑
     *
    $request=[
    'action'=>'edit',
    'orderSn_arr'=>[
        1802011023709,
        1801311170317,
    ],
    'user_id'=>6,
    'user_invoice_id'=>8
    ];
     */
    public function invoiceAction($request){
        $userList=(new CustomerModel())->companyAccountAll($request['user_id']);
        if($userList['error']!=0) return $userList;

        $where=[
            'pay_status'=>2,
            'is_invoice'=>1,
            'user_invoice_id'=>0,
            'user_id'=>['in',$userList['data']],
        ];
        $where=[$where,'order_sn'=>['in',$request['orderSn_arr']]];
        $count=M('order')->where($where)->select();

        if(!$count||count($count)!=count($request['orderSn_arr'])){
            return ['error'=>1,'msg'=>'订单信息错误'];
        }
        $one=M('user_pay_history')->where(['order_sn'=>['in',$request['orderSn_arr']],'type'=>2,'account_pay_id'=>0])->find();
        if($one) return ['error'=>1,'msg'=>$one['order_sn'].'----账期未还，不能开票'];

        $countWhere=[
            'order_sn'=>['in',$request['orderSn_arr']],
            [
                [
                    'type'=>2,
                    'account_pay_id'=>['neq',0],
                ],
                'type'=>['neq',2],
                '_logic'=>'or',
            ]
        ];
        $pay_total=M('order_pay_history')->field('sum(pay_amount) as pay_total')->where($countWhere)->find();
        $request['user_invoice']['invoice_price']=$pay_total['pay_amount'];

        $request['user_invoice']['invoice_tax']=17;
        $request['user_invoice']['user_id']=$request['user_id'];

        $m=M('user_invoice');
        $m->startTrans();
        if($request['action']=='add'){
            $result=$m->field(['implment_man,implment_status'],true)->add($request['user_invoice']);
            $result2=M('order')->where(['order_sn'=>['in',$request['orderSn_arr']]])->save(['user_invoice_id'=>$result]);
            if($result&&$result2!==false){
                $m->commit();
                return ['error'=>0,'msg'=>'发票信息生成成功'];
            }else{
                $m->rollback();
                return ['error'=>1,'msg'=>'发票信息生成失败'];
            }
        }else if($request['action']=='edit'){
            $one=M('user_invoice')->where(['id'=>$request['user_invoice_id'],'implement_status'=>0])->find();
            if(!$one) return ['error'=>1,'msg'=>'发票信息错误'];

            $result2=M('order')->where(['user_invoice_id'=>$request['user_invoice_id']])->save(['user_invoice_id'=>0]);
            $result3=M('order')->where(['order_sn'=>['in',$request['orderSn_arr']]])->save(['user_invoice_id'=>$request['user_invoice_id']]);
            $result=$m->field(['implment_man,implment_status'],true)->save($request['user_invoice']);
            if($result&&$result2!==false&&$result3!==false){
                $m->commit();
                return ['error'=>0,'msg'=>'发票信息更新成功'];
            }else{
                $m->rollback();
                return ['error'=>1,'msg'=>'发票信息更新失败'];
            }
        }
    }

    /**
     * @desc 发票列表
     */
    public function invoiceList($where='',$page='',$pageSize='',$order='',$field='',$is_field=false,$userId=''){
        $must_where=[];
        $method_name=MODULE_NAME;
        $key_method=strtolower($method_name);
        $session=session();
        if($key_method=='admin'){
            $userM=new UserModel();
            //财务部门
            $productPowers=$userM->departmentDataPower('money',$session['adminInfo']['department_id'],$where,'sys_uid');
            if($productPowers['error']!=0) return $productPowers;
            if(isset($productPowers['data']['must_where'])){
                $customers=$userM->adminSaleCustomerPower($productPowers['data']['must_where'],'id');
                if($customers['error']!=0) return $customers;
                $must_where['user_id']=['in',$customers['data']];
            }
        }
        if($where){
            if($must_where){
                $where=[$where,$must_where];
            }
        }else if($must_where){
            $where=$must_where;
        }

        $list=$this->baseList(M('user_invoice'),$where,$page,$pageSize,$order,$field,$is_field);
        if($list['error']==0){
            $invoiceId_arr=[];
            foreach($list['data']['list'] as $k=>$v){
                $invoiceId_arr[]=$v['id'];
            }
            $orderWhere=['user_invoice_id'=>['in',$invoiceId_arr]];
            if($userId){
                $orderWhere['user_id']=$userId;
            }
            $order=M('order')->where($orderWhere)->select();
            if($order){
                $order_arr=[];
                foreach($order as $k=>$v){
                    $order_arr[$v['user_invoice_id']][]=$v;
                }
                foreach($list['data']['list'] as $k=>$v){
                    if($order_arr[$v['id']]) $list['data']['list'][$k]['orderList']=$order_arr[$v['id']];
                    else unset($list['data']['list'][$k]);
                }
                $list['data']['list']=array_values($list['data']['list']);
            }
        }
        return $list;
    }

    /**
     * @desc 发票处理
     */
    public function invoiceImplement($request){
        $one=M('user_invoice')->where(['id'=>$request['id'],'implment_id'=>0])->find();
        if(!$one) return ['error'=>1,'msg'=>'发票信息错误'];

        $data=[
            'implment_man'=>session('adminId'),
            'implment_status'=>1,
        ];

        $result=M('user_invoice')->where(['id'=>$request['id']])->save($data);

        if($result===false) return ['error'=>1,'msg'=>'处理失败'];
        else  return ['error'=>0,'msg'=>'处理成功'];
    }

    /**
     * @desc 开票人的开票抬头
     */
    public function userInvoiceTitle($where){
        $list=$this->baseList(M('user_order_invoice'),$where,'','','invoice_status DESC');
        return $list;
    }





}