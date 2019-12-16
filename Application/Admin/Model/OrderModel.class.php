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
use EES\System\Redis;
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
    public function orderList($where='',$page='',$pageSize='',$order='',$slave=true,$product_where='',$st='',$field='*'){
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
//            //财务部门
//            $productPowers=$userM->departmentDataPower('money',$session['adminInfo']['department_id'],$where,'sys_uid');
//            if($productPowers['error']!=0) return $productPowers;
//            if(isset($productPowers['data']['must_where'])){
//                $customers=$userM->adminSaleCustomerPower($productPowers['data']['must_where'],'id');
//                if($customers['error']!=0) return $customers;
//                $must_where['user_id']=['in',$customers['data']];
//            }
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
        $order=$this->baseList($model,$where,$page,$pageSize,$order,$field);
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

            $customerParent=D('Admin/Customer')->sonGetFather($customerId_arr);//母帐号信息

            //订单是否可换型号
            $fitemnoIsChange=D('Admin/Order','Event')->ordersInfo($orderSn_arr,['dx_product_ftimeno'],['order_sn']);

            $goods=$this->orderGoodsList($orderSn_arr,$product_where);

            $orderInfo_arr=$productId_arr=$package_arr=[];
            if($goods['error']==0){
                foreach($goods['data']['list'] as $k=>$v){
                    foreach($v as $k2=>$v2){
                        $productId_arr[]=$v2['p_id'];
                        $orderInfo_arr[$v2['order_sn']]['current_total']+=($v2['p_num']-$v2['retreat_num']-$v2['knot_num'])*$v2['pay_subtotal']/$v2['p_num'];//现在需要的付款金额
                    }
                }
//                $productList=$this->baseListType(M('product'),$productId_arr,'id',['id','cover_image']);//产品信息
//                foreach($goods['data']['list'] as $k=>$v){
//                    foreach($v as $k2=>$v2){
//                        $goods['data']['list'][$k][$k2]['cover_image']=$productList['data']['list'][$v2['p_id']]['cover_image'];
//                    }
//                }
            }
            $customerId_arr=$customerId_arr?array_unique($customerId_arr):[];
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
				   if($customerList['data']['list'][$v['user_id']]['fcustjc']){
					   $order['data']['list'][$k]['customerName']=$customerList['data']['list'][$v['user_id']]['fcustjc'];
				   }else{
					   $order['data']['list'][$k]['customerName']=$customerList['data']['list'][$v['user_id']]['nick_name'];
				   }
                   $order['data']['list'][$k]['company']=$listCompany['data']['list'][$v['user_id']];
                   $order['data']['list'][$k]['hyInfo'] = M('order_sync_hy')->where(['order_no'=>$v['order_sn'],'is_lock'=>['neq',0]])->select();
                   $order['data']['list'][$k]['isPart'] = $order['data']['list'][$k]['hyInfo'][0]['is_part'] > 0 ? 1:0;
                   $order['data']['list'][$k]['fitemnoIsChange'] = $fitemnoIsChange[0][$v['order_sn']];//订单是否可换型号
                   $order['data']['list'][$k]['sync_status']=$fitemnoIsChange[1][$v['order_sn']]['sync_status'];//订单同步状态
                   $order['data']['list'][$k]['knot_status']=isset($fitemnoIsChange[2][$v['order_sn']])?$fitemnoIsChange[2][$v['order_sn']]['check_status']:10000;//订单结单状态
                   $order['data']['list'][$k]['current_total']=$orderInfo_arr[$v['order_sn']]['current_total'];
				   $mapp=[];
                   $mapp['order_no']=array('eq',$v['order_sn']);
                   if($st==''){
					   $mapp['is_kd']=array('eq',1);
					   $mapp['is_lock']=array('neq',0);
					   $order['data']['list'][$k]['orderKdList'] =M('order_sync_hy')->where($mapp)->select();
				   }else{
					   $mapp['is_lock']=array('neq',0);
					   $order['data']['list'][$k]['orderKdList'] =M('order_sync_hy')->where($mapp)->select();
				   }
               }

               $order['data']['list'][$k]['order_user']='';
               if(isset($customerParent['data']['list'][$v['user_id']])){//母帐号信息
                   $order['data']['list'][$k]['sale']=$customerParent['data']['list'][$v['user_id']]['salename'];
                   $order['data']['list'][$k]['company']['company_name']=$customerParent['data']['list'][$v['user_id']]['company_name'];

                   $son_user=M('user')->field('nick_name')->where([['id'=>$v['user_id']],['id'=>['neq',session('userId')]]])->find();//子帐号
                   $order['data']['list'][$k]['order_user']=$son_user['nick_name'];
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
        $goods=M('order_goods')->field('dx_order_goods.*,(select img as cover_image from dx_product_package_img where package in (select package from dx_product where id = dx_order_goods.p_id)) as cover_image')
            ->where($goods_where)->select();
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
    public function invoiceList($where='',$page='',$pageSize='',$order='id desc',$field='',$is_field=false,$userId=''){
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
            $invoiceId_arr=$userId_arr=[];
            foreach($list['data']['list'] as $k=>$v){
                $invoiceId_arr[]=$v['id'];
                $userId_arr[]=$v['user_id'];
            }
            $userId_arr=array_unique($userId_arr);
            $customersInfo=$this->baseListType(M('user'),$userId_arr,'id','fcustjc,nick_name,id,(select fullname from sys_user where sys_user.uid =dx_user.sys_uid) as salename');
            $orderWhere=['user_invoice_id'=>['in',$invoiceId_arr]];
            if($userId){
                $orderWhere['user_id']=$userId;
            }
            $order=M('order')->field('*')->where($orderWhere)->select();
            if($order){
                $order_arr=[];
                foreach($order as $k=>&$v){
                    $has_money=hasOrderPay([$v['order_sn']]);
                    $v['order_has_pay']=$has_money[0];
                    $order_arr[$v['user_invoice_id']][]=$v;
                }
                foreach($list['data']['list'] as $k=>$v){
                    $list['data']['list'][$k]['customerName']=$customersInfo['data']['list'][$v['user_id']]['fcustjc']?:$customersInfo['data']['list'][$v['user_id']]['nick_name'];
                    $list['data']['list'][$k]['saleName']=$customersInfo['data']['list'][$v['user_id']]['salename'];
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

    /**
     * @desc 定单换商品换erp型号
     */
    public function orderChangeFitemno($request){
        if(!is_array($request['goodsList'])||!$request['goodsList']) return ['error'=>1,'msg'=>'参数错误'];
        $order=$this->orderList(['order_sn'=>$request['order_sn']]);
        if($order['error']!==0) return ['error'=>1,'msg'=>'订单编号错误'];
		$order_sync=M('order_sync')->where(['order_sn'=>$request['order_sn'],'sync_status'=>1])->find();
		if(!$order_sync){
			return ['error'=>1,'msg'=>'换型号订单必须先同步成功'];
		}
        $ProductFitemno=M('product_fitemno');
        $OrderGoods=M('order_goods');

        M()->startTrans();
        foreach($request['goodsList'] as $k=>$v){
            $many=$ProductFitemno->field('dx_product_fitemno.*,(select fitemno from erp_product where erp_product.ftem=dx_product_fitemno.fitemno) as erp_fitemno')->where(['p_sign'=>$v['p_sign'],'fitemno'=>$v['fitemno']])->find();
            if(!$many){
				M()->rollback();
                return ['error'=>1,'msg'=>'erp型号错误'];
            }
            $save_data=[
                'fitemno_change'=>$many['erp_fitemno'],
                'change_fitemno_admin'=>session('adminId')
            ];
            $one=$OrderGoods->field('fitemno_change,change_fitemno_admin')->where(['order_sn'=>$request['order_sn'],'p_name'=>$v['p_sign']])->save($save_data);
            if($one===false){
				M()->rollback();
                return ['error'=>1,'msg'=>$v['p_sign'].':修改错误'];
            }
        }
	
		$orderData=M('order')->where(['order_sn'=>$request['order_sn']])->find();
		if($orderData['pay_type']!=1||($orderData['pay_type']==1&&$orderData['pay_status']==2)){
			$orderSync = M('order_sync');
			if(!($orderSync->where(['order_sn'=>$request['order_sn']])->find())){
				
				$res = $orderSync->add([ 'order_sn'=>$request['order_sn'] ]);
				if($res === false )  {
					M()->rollback();
                    return ['error'=>1,'msg'=>$request['order_sn'].'----订单信息同步写入数据库失败'];
					die(json_encode(['error'=>1,'msg'=>$request['order_sn'].'----订单信息同步写入数据库失败']));
				}
			}else{
				$res = $orderSync->where([ 'order_sn'=>$request['order_sn'] ])->save(['sync_status'=>4]);
				if($res === false ){
					M()->rollback();
                    return ['error'=>1,'msg'=>$request['order_sn'].'----订单信息同步写入数据库失败'];
					die(json_encode(['error'=>1,'msg'=>$request['order_sn'].'----订单信息同步写入数据库失败']));
				}
			}
			$redis = Redis::getInstance();
			$rs = $redis->sAdd( 'shopOrderSyncList1', $request['order_sn'] );
			if($rs === false ){
				M()->rollback();
                return ['error'=>1,'msg'=>$request['order_sn'].'----订单信息带同步失败!请检查缓存'];
				die(json_encode(['error'=>1,'msg'=>$request['order_sn'].'----订单信息带同步失败!请检查缓存']));
			}
			if((int)$rs === 0 ){
				M()->rollback();
                return ['error'=>0,'msg'=>$request['order_sn'].'----订单信息带同步失败!请检查缓存'];
				die(json_encode(['error'=>1,'msg'=>$request['order_sn'].'----已经存在同步列表']));
			}
		}
	
		M()->commit();
        return ['error'=>0,'msg'=>'success'];
    }

    //Event //取出数据
    public function toOrderInfo($where='',$field='',$limit=[0,10]){
        $page=(int)(($limit[0]-1)*$limit[1]);
        $pageSize=(int)$limit[1];
        $list=M('order')->alias('o')
            ->field($field)
            ->join('left join dx_user u on u.id = o.user_id')
            ->join('left join sys_user su on u.sys_uid = su.uid')
            ->where($where)
            ->limit($page,$pageSize)
            ->select();
        $this->list=$list;
        return $list;
    }

    /**
     *
     * @desc  定单实际要付的金额,下单计算总额-退款总额-接单总额
     * @param $orderSn_arr  订单编号数组
     *
     */
    public function orderPayTotalEnd($orderSn_arr){
        $list=M('order_goods')->field('fitemno,order_sn,p_id,p_num,knot_num,pay_subtotal')->where([
            'order_sn'=>['in',$orderSn_arr]
        ])->select();
        if(!$list) return ['error'=>1,'msg'=>'订单信息错误'];

        $orderSn_arr=[];
        foreach($list as $k=>$v){
            $orderSn_arr[$v['order_sn']]['totalEnd']+=($v['p_num']-$v['knot_num'])*$v['pay_subtotal']/$v['p_num'];
        }

        return ['error'=>0,'data'=>$orderSn_arr];
    }

    /**
     * @desc 定单付款总额
     * @param orderSn_arr  array
     */
    public function orderPayment($orderSn_arr){
        $where=[
            'order_sn'=>['in',$orderSn_arr],
            [
                [
                    'type'=>2,
                    'account_pay_id'=>['neq',0]
                ],
                [
                    'type'=>['neq',2]
                ],
                '_logic'=>'or'
            ],
            'type'=>['neq',105]
        ];

        $lists=M('order_pay_history')->field('sum(pay_amount) as total,order_sn,(select pay_type from dx_order where dx_order.order_sn = dx_order_pay_history.order_sn) as pay_type')->where($where)->select();
        print_r($lists);

        $erpPays=M('order_sync_hy')->field('sum(fcxacount) as fcxacount_total')->where(['order_no'=>['in',$orderSn_arr]])->select();
        print_r($erpPays);


        $pay=M('order_pay_history')->field('sum(pay_amount) as pay_amount_total')->where(['order_sn'=>$res[ 'order_sn' ]])->find();
        $pay['pay_amount_total']=$pay['pay_amount_total']?:0;
        $erpPay=M('order_sync_hy')->field('sum(fcxacount) as fcxacount_total')->where(['order_no'=>$res[ 'order_sn' ]])->find();
        $erpPay['fcxacount_total']=$erpPay['fcxacount_total']?:0;




        if($data['pay_type']==2){
            //账期支付
            $pay1=M('order_pay_history')->where(['order_sn'=>$res['order_sn']])->select();
            $my=0.00;
            if ($pay1){
                foreach ($pay1 as $p1){
                    if($p1['type']!=2){
                        $my+=$p1['pay_amount'];
                    }else{
                        if ($p1['account_pay_id']!=0){
                            $my+=$p1['pay_amount'];
                        }
                    }
                }
            }
            if($my>$erpPay['fcxacount_total']){
                $order_has_pay=$my;
            }else{
                $order_has_pay=$erpPay['fcxacount_total'];
            }
            //$order_has_pay=$erpPay['fcxacount_total'];
        }else{
            if($erpPay['fcxacount_total']>$pay['pay_amount_total']){
                $order_has_pay=$erpPay['fcxacount_total'];
            }else{
                $order_has_pay=$pay['pay_amount_total'];
            }
        }



    }
	/**
	 * @desc 定单价格重置
	 * @param orderSn  定单编号
	 * @param  sys_uid  操作人
	 * @param  $pId_change  改价的产品
	 */
	public function orderPriceReset($request){
		$orderSn=$request['orderSn'];
		$sys_uid=session('adminId');
		$pId_change=$request['pId_change'];
		
		$list=$this->orderList(['order_sn'=>$orderSn,'order_status'=>0,'pay_status'=>0]);
		if($list['error']!==0) return ['error'=>1,'msg'=>'定单信息错误'];
		//更新价格 客户订单编号  产品物料  不存在 同步状态  成功状态  取消状态
		$syncOrder=M('user_handle_history','sys_')->where(['index'=>$orderSn])->find();
		
		if( $syncOrder&&($syncOrder['sync_status']==0 || $syncOrder['sync_status']==1||$syncOrder['sync_status']==3)){
			return ['error'=>1,'msg'=>'订单状态不可以同步'];
		}
		//订单同步 或者 换型号时  不存在同步状态  同步中  同步失败  订单取消的  换型号中
		$sorder=M('order_sync')->where(['order_sn'=>$orderSn])->find();
		if(!$sorder||$sorder['sync_status']==0 ||$sorder['sync_status']==2 || $sorder['sync_status']==4){
			return ['error'=>1,'msg'=>'订单状态不可以同步'];
		}
		
		$goodsM=M('order_goods');
		$goodsM->startTrans();
		$order_total=0;
		foreach($pId_change as $k=>$v){
			if(!$v['p_price_true']||!$v['p_id']||!$v['p_num']) return ['error'=>1,'msg'=>'param:error'];
			$one_where=[
				'order_sn'=>$orderSn,
				'p_id'=>$v['p_id'],
			];
			$one_goods_data=[
				'p_price_true'=>$v['p_price_true'],
				'p_sign_sale'=>$v['p_sign_sale']?$v['p_sign_sale']:'',
				'pay_subtotal'=>$v['p_price_true']*$v['p_num'],
			];
			$one_result=$goodsM->where($one_where)->save($one_goods_data);
			if($one_result===false){
				$goodsM->rollback();
				return ['error'=>1,'msg'=>'failed'];
			}
			
			$order_total+=$one_goods_data['pay_subtotal'];
		}
		$order_result=M('order')->where(['order_sn'=>$orderSn])->save(['total'=>$order_total,'order_sn_sale'=>$request['order_sn_sale']?$request['order_sn_sale']:'']);
		
		if($order_result===false){
			$goodsM->rollback();
			return ['error'=>1,'msg'=>'failed'];
		}
		
		$data=[//操作记录
			   'type'=>'order_price',
			   'index'=>$orderSn,
			   'sys_uid'=>$sys_uid,
			   'data_old'=>json_encode($list),
			   'request'=>json_encode($request)
		];
		$AdminHandleHistory_result=D('AdminHandleHistory')->add($data,$syncOrder);
		if($AdminHandleHistory_result['error']!==0){
			$goodsM->rollback();
			return ['error'=>1,'msg'=>'记录失败'];
		}
		
		$key = C('REDIS_KEY.syncOrderInfo_key')?C('REDIS_KEY.syncOrderInfo_key'):'shopOrderSyncInfo';
		$res = Redis::getInstance()->sAdd( $key, $orderSn);
		if($res===false){
			$goodsM->rollback();
			return ['error'=>1,'msg'=>'已经存在同步队列中,操作失败'];
		}
		
		$goodsM->commit();
		return ['error'=>0,'msg'=>'success'];
	}

}