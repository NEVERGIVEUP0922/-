<?php
namespace Small\Controller;
use Home\Controller\WalletController;
use Think\Controller;
use Admin\Controller\MsgController;
class OrderController extends BaseController {

    /**
     * @desc 定单列表
     *
     */
    public function orderList(){
        $request=$this->post;
        $where=$relation_where=[];
        $order='';
        $field='';
        $show_data=in_array($request['show_data'],['orderList','orderDetail','retreatDetail'])?$request['show_data']:'orderList';//显示的数据类型
        if(isset($request['order_status'])) $where['order_status']=$request['order_status'];
        if(isset($request['order_noCheck'])&&$request['order_noCheck']=='order_noCheck'){//非待审核定单
            $add_where=['order_status'=>['neq',1]];
            $where=empty($where)?$add_where:[$where,$add_where];
        }
        if(isset($request['knot'])&&$request['knot']=='knot'){
            $where['knot']=['gt',0];
        }
        if(isset($request['order_type'])&&$request['order_type']==1){
            $where['order_type']=1;
        }
        if(isset($request['order_receiveProduct'])&&$request['order_receiveProduct']=='order_receiveProduct'){//待收贷
            $add_where=[
                'order_status'=>['in',[0,1,2]],
                'ship_status'=>['in',[1,2,3]],
            ];
            $where=empty($where)?$add_where:[$where,$add_where];
        }

        if(isset($request['pay_status'])) $where['pay_status']=$request['pay_status'];
        if(isset($request['ship_status'])) $where['ship_status']=$request['ship_status'];
        if(isset($request['is_retreat'])) $where['is_retreat']=$request['is_retreat']; //退贷退款
        if(isset($request['is_comment'])) $where['is_comment']=$request['is_comment'];
        if(isset($request['order_sn'])) $where['order_sn']=$request['order_sn'];
        if(isset($request['retreat_sn'])&&$request['retreat_sn']){
            $where[]="order_sn in (select order_sn from dx_order_retreat where re_sn = $request[retreat_sn])";
            $relation_where=[
                'retreatDetail'=>'re_sn = "'.$request['retreat_sn'].'"',
            ];
        }

        if(isset($request['is_invoice'])) $where['is_invoice']=$request['is_invoice'];//是否开票
        if(isset($request['invoice_status'])){
            $where['invoice_status']=$request['invoice_status'];//是否已开票
            $where['pay_status']=2;//是否已开票
            $where['user_invoice_id']=0;
            $where[]=[
                'order_sn not in (
                    select order_sn from (
	                    select order_sn,sum((p_num-knot_num-retreat_num)*pay_subtotal/p_num) as current_total from dx_order_goods group by order_sn
                    ) as ot where ot.current_total=0
                )',
            ];
            $where[]=[
                'user_invoice_id not in (select id from dx_user_invoice where implment_status=1)'
            ];
        }

        $userInfo=$this->getUserInfo();
        $where['user_id']=$userInfo['id'];

        $page=$request['page']?:1;
        $pageSize=$request['pageSize']?:C('PAGE_PAGESIZE');
        $limit=($page-1)*$pageSize.','.$pageSize;
        $order='update_at desc';
        $list=D('Order','Logic')->orderList($where,$limit,$field,$order?:'id desc',$show_data,$relation_where?:$userInfo['id']);
        if($list['error']==0){
            foreach ($list['data']['list'] as &$v){

                if($request['show_data']=='retreatDetail'){
                    if(isset($request['retreat_sn'])&&$request['retreat_sn']){
//                        $where[]="order_sn in (select order_sn from dx_order_retreat where re_sn = $request[retreat_sn])";
//                        $relation_where=[
//                            'retreatDetail'=>'re_sn = "'.$request['retreat_sn'].'"',
//                        ];
                        //退款中 退款成功的总金额
                        $re_where['re_sn']=['neq',$request['retreat_sn']];
                        $re_where['handle_status']=['not in',[3,7]];
                        $re_where['order_sn']=$request['order_sn'];
                        $reimg_money=M('order_retreat')->field('sum(retreat_money) as retreatimg_money')->where($re_where)->find();
                        $retreatimg_money=$reimg_money['retreatimg_money']?$reimg_money['retreatimg_money']:0;
                        $hasPay=hasOrderPay([$request['order_sn']]);
                        $v['retreat_money_ok']=$hasPay[0]?$hasPay[0]-$retreatimg_money:0-$retreatimg_money;

                        //退款数量可退金额
                        $re_g_money=0;
                        if($reimg_money){
                            $re_good_money=M('order_retreat_goods')->field("p_price*p_num as re_money_ok")->where(['re_sn'=>$request['retreat_sn'],'order_sn'=>$request['order_sn']])->select();

                            foreach ($re_good_money as $reg_money){
                                $re_g_money+=$reg_money['re_money_ok'];
                            }
                        }

                        $order_Where['order_sn']=$request['order_sn'];
                        $order_re_money=M('order_goods')->field("p_price_true*(erp_num-retreat_num) as re_money_ok")->where($order_Where)->select();
                        $money_ok=$re_g_money;
                        foreach ($order_re_money as $v_money){
                            $money_ok+=$v_money['re_money_ok'];
                        }

                        $order_re_money['re_money_ok']=$money_ok>0?$money_ok:0;
                        $v['retreat_money_ok']=$money_ok>$v['retreat_money_ok']?$v['retreat_money_ok']:$money_ok;

                    }else{
                        //退款中 退款成功的总金额
                        $re_where['handle_status']=['not in',[3,7]];
                        $re_where['order_sn']=$request['order_sn'];
                        $reimg_money=M('order_retreat')->field('sum(retreat_money) as retreatimg_money')->where($re_where)->find();

                        $retreatimg_money=$reimg_money['retreatimg_money']?$reimg_money['retreatimg_money']:0;
                        $hasPay=hasOrderPay([$request['order_sn']]);
                        $v['retreat_money_ok']=$hasPay[0]?$hasPay[0]-$retreatimg_money:0-$retreatimg_money;

                        //退款数量可退金额
                        $order_Where['order_sn']=$request['order_sn'];
                        $order_re_money=M('order_goods')->field("p_price_true*(erp_num-retreat_num) as re_money_ok")->where($order_Where)->select();
                        $money_ok=0.00;
                        foreach ($order_re_money as $v_money){
                            $money_ok+=$v_money['re_money_ok'];
                        }
                        $order_re_money['re_money_ok']=$money_ok>0?$money_ok:0;
                        $v['retreat_money_ok']=$money_ok>$v['retreat_money_ok']?$v['retreat_money_ok']:$money_ok;
                    }
                }


                $hyres=M('order_sync_hy')->where(['order_no'=>$v['order_sn'],'is_recive'=>0])->select();
                if($hyres){
                    if (count($hyres)>1){
                        $v['is_receipt']=2;
                    }else{
                        $v['is_receipt']=1;
                    }
                    $v['display_kont']=0;
                }else{
                    $v['is_receipt']=0;
                    if($v['pay_type']==1&&$v['pay_status']==2&&$v['ship_status']==0&&$v['total']!=0){
                        $v['display_kont']=1;
                    }elseif ($v['ship_status']==3&&$v['total']!=0){
                        $v['display_kont']=1;
                    }elseif ($v['ship_status']==0&&$v['total']==0){
                        $v['display_kont']=1;
                    }elseif(($v['ship_status']==0&&$v['pay_status']==2)||($v['order_type']==1&&$v['deposits_pay_type']==1&&$v['pay_type']!=1&&$v['ship_status']==0&&$v['pay_status']==1)){
                        $v['display_kont']=1;
                    }elseif($v['pay_type']!=1&&$v['pay_status']==1&&$v['ship_status']==0){
                        $v['display_kont']=1;
                    }else{
                        $v['display_kont']=0;
                    }
                }
                //                $where_pay_history=[//定单实付金额
                //                    [
                //                        'type'=>['neq',2],
                //                        [
                //                            'type'=>2,
                //                            'account_pay_id'=>['neq',0]
                //                        ],
                //                        '_logic'=>'or'
                //                    ],
                //                    'order_sn'=>$v['order_sn'],
                //                ];
//                $pay=M('order_pay_history')->field('sum(pay_amount) as pay_amount_total')->where(['order_sn'=>$v['order_sn']])->find();
//                //echo M()->getLastSql();
//
//                $pay['pay_amount_total']=$pay['pay_amount_total']?$pay['pay_amount_total']:0;
//                $erpPay=M('order_sync_hy')->field('sum(fcxacount) as fcxacount_total')->where(['order_no'=>$v['order_sn']])->find();
//                //echo M()->getLastSql();
//                $erpPay['fcxacount_total']=$erpPay['fcxacount_total']?:0;
//                if($v['pay_type']==2){
//                    $pay1=M('order_pay_history')->where(['order_sn'=>$v['order_sn']])->select();
//
//                    $my=0.00;
//                    if ($pay1){
//                        foreach ($pay1 as $p1){
//                            if($p1['type']!=2){
//                                $my+=$p1['pay_amount'];
//                            }else{
//                                if ($p1['account_pay_id']!=0){
//                                    $my+=$p1['pay_amount'];
//                                }
//                            }
//                        }
//                    }
//                    if($my>$erpPay['fcxacount_total']){
//                        $v['order_has_pay']=$my;
//                    }else{
//                        $v['order_has_pay']=$erpPay['fcxacount_total'];
//                    }
//                }else{
//                    if($erpPay['fcxacount_total']>$pay['pay_amount_total']){
//                        $v['order_has_pay']=$erpPay['fcxacount_total'];
//                    }else{
//                        $v['order_has_pay']=$pay['pay_amount_total'];
//                    }
//                }

            }
        }
        $list['data']['page']=$page;
        $list['data']['pageSize']=$pageSize;
        $this->return_data['data']=$list['data'];
        $this->return_data['statusCode']=$list['error'];
        $this->return_data['msg']=$list['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 下单
     * @param   $request=[//测试数据
    'addressId'=>4435,
    'deliveryId'=>1,
    'is_invoice'=>1,
    ];
     *
     */
    public function createOrder(){
        $request=$this->post;
        if(isset($request['action'])&&$request['action']=='createOrder'){
            $this->createOrderPay();
        }
        else{
            $get = I('get.');
            $data=$this->post;
            $data['delivery_id']=$data['deliveryId'];
            $data['address_id']=$data['addressId'];
            $data['ship_type']=$data['delivery_id'];

            $data['note']=$data['remark'];
            //子账号获取母账号账期
            $userInfo=$this->getUserInfo();
            $user_id = $userInfo['id'];
            $parentId=0;//帐号的父帐号

            //子账号获取母账号账期
            if($userInfo['user_type']==20){
                $user_p=D('user_son')->where(['user_id'=>$userInfo['id']])->find();
                $userAccount=D('user')->userAccountIsPass($user_p['p_id']);
                $parentId=$user_p['p_id'];
            }else{
                $userAccount=D('user')->userAccountIsPass($userInfo['id']);
            }
            //地址信息
            $userOrderAddress = $this->userOrderAddress($userInfo);

            $is_invoice=isset($data['is_invoice'])?$data['is_invoice']:0;//是否开发票,1开，0不开
            $settlementProduct = $this->settlementProduct($get['pid'],$is_invoice,$get['sample']);//下单商品数据
            $basket_id=$settlementProduct['basket_id'];

            if($settlementProduct['error']!=0){
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']='订单商品有误，请检查';
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);
            }
            $orderProduct=$settlementProduct['data']['list'];//订单商品数据
            $order_status=$settlementProduct['data']['order_status']?:0;//订单是否要审核

            $samples=D('product')->customerProductExampleAction($userInfo['id'],$parentId);
            if($samples['error']===0){
                $sample_list=$this->productList(['id'=>['in',$samples['data']]]);
            }
            $basket_detail=$settlementProduct['data']['list'];
            if($sample_list['data']['list']){
                foreach($sample_list['data']['list'] as $k=>$v){
                    $v['sample']=1;
                    $v['num']=$samples['listId_arr'][$v['id']]['buy_num'];
                    $basket_detail_sample[]=$v;
                }
            }
            $this->replaceKey($basket_detail_sample);
            $this->replaceKey($basket_detail);
            $productList['list']['basket_detail_sample']=$basket_detail_sample?$basket_detail_sample:[];
            $productList['list']['basket_detail']=$basket_detail?$basket_detail:[];
            $orderAddress=D('MemberCenter','Logic')->my(['id'=>$userInfo['id']],'','','','orderAddress','status=1');//默认收贷地址
            $productList['address']=$orderAddress['data']['list'][0]['user_order_address'][0];
            $productList['userAccount']=$userAccount;
            $this->return_data['data']=$productList;
            $this->return_data['statusCode']=0;
            $this->return_data['msg']='success';
            $this->ajaxReturn($this->return_data);
        }
        $where=[];
        $order='';
        $field='';
        $show_data='createGoods';//显示的数据类型

        $userInfo=$this->getUserInfo();
        $where['user_id']=$userInfo['id'];
        //子账号获取母账号账期
        if($userInfo['user_type']==20){
            $user_p=D('user_son')->where(['user_id'=>$userInfo['id']])->find();
            $userAccount=D('user')->userAccountIsPass($user_p['p_id']);
        }else{
            $userAccount=D('user')->userAccountIsPass($userInfo['id']);
        }
        $request['is_invoice']=($request['is_invoice']==1)?1:0;//1开票,0不开票

        $list=D('Basket','Logic')->basket($where,'','','',$show_data,$userInfo['id'],'',$request['is_invoice']);//购物车选中结算的商品

        $orderAddress=D('MemberCenter','Logic')->my(['id'=>$userInfo['id']],'','','','orderAddress','status=1');//默认收贷地址
        $list['data']['address']=$orderAddress['data']['list'][0]['user_order_address'][0];
        $list['data']['userAccount']=$userAccount;
        if($list['error']<0||$request['action']!='createOrder'){//结算页面
            $this->return_data['data']=$list['data'];
            $this->return_data['statusCode']=$list['error'];
            $this->return_data['msg']=$list['msg'];
            $this->ajaxReturn($this->return_data);
        }

        //创建定单
        $result=D('order','Logic')->createOrder($list['data'],$userInfo['id'],$request);

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }
    private function replaceKey(&$arr){
        foreach ($arr as &$v){
            if($v['price_section']){
                foreach ($v['price_section'] as &$item){
                    $item['lft_num']=$item['start'];
                    $item['right_num']=$item['end'];
                    $item['unit_price']=$item['price'];
                }
                $v['product_price']=$v['price_section'];
            }
            if(isset($v['cover_image'])){
                $v['img']=$v['cover_image'];
            }
            if(isset($v['id'])){
                $v['pid']=$v['id'];
            }
            if(isset($v['bargainInfo'])&&$v['bargainInfo']&&$v['bargainInfo']['is_pass']=='是'){
                $v['user_product_bargain']=$v['bargainInfo'];
            }
        }
    }
    public function createOrderPay(){

        $get = I('get.');
        $data=$this->post;
        $data['delivery_id']=$data['deliveryId'];
        $data['address_id']=$data['addressId'];
        $data['ship_type']=$data['delivery_id'];

        $data['note']=$data['remark'];
        //子账号获取母账号账期
        $userInfo=$this->getUserInfo();
        $user_id = $userInfo['id'];
        $parentId=0;//帐号的父帐号

        //子账号获取母账号账期
        if($userInfo['user_type']==20){
            $user_p=D('user_son')->where(['user_id'=>$userInfo['id']])->find();
            $userAccount=D('user')->userAccountIsPass($user_p['p_id']);
            $parentId=$user_p['p_id'];
        }else{
            $userAccount=D('user')->userAccountIsPass($userInfo['id']);
        }
        //地址信息
        $userOrderAddress = $this->userOrderAddress($userInfo);

        $is_invoice=isset($data['is_invoice'])?$data['is_invoice']:0;//是否开发票,1开，0不开
        $settlementProduct = $this->settlementProduct($get['pid'],$is_invoice,$get['sample']);//下单商品数据
        $basket_id=$settlementProduct['basket_id'];
        if($settlementProduct['error']!=0){
            $this->return_data['data']=[];
            $this->return_data['statusCode']=-1;
            $this->return_data['msg']='订单商品有误，请检查';
            $this->return_data['request']=$data;
            $this->ajaxReturn($this->return_data);
        }
        if($settlementProduct['error']!=0){
            $this->return_data['data']=[];
            $this->return_data['statusCode']=-1;
            $this->return_data['msg']='订单商品有误，请检查';
            $this->return_data['request']=$data;
            $this->ajaxReturn($this->return_data);
        }
        $orderProduct=$settlementProduct['data']['list'];//订单商品数据
        $order_status=$settlementProduct['data']['order_status']?:0;//订单是否要审核

        $samples=D('product')->customerProductExampleAction($userInfo['id'],$parentId);
        if($samples['error']===0){
            $sample_list=$this->productList(['id'=>['in',$samples['data']]]);
        }

        $error = ['error' => 0, 'msg' => '正常'];
        if ($data['action'] == 'createOrder') {
            $pay_type = $data['pay_type']?:'';//1在线支付，2账期支付，3快递代收，4面对面付款，5银行转账，6线下支付
            // if($pay_type==2&&$userAccount['error']!==0) die(json_encode($userAccount));
            if($pay_type==2&&$userAccount['error']!==0){
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']=$userAccount['msg'];
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);
            }

            $order_type = $data['order_type']?1:0;//0没有定金，1预付定金
            if (!in_array($pay_type, [1, 2, 3,4,5,6])){
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']='支付方式不正确';
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);
            }
            $ship_type = $data['ship_type'];//1快递，2物流，3自取，4送货
            if (!in_array($ship_type, [1, 2, 3,4,])){
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']='运输方式不正确';
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);

            }
            //            if( $order_status ==0 && $pay_type==2 ) $order_status=2;//账期支付，订单显示完全支付
            if (isset($data['deposits_pay_type'])&&!in_array($data['deposits_pay_type'], [1, 4, 5,6])){
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']='定金支付方式不正确';
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);
            }
            //定金支付方式（1在线支付，4面对面付款，5银行转账，6线下支付）
            //运费
            $delivery_type = ($data['delivery_type'] == 1) ? 1 : 2;//1,到付，2寄付
            $select_delivery_id = $data['delivery_id'];
            $delivery = $this->orderFreight(1,$delivery_type,$select_delivery_id);//运费
            if($delivery['error']==1){
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']=$delivery['msg'];
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);
            }
            $delivery_price=$delivery['data']['delivery_price'];
            //收货地址信息
            if(!$userOrderAddress&&$data['ship_type']!=3){
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']='地址信息不正确';
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);
            }
            $address_error=0;

            foreach($userOrderAddress as $k=>$v){
                if($v['id']==$data['address_id']){
                    $address_error=1;
                    $base_address=$v;
                }
            }
            if($data['ship_type']==3){
                $address_error=1;
                if(!$base_address){
                    if($userOrderAddress){
                        $base_address=$userOrderAddress[0];
                    }else{
                        $base_address['consignee']="";
                        $base_address['area_code']=0;
                        $base_address['address']="";
                        $base_address['mobile']="";
                    }
                }
            }
            if($address_error===0){
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']='地址信息不正确';
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);
            }
            //配送信息
            $where_delivery = [ 'id' => $select_delivery_id];
            $base_delivery = D('delivery')->where($where_delivery)->find();
            if (!$base_delivery){
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']='配送信息不正确';
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);
            }
            //备注信息
            $base_note = $data['remark'] ? $data['remark'] : 'no';


            //商品列表信息
            $base_product = [];
            //下单成功后商品库存信息
            $base_product_store=[];



            //基础数据处理
            $orderSn = $this->orderSn();
            if($orderSn['error']!=0){
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']='订单号生成失败';
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);
            }
            $order_sn=$orderSn['data']['one'];
            $add_data_goods = [];
            $total_origin = $total_deposits = $total = 0;

            $discountPriceIsPass=$this->discountPriceIsPass($is_invoice,$pay_type,$data['deposits_pay_type']);//优惠是否执行
            $wxPay=C("wxpay_opinion");

            $exampleProductAction=[];
            $totalXianhuo=0;//现贷总额
            if($orderProduct){
                foreach ($orderProduct as $k => $v) {
//                    $wa_intergral="";
//                    $wa_result=$this->returnIntegral([$v['id']]);
//                    if($wa_result){
//                        $wa_intergral=json_encode($wa_result[$v['id']]);
//                    }
                    $one_deposits_subtotal=0;//定金
                    //判断商品是否可拆
                    if($v['min_open']==0&&((int)$v['num']%(int)$v['min']!=0)){
                        $this->return_data['data']=[];
                        $this->return_data['statusCode']=-1;
                        $this->return_data['msg']=$v['p_sign'].'型号不可拆';
                        $this->return_data['request']=$data;
                        $this->ajaxReturn($this->return_data);

                    }
                    //+
                    $p_t=$discountPriceIsPass?$v['price_true']:$v['price_show'];
                    $p_t=round($p_t,4);

                    $one_pay_total=$p_t*$v["num"];
                    $one_pay_total=round($one_pay_total,2);
                    //$p_t=round($one_pay_total/$v["num"],6);
                    //+
                    //$one_pay_total=$discountPriceIsPass?($v['subtotal']-$v['discount_subtotal']):$v['subtotal'];//优惠是否执行--实付小计

                    if($v['store']<$v['num']){//没有库存且设置了定金比例的
                        if((float)$v['earnest_scale']){
                            //+
                            $one_deposits_subtotal=round((float)$one_pay_total*(float)$v['earnest_scale'],2);//定金
                            //+
                            //$one_deposits_subtotal=(float)$one_pay_total*(float)$v['earnest_scale'];//定金
                        }
                    }else{
                        $totalXianhuo+=$one_pay_total;//现贷总额
                    }

                    $rule_info=$v['bargainInfo']?json_encode($v['bargainInfo']):'';//议价
                    $one_fitemno=$v['bargainInfo']['fitemno']?$v['bargainInfo']['fitemno']:$v['fitemno'];
                    $fitemno_erp=M('product','erp_')->field('fitemno')->where(['ftem'=>$one_fitemno])->find();

                    if(!$fitemno_erp['fitemno']){
                        $this->return_data['data']=[];
                        $this->return_data['statusCode']=-1;
                        $this->return_data['msg']='型号错误，请联系私人客服及时修改,再下单';
                        $this->return_data['request']=$data;
                        $this->ajaxReturn($this->return_data);
                    }
                    $add_data_goods[] = [
                        'order_sn' => $order_sn,
                        'rule_info'=>$rule_info,//议价
                        'fitemno' => $one_fitemno,
                        'fitemno_sync' => $fitemno_erp['fitemno'],//erp同步的型号转换
                        'earnest_scale' => $v['earnest_scale']?$v['earnest_scale']:0,
                        'p_id' => $v['id'],
                        'p_name' => $v['p_sign'],
                        'bargain_price_id' => isset($v['bargainInfo']['id'])?$v['bargainInfo']['id']:0,
                        'p_price_show' => $v['price_show'],
                        'is_discount_num' => $v['is_discount_num'],//折扣限比例
                        //'p_price_true' => $discountPriceIsPass?$v['price_true']:$v['price_show'],
                        //+
                        'p_price_true' =>$p_t,
                        //+
                        'p_num' => $v['num'],
                        'subtotal' => $v['subtotal'],//显示小计
                        'discount_subtotal' => $v['discount_subtotal'],//优惠小计
                        'deposits_subtotal' =>$one_deposits_subtotal,//定金
                        'pay_subtotal' => $one_pay_total,//实付小计
                        'is_invoice_change'=>($is_invoice&&$v['bargainInfo']['price_invoice_change_pass'])?1:0,//是否换型号开票
                        //'wa_intergral'=>$wa_intergral,
                    ];
                    $total_origin += $v['subtotal'];
                    $total += $one_pay_total;
                    $total_deposits += $one_deposits_subtotal;

                    $base_product_store[]=[//更新的产品库存数据
                        'id'=>$v['id'],
                        'fitemno'=>$v['fitemno'],
                        'store'=>$v['store']-$v['num'],
                        'sell_num'=>$v['sell_num']+$v['num'],
                    ];
                }
            }elseif(!$orderProduct&&!$sample_list['data']['list']){
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']='购物车已空，请先添加至购物车';
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);
            }
            $order_type= D('order')->orderIsAllPay($pay_type,$total_deposits,$order_type);//是否全款支付
            if($order_type==1&&!isset($wxPay[$data['ship_type']][$is_invoice][$pay_type][$data['deposits_pay_type']])){
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']='请选择支付方式后再提交';
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);
            }elseif(!isset($wxPay[$data['ship_type']][$is_invoice][$pay_type])){
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']='请选择支付方式后再提交';
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);
            }
            $add_data_order = [//order表数据
                'order_sn' => $order_sn,
                'order_status' => $order_status,
                'order_type' => $order_type,
                'pay_type' => $pay_type,
                'ship_status' => 0,
                'ship_type' => $ship_type,
                'is_invoice' => $is_invoice,
                'user_id' => $userInfo['id'],
                'delivery_name' => $base_delivery['name'],
                'delivery_code' => $base_delivery['code'],
                'delivery_price' => $delivery_price,
                'total' =>$delivery_price + $total,//订单实付总价
                'pay_status'=>$settlementProduct['pay_status'],
                'total_origin' =>$delivery_price + $total_origin,
                'total_invoice' =>$delivery_price + $total,
                'total_deposits' =>$total_deposits,//定金
                'total_discount' =>$total_origin-$total,
                'deposits_pay_type' =>$data['deposits_pay_type'],
            ];

            $add_data_detail = [//order_detail表数据
                'order_sn' => $order_sn,
                'consignee' => $base_address['consignee'],
                'area_code' => $base_address['area_code'],
                'address' => $base_address['address'],
                'mobile' => $base_address['mobile'],
                'note' => $base_note,
            ];


            //定单样品数据user_product_example
            $order_example_data=$exampleProductAction=[];
            if(isset($sample_list['data']['list'])&&$sample_list['data']['list']){
                foreach($sample_list['data']['list'] as $k=>$v){

                    $one_sample=$samples['listId_arr'][$v['id']]['fitemno'];//erp同步的型号转换
                    $fitemno_erp=M('product','erp_')->field('fitemno')->where(['ftem'=>$one_sample])->find();

                    $exampleProductAction=$v['id'];
                    $add_data_goods[]=[
                        'order_sn' => $order_sn,
                        'rule_info'=>json_encode($samples['listId_arr'][$v['id']]),
                        'fitemno' => $one_sample,
                        'fitemno_sync' => $fitemno_erp['fitemno'],//erp同步的型号转换
                        'earnest_scale' => 0,
                        'p_id' => $v['id'],
                        'p_name' => $v['p_sign'],
                        'bargain_price_id' => 0,
                        'p_price_show' => 0,
                        'is_discount_num' => 1,//折扣限比例
                        'p_price_true' => 0,
                        'p_num' => $samples['listId_arr'][$v['id']]['buy_num'],
                        'subtotal' => 0,//显示小计
                        'discount_subtotal' => 0,//优惠小计
                        'deposits_subtotal' =>0,//定金小计
                        'pay_subtotal' => 0,//实付小计
                        'is_invoice_change'=>0,//是否换型号开票
                    ];
                }
            }
            $user_pay=[//本次支付
                'user_pay_step'=>[
                    'table_prefix'=>'dx_',
                    'field'=>'user_id,amount,index,type',
                    'data'=>[
                        'user_id'=>$userInfo['id'],
                        'amount'=>($order_type ? $total_deposits+$totalXianhuo:$delivery_price + $total)*100,//本次支付
                        'index'=>$order_sn,
                        'type'=>'1',
                    ]
                ]
            ];
            //账期支付
            $order_total = $add_data_order['total'];//订单金额
            $typePayNum = $this->typePayNum($pay_type, $order_total, $delivery_price, $total_deposits,$add_data_order['order_type'],$totalXianhuo,$data['deposits_pay_type']);
            $online_pay = $typePayNum['online_pay'];//在线支付金额
            $account_pay = $typePayNum['account_pay'];//账期支付金额
            //2018-10-18修改
            //$add_data_order['already_paid']=$account_pay?:0;
            $add_data_order['already_paid']=0;
            $user = $basket_id = D('basket')->where(['user_id' => $userInfo['id']])->find();
            $basket_id = $user['basket_id'];
            $log_sql='';

            M('order')->startTrans();
            $order_result=D('order')->data($add_data_order)->add();
            $order_detail_result=D('order_detail')->data($add_data_detail)->add();
            $order_goods_result=D('order_goods')->addAll($add_data_goods);
            //下单成功,清除购物车相应商品
            $basket_detail_result=D('basket_detail')->where(['basket_id' => $basket_id, 'status' => 1])->delete();

            if ($account_pay){
                if($userAccount['error']!=0) die(json_encode($userAccount));
                if($userAccount['data']<$account_pay){
                    M()->rollback();
                    $this->return_data['data']=[];
                    $this->return_data['statusCode']=-1;
                    $this->return_data['msg']='账期可用余额不足';
                    $this->return_data['request']=$data;
                    $this->ajaxReturn($this->return_data);
                }

                $accountPayResult=$this->orderAccountPay($userInfo['id'], $order_sn, $order_total, $account_pay,$userAccount);//账期支付
            }
            $product_result=1;
            $toErp_result=0;
            if( $total_deposits==0 && in_array($pay_type,[2,3,4,5,6]) ){

                foreach($base_product_store as $k=>$v){
                    $sell_num=(int)$v['sell_num'];
                    $one_id=(int)$v['id'];
                    $dataErp=[
                        'store'=>$v['store'],
                    ];

                    $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
                    $one_store_result=$Model->execute("update dx_product set sell_num=$sell_num where id=$one_id");

                    if( !$one_store_result){
                        $product_result=0;//更新库存
                        break;
                    }
                }
            }

            //样品更新
            $example_result=0;
            if($exampleProductAction){
                $oneWhere=[
                    'pid'=>['in',$exampleProductAction],
                    'uid'=>$parentId?:$user_id,
                ];
                $sample_result=M('basket_detail_sample')->where(['basket_id'=>$basket_id,'pid'=>['in',$exampleProductAction],'status'=>1])->delete();
                $sample_result2=M('user_product_example')->where($oneWhere)->save(['step'=>0]);
                if($sample_result===false||$sample_result2===false){
                    $example_result=1;
                }
            }
            $error=0;
            if(!$order_result){
                $error=1;
            }else if(!$order_detail_result){
                $error=2;
            }else if(!$order_goods_result){
                $error=3;
            }else if($basket_detail_result===false){
                $error=4;
            }else if(!$product_result){
                $error=5;
            }else if(isset($accountPayResult['error'])&&$accountPayResult['error']!=0){
                $error=7;
            }else if($example_result){
                $error=9;
            }


            if($error==0){

                //                if( $total_deposits==0 && in_array($pay_type,[2,3,4,5,6]) ){
                if( in_array($pay_type,[2,3,4,5,6]) || $add_data_order['pay_status']==2){
                    $isSyncArr=$add_data_order;
                    $$isSyncArr['deposits_pay_status']=0;
                    $isSync=isSync($isSyncArr);
                    //审核不同步
                    if($order_status!=1&&$isSync){
                        $toErp=(new \Home\Model\ErpModel())->orderToErp($order_sn);//订单同步到erp
                        if($toErp['error']!=0) $error=8;
                        //发送消息通知
                        MsgController::writeMsgToUserSale( $user_id, '新订单'.$order_sn, '新订单编号:'.$order_sn.'{}
                 订单总金额:'.$order_total.'{}在线支付金额:'.$online_pay.'{}账期支付金额:'.$account_pay.'{}下单时间:'.date('Y-m-d H:i:s') );
                        //审核不同步
                    }else{
                        //$toErp=(new \Home\Model\ErpModel())->orderToErp($order_sn);//订单同步到erp
                        //if($toErp['error']!=0) $error=8;
                        //发送消息通知
                        MsgController::writeMsgToUserSale( $user_id, '新订单'.$order_sn, '新订单编号:'.$order_sn.'{}
                 订单总金额:'.$order_total.'{}在线支付金额:'.$online_pay.'{}账期支付金额:'.$account_pay.'{}下单时间:'.date('Y-m-d H:i:s') );
                    }
                }

                //首单减20,在线支付
                //				$FirstOrder_result=D('Home/Order')->customerFirstOrder();

                M('order')->commit();
                $return_data=['order_sn'=>$order_sn,'online_pay'=>$online_pay];
                $error = ['error' => $error, 'msg' => '订单提交成功','data'=>$return_data];
            }else{
                M('order')->rollback();
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']='订单提交失败';
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);

            }
            //有账期未还下单提示
            if($pay_type!=1&&(int)($userAccount['one']['used_debt']*100)>0){//在线支付的时候不提示
                $this->return_data['data']=$add_data_order;
                $this->return_data['statusCode']=300;
                $this->return_data['msg']= $error['msg'];
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);

            }
            if($order_status==1){
                $this->return_data['data']=$add_data_order;
                $this->return_data['statusCode']=1;
                $this->return_data['msg']= '订单审核中.....';
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);
            }else{
                $this->return_data['data']=$add_data_order;
                $this->return_data['statusCode']=0;
                $this->return_data['msg']= '订单提交成功';
                $this->return_data['request']=$data;
                $this->ajaxReturn($this->return_data);
            }
        }

    }

    /*
 * 账期支付
 * params=[
 *              user_id:用户id,
 *              order_sn:订单编号,
 *              $order_total:订单总金额,
 *              $account_pay:账期支付金额,
 * ]
 */
    public function orderAccountPay($user_id, $order_sn, $order_total, $account_pay,$userAccount)
    {
        $type = 2;//账期支付
        $pay_history_data = [
            'order_sn' => $order_sn,
            'order_total' => $order_total,
            'pay_amount' => $account_pay,
            'type' => $type,
            'user_order_account_id' =>0,
            'pay_name' => '账期支付',
        ];
        $quota_used = $userAccount['one']['used_quota'] + $account_pay;

        $m=M('order_pay_history');
        //        $m->startTrans();
        $result1=$m->data($pay_history_data)->add();//记录本次支付
        $result2=M('accounts','erp_')->data(['used_quota' => $quota_used])->where(['id' => $userAccount['one']['id']])->save();//更新账期记录
        if($result1&&$result2!==false){
            //            $m->commit();
            $error = ['error' => 0, 'msg' => '账期支付成功'];
        }else{
            //            $m->rollback();
            $error = ['error' => 1, 'msg' => '账期支付失败'];
        }
        return $error;
    }
    /*
     * 计算支付金额:在线支付，帐期支付,货到付款
     * params=
     *      type:1帐期支付 ,2在线支付，3货到付款，
     *      order_total:订单总额
     *      account_pay:账期支付金额,
     *      delivery_price:运费,
     *      goods_deposit:商品定金,
     *      order_type:1定金支付，0没有选择定金支付,
     *      totalXianhuo：现贷金额，
     *      deposits_pay_type:定金支付方式
     */
    public function typePayNum($type, $order_total, $delivery_price, $goods_deposits,$order_type,$totalXianhuo,$deposits_pay_type)
    {
        //1在线支付，2账期支付，3快递代收，4面对面付款,5银行转账，6线下支付
        $return = [];
        if ($type == 1) {
            if($order_type==1){
                if($deposits_pay_type==1) $return['online_pay'] = $goods_deposits + $totalXianhuo+ $delivery_price;
                else $return['online_pay'] = 0;
            }else{
                $return['online_pay'] = $order_total + $delivery_price;
            }
            $return['account_pay'] = 0;
            $return['credit_pay'] = 0;
        } else if ($type == 2) {
            $return['account_pay'] = $order_total + $delivery_price-$goods_deposits;
            if($deposits_pay_type==1){
                $return['online_pay'] = $goods_deposits;
            }
            $return['credit_pay'] = 0;
        } else if ($type == 3 || $type == 4 || $type == 5|| $type == 6) {
            $return['account_pay'] = 0;
            if($deposits_pay_type==1){
                $return['online_pay'] = $goods_deposits;
            }
            $return['credit_pay'] = $order_total + $delivery_price;
        }
        //		if ($return['online_pay'] < $goods_deposits) die(json_encode(['error' => 1, 'msg' => '在线支付金额不能少于产品定金']));
        return $return;
    }

    /*
     * 商品列表
     *
     */
    public function productList($where='',$page='',$pageSize='',$level=true,$order=''){
        $where['is_online']=1;
        $product=new \Admin\Model\ProductModel;
        $field='unit,pack_unit,min,min_open';
        $field.=',parameter,package,batch,is_earnest,earnest_scale,is_delivery';
        $field.=',delivery,cover_image,fitemno_access,is_tax,discount_num,describe_image';
        $field.=',note,note_isShow,describe,is_inquiry_table,id,p_sign';
        $field.=',cate_id,brand_id,unit,tax,fitemno,sell_num,show_site';

        $productResult=$product->productList($where,$page,$pageSize,$level,$order,$field,'front');
        return $productResult;
    }


    /*
 * 选择商品去结算
 * $pid=[1,2,3,4]
 */
    public function settlementProduct($pid,$is_invoice,$sample_pid=[])
    {
        $userInfo=$this->getUserInfo();
        $user_id = $userInfo['id'];
        $user = D('basket')->where(['user_id' => $user_id])->find();
        $basket_id = $user['basket_id'];
        $where = ['basket_id' => $basket_id, 'status' => 1];
        $basket_detail = D('basket_detail')->where($where)->select();
        $pay_status=0;
        if(!$basket_detail) $pay_status=2;
        $field_pid = $basket_num = [];
        $productId_arr=[];
        foreach ($basket_detail as $k => $v) {
            $field_pid[$k]['id'] = $v['pid'];
            $basket_num[$v['pid']] = $v['num'];
            $productId_arr[]=$v['pid'];
        }
        //直接引用
        $admin_product=new \Admin\Model\ProductModel;
        $producResult=$admin_product->productList(['id'=>['in',$productId_arr]]);
        $product_list=($producResult['error']==0)?$producResult['data']['list']:'';

        foreach($product_list as $k=>$v){ $product_list[$k]['num']=$basket_num[$v['id']]; }

        $productList=$this->goodsPrice($product_list,$is_invoice);

        $productList['basket_id']=$basket_id;
        $productList['pay_status']=$pay_status;//支付状态
        return $productList;
    }


    /*
 *商品价格
 * $product_list=[
 *      [
 *           id=>1,
 *           num=>1,
 *           .......
 *      ]
 * ]
 */
    public function goodsPrice($product_list,$is_invoice){ //开票和优惠的逻辑分开
        $userInfo=$this->getUserInfo();
        $priceCustomerId=$userInfo['id'];
        if($userInfo['user_type']==20){
            $parent=M('user_son')->where(['user_id'=>$priceCustomerId])->find();
            $priceCustomerId=$parent['p_id'];
        }
        $list=$this->customerProductPrice($priceCustomerId,$product_list);
        if($list['error']==0){ $list_discount=$list['data']['list']; }
        $order_status=0;//订单是否审核,0不审核，1审核

        foreach($product_list as $k=>$v){
            $product_list[$k]['bargainInfo']='';
            $onePrice=[];
            $onePrice['subtotal']=0;//小计
            $onePrice['discount_subtotal']=0;//优惠小计
            $onePrice['is_discount_num']=1;//折扣限额比例

            //判断是否可执行优惠价
            $oneProduct=$list_discount[$v['id']];

            if( $is_invoice && !$v['is_tax'] && !$oneProduct['price_invoice_change_pass']) $order_status=1;//开票，未进项，没有议价或换型号开票没启用---订单需要审核

            if($oneProduct){//有议价
                $product_list[$k]['bargainInfo']=$oneProduct;
                if($v['num']>=$oneProduct['min_buy']){//大于最小购买量
//                    $request=$this->post;
//                    if(isset($request['action'])&&$request['action']=='createOrder'){
//                        $discountPriceIsPass=$this->discountPriceIsPass($is_invoice,$request['pay_type'],$request['deposits_pay_type']);//优惠是否执行
//                        if($discountPriceIsPass){
//                            $is_invoice=0;
//                        }else{
//                            $is_invoice=1;
//                        }
//                    }
                    if($oneProduct['price_pass']&&$oneProduct['price_tax_pass']){//含税和不含税都有议价
                        if($is_invoice==1&&$oneProduct['price_tax_pass']==1){//开票

                            $onePrice['price_show']=$oneProduct['discount_price_tax'];//显示价格
                            $onePrice['price_true']=$oneProduct['discount_price_tax'];//计算单价

                        }else if(!$is_invoice&&$oneProduct['price_pass']==1){//不开票
                            //$onePrice['price_true']=$oneProduct['discount_price']*$v['tax'];//计算单价
                            $onePrice['price_show']=$oneProduct['discount_price_tax'];//显示价格
                            $onePrice['price_true']=$oneProduct['discount_price'];//计算单价

                            $onePrice['discount_subtotal']=($oneProduct['discount_price_tax']-$oneProduct['discount_price'])*$v['num'];//优惠小计

                            $request=$this->post;
                            if(isset($request['action'])&&$request['action']=='createOrder'){
                                $discountPriceIsPass=$this->discountPriceIsPass($is_invoice,$request['pay_type'],$request['deposits_pay_type']);//优惠是否执行
                                if($discountPriceIsPass){
                                    //$onePrice['price_show']=$oneProduct['discount_price']*$v['tax'];//显示价格
                                    $onePrice['price_show']=$oneProduct['discount_price_tax'];//显示价格
                                    $onePrice['price_true']=$oneProduct['discount_price'];//计算单价

                                    $onePrice['discount_subtotal']=($oneProduct['discount_price_tax']-$oneProduct['discount_price'])*$v['num'];//优惠小计
                                }else{
                                    $onePrice['price_show']=$oneProduct['discount_price_tax'];//显示价格
                                    $onePrice['price_true']=$oneProduct['discount_price'];//计算单价

                                    $onePrice['discount_subtotal']=($oneProduct['discount_price_tax']-$oneProduct['discount_price'])*$v['num'];//优惠小计
                                }
                            }
                        }
                    }else if(!$oneProduct['price_pass']&&$oneProduct['price_tax_pass']){//只含税有议价

                        $onePrice['price_show']=$oneProduct['discount_price_tax'];//显示价格
                        $onePrice['price_true']=$oneProduct['discount_price_tax'];//计算单价

                        //折扣限
                        if($is_invoice!=1&&$oneProduct['price_tax_pass']==1 && $oneProduct['discount_price_tax']*$v['num']>=$v['discount_num']){//不开票
                            if($oneProduct['price_invoice_change_pass']==1) $v['tax']=1.1;//换型号的计算优惠时税率固定1.1
                            $onePrice['discount_subtotal']=$oneProduct['discount_price_tax']*$v['num']*(1-1/$v['tax']);
                            $onePrice['is_discount_num']=0.9;

                            $onePrice['price_true']=$oneProduct['discount_price_tax']/$v['tax'];//计算单价
                        }
                    }else if($oneProduct['price_pass']&&!$oneProduct['price_tax_pass']){//只不含税有议价

                        $onePrice['price_show']=$oneProduct['discount_price']*$v['tax'];//显示价格

                        if($is_invoice==1&&$oneProduct['price_pass']==1){//开票

                            $onePrice['price_true']=$oneProduct['discount_price']*$v['tax'];//计算单价

                        }else if($is_invoice!=1&&$oneProduct['price_pass']==1){//不开票

                            $onePrice['price_true']=$oneProduct['discount_price'];//计算单价

                            $onePrice['discount_subtotal']=$oneProduct['discount_price']*($v['tax']-1)*$v['num'];//优惠小计
                        }
                    }
                }
                $onePrice['subtotal']=$onePrice['price_show']*$v['num'];//小计
                $request=$this->post;
                if(isset($request['action'])&&$request['action']=='createOrder'&&!$onePrice['subtotal']){
                    $product_list[$k]['bargainInfo']="";
                }

            }

            if(!$onePrice['subtotal']){//商品没有议价
                foreach($v['price_section'] as $k2=>$v2){
                    if(!$v['num']) return ['error'=>1,'msg'=>'商品数量num没有设置'];
                    if($v['num']<$v2['end']){
                        $product_list[$k]['price_true']=$product_list[$k]['price_show']=$v2['price'];break;//根据区间选中的价格
                    }
                }
                if(!$product_list[$k]['price_true']){
                    $last_price=array_pop($v['price_section']);
                    $product_list[$k]['price_true']=$product_list[$k]['price_show']=$last_price['price'];//根据区间选中的价格
                }

                $onePrice['subtotal']=$product_list[$k]['price_true']*$v['num'];//小计
                if(!$is_invoice){//不开票达到折扣限
                    if( $onePrice['subtotal']>=$v['discount_num'] ){
                        $onePrice['discount_subtotal']=$onePrice['subtotal']*(1-1/$v['tax']);
                        $onePrice['is_discount_num']=0.9;

                        $product_list[$k]['price_true']=$product_list[$k]['price_true']/$v['tax'];//实付价格20180718
                    }
                }
            }

            $product_list[$k]=array_merge($product_list[$k],$onePrice);
        }

        return ['error'=>0,'data'=>['list'=>$product_list,'order_status'=>$order_status]];
    }

    /*
     * 顾客商品优惠价格
     *
     */
    public function customerProductPrice($cusId,$productList){
        $product=new \Admin\Model\ProductModel;
        $price=$product->customerProductPrice($cusId,$productList);
        return $price;
    }

    /*
 * 判断是否能执行优惠价
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

    /*
 *用户收货地址列表
 */
    public function userOrderAddress($userInfo)
    {
        $address_list = [];
        $user_id=$userInfo['id'];
        $where=[
            'user_id' => $user_id,
        ];
        if($userInfo['user_type']==20){//企业子帐号
            $where[]=[
                "user_id = (select p_id from dx_user_son where user_id=$user_id)",
            ];
            $where['_logic']='or';
        }
        $address_list = D('user_order_address')->where($where)->select();
        return $address_list;
    }

    /*
     *运费
     * params=[
     * 1,到付，2寄付
     * ]
     */
    public function orderFreight($is_return=0,$type = 1,$select_delivery_id='')
    {
        $get=I('get.');
        if(!$is_return){
            $type = $get['type'];
            $select_delivery_id=$get['select_delivery_id'];
        }
        $error=['error'=>0,'msg'=>'运费'];
        if ($type == 1) {//到付
            $error['data']=['delivery_price'=>0];
        } else if($type == 2) {//寄付
            if($select_delivery_id){
                $delivery=D('delivery')->where(['id'=>$select_delivery_id])->find();
                if( !$delivery ){
                    $error=['error'=>1,'msg'=>'快递没有此项选择'];
                }else if($delivery['name']=='同城自取'){
                    $error['data']=['delivery_price'=>0];
                }else{//运费计算
                    $error['data']=['delivery_price'=>0];
                }
            }else{
                $error=['error'=>1,'msg'=>'快递没有此项选择'];
            }
        }
        if(!$is_return) die(json_encode($error));
        return $error;
    }

    /**
     * @desc 结单,退贷退款
     *
     */
    public function knotOrder(){
        $request=$this->post;

        $userInfo=$this->getUserInfo();
        $request['user_id']=$userInfo['id'];
        $request['user_name']=$userInfo['user_name'];

        $result=D('KnotOrder','Logic')->knotOrder($request);

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 申请返差额
     * @param
    $request=[
    'order_sn'=>'180424110275',
    'customer_account_type'=>'1',
    'customer_account'=>'123',
    'notify_mobile'=>'123',
    'account_name'=>'',
    ];
     *
     */
    public function knotOrderMoney(){
        $request=$this->post;

        $userInfo=$this->getUserInfo();
        $request['user_id']=$userInfo['id'];

        $result=D('Admin/Order','Event')->updateOrder($request);
        $result=$result[0];

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=-$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 取消定单
     *
     */
    public function cancelOrder(){
        $request=$this->post;

        $userInfo=$this->getUserInfo();
        $request['user_id']=$userInfo['id'];

        $result=D('order','Logic')->cancelOrder($request);

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 确认收贷
     *
     */
    public function hyReceive(){
        $request=$this->post;

        $userInfo=$this->getUserInfo();
        $request['user_id']=$userInfo['id'];

        $result=D('order','Logic')->hyReceive($request);

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /*
     *获取订单编号
     *
     */
    public function orderSn(){
        $m=new \Admin\Model\OrderModel;
        $list=$m->orderSn();
        return $list;
//        return ['error'=>0,'data'=>['one'=>uniqid()]];
    }


    /**
     * @desc 判断是否满足积分规则
     *array arr 产品ID数组  [good_id][type]
     * 特殊积分优先普通积分规则  特殊积分（101） 普通积分（1 21 41）
     */
    public function returnIntegral($arr){
        foreach ($arr as $v){
            $where['_string']='FIND_IN_SET($v, cell_code)';
            $where['start_time']=['ELT',date("Y-m-d H:i:s",time())];
            $where['end_time']=['EGT',date("Y-m-d H:i:s",time())];
            $where['status']=1;
            //特殊规则
            $where['type']=101;
            $specialRules=M('integral_rule','wa_')->where($where)->find();
            $return='';
            if(!$specialRules){
                $where['type']=['IN',[1,21,41]];
                $generalRules=M('integral_rule','wa_')->where($where)->select();
                if($generalRules){
                    $ids = array_column($generalRules, 'id');
                    $where['id']=["not in",$ids];
                }
                unset($where["_string"]);
                $generalRules=M('integral_rule','wa_')->where($where)->select();
                foreach($generalRules as $g_v){
                    $return[$v][$g_v['type']]=$g_v;
                }
            }else{
                $return[$v][101]=$specialRules;
            }
            return $return;
        }
    }


}