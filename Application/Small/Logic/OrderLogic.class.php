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
use EES\System\Redis;
class OrderLogic extends BaseLogic
{
    public $limit='0,10';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 定单列表
     *
     */
    public function orderList($where=[],$limit='',$field='',$order='',$relation=false,$relation_where=[]){
        if(!$field) $field='*';
//        if(!$limit) $limit=$this->limit;

        $where=[$where,'order_status'=>['in',[0,1,2,3]]];

        $m=D('order');
        $m->where($where)->field($field);
        if($order) $m->order($order);
        if($relation) $m->$relation($relation_where)->relation(true);
        $list=$m->limit($limit)->select();

        if(!$list) return ['error'=>-400,'msg'=>'没有数据'];
        if($relation=='orderPay') return ['error'=>0,'msg'=>'success','data'=>['list'=>$list]];//定单支付信息

        $pId=[];
        $k=$v=$k2=$v2=[];
        $kdSn_arr=[];
        foreach($list as $k=>$v){
            foreach($v['order_goods'] as $k2=>$v2){
                $pId[]=$v2['p_id'];
            }
            foreach($v['order_sync_hy'] as $k3=>$v3){
                if($v3['hy_num']) $kdSn_arr[]=$v3['hy_num'];
            }
        }
        $pId=array_unique($pId);
        $productList=D('Small/Product','Logic')->productList(['id'=>['in',$pId]],'','id,package','','orderDetail');
        $k=$v=[];
        $pId_img=[];

        if(in_array($relation,['retreatDetail','orderDetail'])){//定单详情
            //获取快递信息
            $KDList=D('Small/KD','Logic')->KDList($kdSn_arr);
        }

        if($productList['error']>=0){
            foreach($productList['data']['list'] as $k=>$v){
                $pId_img[$v['id']]=$v;
            }
            $k=$v=$k2=$v2=[];
            foreach($list as $k=>$v){
                $list[$k]['order_user']='';//是否是主帐号定单
                foreach($v['order_goods'] as $k2=>&$v2){
                    $list[$k]['order_goods'][$k2]['img']=$pId_img[$v2['p_id']]['img']?:'';
                }
                if(in_array($relation,['retreatDetail','orderDetail'])){//定单详情
                    if($v['order_sync_hy']){//贷运详情
                        foreach($v['order_sync_hy'] as $kk=>$vv){
                            if($vv['hy_num']){
                                $list[$k]['order_sync_hy'][$kk]['hy']=$KDList['data'][$vv['hy_num']]['Traces'];
                            }
                        }
                    }
                }
                $list[$k]['display_kont']=$this->displayKnot($v);//是否能结单
                if(is_null($v['check_status'])) $list[$k]['check_status']=10000;
//                if($relation=='retreatDetail'){//已退款金额
                    $one_has_retreat_money=0;
                    $order_retreat_goods_arr=[];
                    foreach($v['order_retreat_goods'] as $kkkk=>$vvvv){
                        $order_retreat_goods_arr[$vvvv['re_sn']][]=$vvvv;
                    }
                    foreach($v['order_retreat'] as $kkk=>$vvv){
                        if($vvv['handle_status']==6){
                            $one_has_retreat_money+=$vvv['retreat_money'];
                        }
                        $list[$k]['order_retreat'][$kkk]['item']=$order_retreat_goods_arr[$vvv['re_sn']];
                    }
                    unset($list[$k]['order_retreat_goods']);
                    $list[$k]['has_retreat_money_total']=$one_has_retreat_money?:0;
//                }
                $has_money=hasOrderPay([$v['order_sn']]);
                $list[$k]['hymoney']=$has_money[0];//已付金额
                //$list[$k]['hymoney']=$v['order_pay_history']['hymoney'];//已付金额
            }
        }

        return ['error'=>0,'msg'=>'success','data'=>['list'=>$list]];
    }

    /**
     * @desc 定单是否能结单
     *
     */
    public function displayKnot($v){
        $hyres=M('order_sync_hy')->where(['order_no'=>$v['order_sn'],'is_recive'=>0])->select();
        if($hyres){
            $v['display_knot']=0;
        }else{
            if($v['pay_type']==1&&$v['pay_status']==2&&$v['ship_status']==0&&$v['total']!=0){
                $v['display_knot']=1;
            }elseif ($v['ship_status']==3&&$v['total']!=0){
                $v['display_knot']=1;
            }elseif ($v['ship_status']==0&&$v['total']==0){
                $v['display_knot']=1;
            }elseif($v['ship_status']==0&&($v['pay_status']==1||$v['pay_status']==2)){
                $v['display_knot']=1;
            }else{
                $v['display_knot']=0;
            }
        }
        return $v['display_knot'];
    }

    /**
     * @desc 下单
     *
     */
    public function createOrder($productList,$user_id,$request){
        if(!$productList||!$user_id) return ['error'=>-1,'msg'=>'参数错误'];

        $request['pay_type']=(int)$request['pay_type']?:1;//支付方式
        if(!in_array((int)$request['pay_type'],[1,2,3,4,5,6])) return ['error'=>-1,'msg'=>'支付方式错误'];

        //获取订单编号
        $orderSn=$this->orderSn();
        if($orderSn['error']!==0) return ['error'=>-1,'msg'=>'定单编号错误'];
        $order_sn=$orderSn['data']['one'];

        $request['order_sn']=$order_sn;

        //收贷信息
        $orderAddress=$this->orderAddress(['user_id'=>$user_id,'id'=>$request['addressId']],'',$request);
        if($orderAddress['error']<0) return $orderAddress;
        $orderAddress_data=$orderAddress['data'];

        //配送信息
        $deliveryInfo=$this->delivery(['id'=>$request['deliveryId']]);
        if($deliveryInfo['error']<0) return $deliveryInfo;
        $delivery=$deliveryInfo['data'];//一级或二级
        $delivery_level1=$deliveryInfo['one_parent']?:$delivery;//一级物流信息

        //运费
        $freiht=0;

        $is_invoice=($request['is_invoice']==1)?1:0;//1开票,0不开票
        $pay_type=$request['pay_type'];//支付方式
        $is_discount=$this->discountPriceIsPass($is_invoice,$pay_type,'')?1:0;//是否执行优惠,0不执行，1执行

        //订单数据
        $orderList=$productList['list'];
        $orderList['order_sn']=$order_sn;
        $orderList['pay_type']=$pay_type;
        $orderList['ship_type']=$delivery_level1['id'];
        $orderList['ship_type_level2']=$delivery['id'];
        $orderList['is_invoice']=$is_invoice;
        $orderList['delivery_code']=$delivery['code'];
        $orderList['delivery_name']=$delivery['name'];

        $settlementOrder=$this->settlementOrder($orderList,$is_discount,$user_id);
        if($settlementOrder['error']<0) return $settlementOrder;

        $order_field='delivery_name,delivery_code,is_invoice,user_id,total,order_sn,total_origin,total_invoice,total_discount,order_status,pay_status,ship_status,pay_type,ship_type,ship_type_level2,delivery_price,total_integral,already_paid';
        $order_goods_field='order_sn,p_price_true,p_price_show,subtotal,pay_subtotal,discount_subtotal,is_discount_num,rule_info,bargain_price_id,deposits_subtotal,p_id,p_name,fitemno,p_num';
        $order_detail_field='order_sn,consignee,area_code,address,zipcode,mobile,note';

        //数据保存
        $m=M('order');
        $productM=M('product');
        $m->startTrans();
        $log_sql='';

        $result=$m->field($order_field)->add($settlementOrder['data']['order_data']);
        $log_sql.=D()->getLastSql();
        $result2=M('order_goods')->field($order_goods_field)->addAll($settlementOrder['data']['order_goods']);
        $log_sql.=D()->getLastSql();
        $result3=M('order_detail')->field($order_detail_field)->add($orderAddress['orderAddress']);
        $log_sql.=D()->getLastSql();

        $pId_arr=$pId_arr_sample =[];
        $result6=1;
        $result4=1;
        $result5=1;
        $result6_one='';
        //销量更新
        foreach($settlementOrder['data']['order_goods'] as $k=>$v){
            $result6_one=$productM->execute('update dx_product set sell_num=sell_num+'.(int)$v['p_num'].' where id='.$v['p_id'].' and sell_num='.$v['sell_num']);
            $log_sql.=D()->getLastSql();
            if(!$result6_one){
                $result6='';
            }
            if($v['subtotal']==0){//样品
                $pId_arr_sample[]=$v['p_id'];
            }else{//非样品
                $pId_arr[]=$v['p_id'];
            }
        }
        //购物车更新
        if($pId_arr) $result4=M('basket_detail')->where(['basket_id'=>$productList['list']['basket_id'],'pid'=>['in',$pId_arr]])->delete();
        $log_sql.=D()->getLastSql();
        if($pId_arr_sample) $result5=M('basket_detail_sample')->where(['basket_id'=>$productList['list']['basket_id'],'pid'=>['in',$pId_arr_sample]])->delete();
        $log_sql.=D()->getLastSql();

        if(!empty($pId_arr)){//非全部样品 :支付信息更新
            $result7=D('pay','Logic')->userPayAdd($user_id,$order_sn,$settlementOrder['data']['order_data']['total'],1);
            $log_sql.=D()->getLastSql();
        }
        if($pId_arr_sample){ //更新样品管理
           $result8=M('user_product_example')->where(['user_id'=>$request['user_id'],'pid'=>['in',$pId_arr_sample]])->save(['step'=>0]);
           $log_sql.=D()->getLastSql();
        }

        $msg='success';
        $error=0;
        if(!$result){
            $error=-1;
            $msg='定单信息错误';
        }else if(!$result2){
            $error=-2;
            $msg='商品信息错误';
        }else if(!$result3){
            $error=-3;
            $msg='收贷地址错误';
        }else if($result4===false){
            $error=-4;
            $msg='购物车信息错误';
        }else if($result5===false){
            $error=-5;
            $msg='样品信息错误';
        }else if(!$result6){
            $error=-6;
            $msg='商品信息错误2';
        }else if(isset($result7['error'])&&$result7['error']<0){
            $error=-7;
            $msg='支付信息错误';
        }else if($result8===false){
            $error=-8;
            $msg='样品信息错误';
        }

        if($error<0){
            $m->rollback();
            //日志记录
            $log_str='productList:';
            $log_str.=json_encode($productList).';user_id:';
            $log_str.=json_encode($user_id).';request:';
            $log_str.=json_encode($request);
            $log_str.=';erorr:'.$error;
            $log_str.=';msg:'.$msg;
            $log_str.=';sql:'.$log_sql;

            kelly_log($log_str,'kelly_xcx_createOrder_error','ERR');
        }else{
            $m->commit();
        }

        return ['error'=>$error,'msg'=>$msg,'data'=>['order_sn'=>$order_sn]];
    }

    /**
     * @desc 判断是否能执行优惠价
     * 支付方式（1在线支付，2账期支付，5银行转账，3快递代收，4面对面付款，6线下支付）
     * 定金支付方式（1在线支付，4面对面付款，5银行转账，6线下支付）
     */
    public function discountPriceIsPass($is_invoice,$pay_type,$deposits_pay_type){
        $pass=true;
        if($is_invoice==1){//开票
            $pass=false;
        }else{//不开票
            if( in_array($pay_type,[1,5]) || in_array($deposits_pay_type,[1,5]) ) $pass=false;
        }
        return $pass;
    }

    /**
     * @desc 订单结算
     *
     */
    public function settlementOrder($productList,$is_discount,$user_id){
        if(!$productList) return ['error'=>-1,'msg'=>'结算商品错误'];

        $total=$total_origin=$total_discount=0;
        $order_goods=[];
        $order_status=0;
        foreach($productList['basket_detail'] as $k=>$v){
            if($v['order_status']==1){//要审核的定单
                $order_status=1;
            }

            if($is_discount===0){//不优惠
                $total+=$v['subtotal'];
                $order_goods[]=[
                    'order_sn'=>$productList['order_sn'],
                    'p_price_true'=>$v['price_show'],
                    'p_price_show'=>$v['price_show'],
                    'subtotal'=>$v['subtotal'],
                    'pay_subtotal'=>$v['subtotal'],
                    'discount_subtotal'=>0,
                    'is_discount_num'=>1,//是否用了折扣线
                    'rule_info'=>$v['user_product_bargain']?json_encode($v['user_product_bargain']):'',
                    'bargain_price_id'=>$v['user_product_bargain']['id']?:0,
                    'deposits_subtotal'=>0,//定金小计
                    'p_id'=>$v['pid'],
                    'p_name'=>$v['p_sign'],
                    'fitemno'=>$v['fitemno'],
                    'p_num'=>$v['num'],
                    'sell_num'=>$v['sell_num'],
                ];
            }else{//优惠
                //小程序暂时不做优惠处理
                $total+=($v['subtotal']-$v['discount_subtotal']);
                $order_goods[]=[
                    'order_sn'=>$productList['order_sn'],
                    'p_price_true'=>$v['price_show'],
                    'p_price_show'=>$v['price_show'],
                    'subtotal'=>$v['subtotal'],
                    'pay_subtotal'=>$v['subtotal'],
                    'discount_subtotal'=>$v['discount_subtotal'],
                    'is_discount_num'=>$v['is_discount_num'],//是否用了折扣线
                    'rule_info'=>$v['user_product_bargain']?json_encode($v['user_product_bargain']):'',
                    'bargain_price_id'=>$v['user_product_bargain']['id']?:0,
                    'deposits_subtotal'=>0,//定金小计
                    'p_id'=>$v['pid'],
                    'p_name'=>$v['p_sign'],
                    'fitemno'=>$v['fitemno'],
                    'p_num'=>$v['num'],
                    'sell_num'=>$v['sell_num'],
                ];
            }
        }

        //样品
        foreach($productList['basket_detail_sample'] as $k=>$v){
            $order_goods[]=[
                'order_sn'=>$productList['order_sn'],
                'p_price_true'=>0,
                'p_price_show'=>0,
                'subtotal'=>0,
                'pay_subtotal'=>0,
                'discount_subtotal'=>0,
                'is_discount_num'=>1,//是否用了折扣线
                'rule_info'=>'',
                'bargain_price_id'=>0,
                'deposits_subtotal'=>0,//定金小计

                'p_id'=>$v['pid'],
                'p_name'=>$v['p_sign'],
                'fitemno'=>$v['fitemno'],
                'p_num'=>$v['num'],
                'sell_num'=>$v['sell_num'],
            ];
        }

        $total_origin=$total;

        $data_return=[
            'order_data'=>[
                'total'=>$total,//需实付总计
                'total_origin'=>$total_origin,//原价总计
                'total_invoice'=>$total,//开票金额
                'total_discount'=>$total_discount,//优惠总计
                'order_sn'=>$productList['order_sn'],
                'order_status'=>$order_status,
                'pay_status'=>empty($productList['basket_detail'])?2:0,
                'ship_status'=>0,
                'pay_type'=>$productList['pay_type'],
                'ship_type'=>$productList['ship_type'],
                'ship_type_level2'=>$productList['ship_type_level2'],
                'delivery_price'=>$productList['delivery_price']?:0,//运费
                'total_integral'=>0,
                'already_paid'=>0,
                'user_id'=>$user_id,

                'is_invoice'=>$productList['is_invoice'],
                'delivery_code'=>$productList['delivery_code'],
                'delivery_name'=>$productList['delivery_name'],
            ],
            'order_goods'=>$order_goods,
        ];

        return ['error'=>0,'data'=>$data_return];
    }

    /**
     * @desc 获取订单编号
     *
     */
    public function orderSn(){
        $list= D('Admin/Order')->orderSn();
        return $list;
    }

    /**
     * @desc 收贷信息
     *
     */
    public function orderAddress($where=[],$field='',$request){
        if(!$field) $field='user_id,consignee,area_code,address,zipcode,mobile';
        if(!$where) return ['error'=>-1,'msg'=>'收贷地址参数错误'];
        $one= M('user_order_address')->field($field)->where($where)->find();
        if(!$one) return ['error'=>-1,'msg'=>'收贷地址信息错误'];

        $orderAddress=[
            'order_sn'=>$request['order_sn'],
            'consignee'=>$one['consignee'],
            'area_code'=>$one['area_code'],
            'address'=>$one['address'],
            'zipcode'=>$one['zipcode'],
            'mobile'=>$one['mobile'],
            'note'=>$request['remark']?:'no',
        ];
        return ['error'=>0,'data'=>$one,'orderAddress'=>$orderAddress];
    }

    /**
     * @desc 物流信息
     *
     */
    public function delivery($where=[],$field=''){
        if(!$field) $field='id,name,is_default,code,describe,parent_id';
        if(!$where) return ['error'=>-1,'msg'=>'物流信息参数错误'];
        $one= M('delivery')->field($field)->where($where)->find();
        if(!$one) return ['error'=>-1,'msg'=>'物流信息错误'];

        if($one['parent_id']){
            $one_parent= M('delivery')->field($field)->where(['id'=>$one['parent_id']])->find();
        }

       return ['error'=>0,'data'=>$one,'one_parent'=>$one_parent?:''];
    }

    /**
     * @desc 定单支付
     *
     */
    public function orderPay($user_id,$order_sn){
        $type=1;
        $orderPayCheck=$this->orderPayCheck($order_sn,$user_id);
        if($orderPayCheck['error']<0) return $orderPayCheck;

        return $orderPayCheck;
    }

    /**
     * @desc 定单支付数据检查
     *
     */
    public function orderPayCheck($order_sn,$user_id=''){
        $where=[ 'order_sn'=>$order_sn, ];
        if($user_id) $where['user_id']=$user_id;
        $where['pay_status']=['neq',2];
        $where['order_status']=['neq',1];

        $order=$this->orderList($where,'','','id asc','orderPay',['user_pay_account'=>'user_id='.$user_id]);
        if($order['error']<0) return $order;

        $one_order=$order['data']['list'][0];
        $order_goods=$one_order['order_goods'];
        $user_pay_account=$one_order['user_pay_account'][0];

        //检测金额是否正确
        $total=$one_order['total'];
        $total_goods=0;
        foreach($order_goods as $k=>$v){
            $total_goods+=$v['pay_subtotal'];
        }

        $total=round($total,2);
        $total_goods=round($total_goods,2);
        $amount=-$user_pay_account['amount'];

//        if($total!=$total_goods||(int)($total*100)!=$amount){
//            return ['error'=>-1,'msg'=>'金额不对'];
//        }
        if($total!=$total_goods){
            return ['error'=>-1,'msg'=>'金额不对'];
        }

        $pay_amount=0;
        foreach($one_order['user_pay_account'] as $k=>$v){//计算支付金额
            $pay_amount+=$v['amount'];
        }
        //if($pay_amount>=0) return ['error'=>-1,'msg'=>'没有欠款'];

        return ['error'=>0,'msg'=>'pass','data'=>['unpaid'=>-$pay_amount/100,'one_order'=>$one_order]];
    }

    /**
     * @desc 取消定单
     *
     */
    public function cancelOrder($request){
        $cancelOrderCheck=$this->cancelOrderCheck($request);
        if($cancelOrderCheck['error']<0) return $cancelOrderCheck;

        $orderCancel=M('order')->where(['order_sn'=>$request['order_sn']])->save(['order_status'=>100]);
        if($orderCancel===false){
            return ['error'=>-1,'msg'=>'取消失败'];
        }

        return ['error'=>0,'msg'=>'取消成功'];
    }

    /**
     * @desc 取消定单数据检测
     *
     */
    public function cancelOrderCheck($request){
//        $where=[
//            'order_sn'=>$request['order_sn'],
//            'user_id'=>$request['user_id'],
//            'status'=>0
//        ];
//        $one=M('order')->field('id')->where($where)->find();
//        if(!$one) return ['error'=>-1,'msg'=>'定单信息错误'];
//        return ['error'=>0,'msg'=>'pass'];


        //订单信息是否存在 是否删除
        $where[]=array(
            'order_sn'=>$request['order_sn']
        );
//        $where[]=array(
//            'order_status'=>['IN',[0,1]],
//        );
        $orderInfo=M('order')->where($where)->find();
        if(!$orderInfo){
                return ['error'=>-1,'msg'=>'订单信息错误'];
        }else{
            if(in_array($orderInfo['order_status'],[0,1])&&($orderInfo['pay_status']==1&&$orderInfo['ship_status']==0)){
                return ['error'=>-1,'msg'=>'订单信息错误'];
            }
        }
        //同步订单信息
        $orderSync=M('order_sync')->where(['order_sn'=>$request['order_sn']])->find();
        if(!$orderSync){
            return ['error'=>0,'msg'=>'取消成功'];
        }else{
            if($orderSync['sync_status']==2){
                //直接删除
                $delRes=M('order')->where(['order_sn'=>$request['order_sn']])->save(['order_status'=>100]);
                if($delRes===false){
                    return ['error'=>-1,'msg'=>'取消失败'];
                }else{
                    return ['error'=>0,'msg'=>'取消成功'];
                }
            }elseif(in_array($orderSync['sync_status'],[0,4])){
                //不可以删除
                return ['error'=>-1,'msg'=>'暂时不可以取消，请稍后'];
            }elseif ($orderSync['sync_status']==6){
                //删除 同步erp
                M()->startTrans();
                $delRes=M('order')->where(['order_sn'=>$request['order_sn']])->save(['order_status'=>100,'delstatus'=>3]);
                if($delRes===false){
                    M()->rollback();
                    return ['error'=>-1,'msg'=>'取消失败'];
                }else{
                    $key='delOrderSyncList';

                    $res = Redis::getInstance()->sAdd($key, $request['order_sn']);
                    if($res===false){
                        M()->rollback();
                        return ['error'=>-1,'msg'=>'取消失败'];
                    }
                    M()->commit();
                    return ['error'=>0,'msg'=>'取消成功'];
                }
            }else{
                $orderSyncInfo=M('user_handle_history','sys_')->where(['index'=>$request['order_sn']])->find();
                if (!$orderSyncInfo){
                    //删除  同步erp
                    $delRes=M('order')->where(['order_sn'=>$request['order_sn']])->save(['order_status'=>100,'delstatus'=>3]);
                    if($delRes===false){
                        M()->rollback();
                        return ['error'=>-1,'msg'=>'取消失败'];
                    }else{
                        $key='delOrderSyncList';
                        $res = Redis::getInstance()->sAdd($key, $request['order_sn']);
                        if($res===false){
                            M()->rollback();
                            return ['error'=>-1,'msg'=>'取消失败'];
                        }
                        M()->commit();
                        return ['error'=>0,'msg'=>'取消成功'];
                    }
                }else{
                    if ($orderSyncInfo['sync_status']==0){
                        //不可以删除
                        return ['error'=>-1,'msg'=>'暂时不可以取消，请稍后'];
                    }else{
                        //删除  同步erp
                        $delRes=M('order')->where(['order_sn'=>$request['order_sn']])->save(['order_status'=>100,'delstatus'=>3]);
                        if($delRes===false){
                            M()->rollback();
                            return ['error'=>-1,'msg'=>'取消失败'];
                        }else{
                            $key='delOrderSyncList';
                            $res = Redis::getInstance()->sAdd($key, $request['order_sn']);
                            if($res===false){
                                M()->rollback();
                                return ['error'=>-1,'msg'=>'取消失败'];
                            }
                            M()->commit();
                            return ['error'=>0,'msg'=>'取消成功'];
                        }
                    }
                }
            }
        }
    }

    /**
     * @desc 定单评论
     *
     */
    public function orderComment($request){
        $orderCommentCheck=$this->orderCommentCheck($request);
        if($orderCommentCheck['error']<0){
            $orderCommentCheck['error']=-400;
            return $orderCommentCheck;
        }

        return D('order')->orderCommentAction($orderCommentCheck['data']['comment_arr']);
    }

    /**
     * @desc 定单评论数据检测
     *
     */
    public function orderCommentCheck($request){
        $order=M('order')->field('id')->where(['user_id'=>$request['user_id'],'order_sn'=>$request['order_sn'],'order_status'=>3])->find();
        if(!$order) return ['error'=>-1,'msg'=>'定单信息错误'];

        $must_key=['p_id','content','star'];
        $pid_arr=[];

        foreach($request['comment_arr'] as $kk=>$vv){
            foreach($must_key as $k=>$v){
                if(!isset($vv[$v])) return ['error'=>-1,'msg'=>'参数错误'];
            }
            $request['comment_arr'][$kk]['order_sn']=$request['order_sn'];
            $request['comment_arr'][$kk]['user_id']=$request['user_id'];
            $pid_arr[]=$vv['p_id'];
        }
        $product=M('order_goods')->field('id')->where(['order_sn'=>$request['order_sn'],'p_id'=>['in',$pid_arr]])->select();
        //print_r(M()->getLastSql());die($product);
        if(!(int)count($product)||(int)count($product)!==(int)count($pid_arr)) return ['error'=>-1,'msg'=>'商品信息错误'];

        return ['error'=>0,'msg'=>'pass','data'=>$request];
    }

    /**
     * @desc 确认收贷
     *
     */
    public function hyReceive($request){
        $order=M('order')->field('id')->where(['user_id'=>$request['user_id'],'order_sn'=>$request['order_sn']])->find();
        if(!$order) return ['error'=>-1,'msg'=>'定单信息错误'];

        $m=M('order_sync_hy');
        $where=['order_no'=>$request['order_sn'],'erp_th_no'=>$request['partid']];
        $one=$m->where($where)->find();
        if(!$one) return ['error'=>-1,'msg'=>'定单信息错误2'];
        M()->startTrans();
        $result=$m->where($where)->save(['is_recive'=>1,'c_recive'=>1]);
        if($result===false){
            M()->rollback();
            return ['error'=>-1,'msg'=>'field'];
        }
        $ordersave=orderStatus($request['order_sn']);
        $result1=M('order')->where(['order_sn'=>$request['order_sn']])->save($ordersave);
        if($result1===false){
            M()->rollback();
            return ['error'=>-1,'msg'=>'field'];
        }
        M()->commit();
        return ['error'=>0,'msg'=>'success'];
    }

}