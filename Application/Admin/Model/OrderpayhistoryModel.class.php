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

class OrderpayhistoryModel extends BaseModel
{

    protected $tableName = 'order_pay_history';
    /**
     *
     * @desc  支付历史列表
     * dx_order_pay_img
     *
     */
    public function payHistoryList($where='',$page='',$pageSize='',$order='order_sn desc,pay_time asc',$field=''){
        $list=$this->baseList(M('order_pay_history'),$where,$page,$pageSize,$order,$field);
        return $list;
    }

     /**
     *
     * @desc 水单列表
     * dx_order_pay_img
     *
     */
     public function payImgList($where='',$page='',$pageSize='',$order='',$field='',$table_arr=[]){
         $must_where=[];
         $method_name=MODULE_NAME;
         $key_method=strtolower($method_name);
         $session=session();
         if($key_method=='admin'){
             $userM=new UserModel();
             $productPowers=$userM->departmentDataPower('order',$session['adminInfo']['department_id'],$where,'sys_uid');
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

         $where[]=['is_del'=>1];

         $list=$this->baseList(M('order_pay_img'),$where,$page,$pageSize,$order,$field);
         if($list['error']==0){
             $userId_arr=$adminId_arr=$accountPayHistory_arr=[];
             foreach($list['data']['list'] as $k=>$v){
                 $userId_arr[]=$v['user_id'];
                 $adminId_arr[]=$v['sys_uid'];
                 $accountPayHistory_arr[]=$v['account_pay_history_id'];
             }
             $accountPayHistory_arr=array_unique($accountPayHistory_arr);
             if(in_array('dx_order_pay_history',$table_arr)){
                 $payHistory=$this->baseListType(M('order_pay_history'),$accountPayHistory_arr,'id');
             }
             $accountPayHistory_arr=array_unique($accountPayHistory_arr);
             $accountPayHistory=$this->baseListType(M('account_pay_history'),$accountPayHistory_arr,'id');
             $user=$this->baseListType(M('user'),$userId_arr,'id');
             $admin=$this->baseListType(M('user','sys_'),$adminId_arr,'uid');
             foreach($list['data']['list'] as $k=>$v){
                 $list['data']['list'][$k]['user_name']=$user['data']['list'][$v['user_id']]['fcustjc'];
                 $list['data']['list'][$k]['sale_name']=$admin['data']['list'][$v['sys_uid']]['user_name'];
                 $list['data']['list'][$k]['order_sn']=$v['order_sn']?$v['order_sn']:$accountPayHistory['data']['list'][$v['account_pay_history_id']]['account_pay_sn'];
                 if($v['pay_type']==0&&in_array('dx_order_pay_history',$table_arr)){
                     $list['data']['list'][$k]['check_amount']=$payHistory['data']['list'][$v['account_pay_history_id']]['pay_amount'];
                 }
             }
         }
         return $list;
     }

    /**
     *
     * @desc 支付水单上传
     * dx_order_pay_img
     * 只有银行转账需要水单
     *
     */
    public function orderPayImg($request){
        if( !$request['pay_img'] || !in_array($request['img_type'],[1,2]) ) return ['error'=>1,'msg'=>'参数错误'];

        $where=[
            'order_sn'=>$request['order_sn'],
            'user_id'=>$request['user_id'],
        ];
        if($request['img_type']==1) $where['deposits_pay_type']=5;//定金
        else if($request['img_type']==2) $where['pay_type']=5; //尾款

        $list=$this->baseList(M('order'),$where);
        if($list['error']!=0) return ['error'=>1,'msg'=>'订单信息错误'];

        $data=[
            'img_type'=>$request['img_type'],
            'order_sn'=>$request['order_sn'],
            'user_id'=>$request['user_id'],
        ];

        $m=M('order_pay_img');
        $m->startTrans();

//        $result1=$m->where($data)->delete();
        $data['pay_img']=$request['pay_img'];
        $result2=$m->add($data);

        if($result2){
            $m->commit();
            return ['error'=>0,'msg'=>'保存成功'];
        }else{
            $m->rollback();
            return ['error'=>1,'msg'=>'保存失败'];
        }
    }

    /**
     *
     * @desc 水单审核
     * 5银行转账才要水单审核
     */
    public function payImgAction($request)
    {
        if (!in_array($request['status'], [1, 2])) return ['error' => 1, 'msg' => '参数错误'];
        $payImg = $this->baseList(M('order_pay_img'), ['id' => $request['id'],'is_del'=>1]);
        if ($payImg['error'] != 0) return $payImg;
        $img = $payImg['data']['list'][0];
        if ($img['status'] == 2) return ['error' => 1, 'msg' => '已通过审核的不能再次审核'];

        $dx_account_pay_history_step=1;
        if($img['pay_type']==1){//帐期还款
            $account_pay=$this->baseList(M('account_pay_history'),['id'=>$img['account_pay_history_id'],'step'=>1,'is_del'=>1]);
            if($account_pay['error']!==0) return ['error'=>1,'msg'=>'帐期还款信息错误'];
            $account_info=$account_pay['data']['list'][0];
            $account_has_pay=M('order_pay_img')->field('sum(check_amount) as pay_sum')->where(['account_pay_history_id'=>$img['account_pay_history_id']])->find();
            $pay_sum=(float)$account_has_pay['pay_sum'];
            if((int)($request['pay_amount']*100)>=(int)(($account_info['total']-$pay_sum)*100)){
                $dx_account_pay_history_step=2;
            }
        }

        $data = [
            'status' => $request['status'],
            'check_amount' => $request['pay_amount'],
            'check_time' => date('Y-m-d H:i:s', time()),
            'sys_uid' => session('adminId')
        ];

        $m = M('order_pay_img');
        $m->startTrans();

        if($dx_account_pay_history_step===2){//帐期
            $account_result=M('account_pay_history')->where(['id'=>$img['account_pay_history_id'],'step'=>1,'is_del'=>1])->save(['step'=>2]);
            $account_result3=M('order')->where(["order_sn in (select order_sn from dx_order_pay_history where account_pay_selected=$img[account_pay_history_id])"])->save(['pay_status'=>2]);
            $account_result2=M('order_pay_history')->where(['account_pay_selected'=>$img['account_pay_history_id']])->save(['account_pay_id'=>$img['account_pay_history_id'],'account_pay_selected'=>0]);
            if(!$account_result||!$account_result2||!$account_result3){
                $m->rollback();
                return ['error' => 1, 'msg' => '审核失败1'];
            }
        }

        $result = $m->where(['id' => $request['id']])->save($data);
        if ($result === false) {
            $m->rollback();
            return ['error' => 1, 'msg' => '审核失败'];
        }
        if ($request['status'] == 1) {//审核不通过
            $m->commit();
            return ['error' => 0, 'msg' => '审核成功'];
        } else if ($request['status'] == 2) {//审核通过付款成功
            $request['pay_type'] = 5;//银行转账才要水单审核
            $request['img_type']=$img['img_type'];

            if($img['pay_type']==0){
                $pay_result = $this->userOrderPayHistoryAdd($request,false);
                if ($pay_result['error'] != 0) {
                    $m->rollback();
                    return ['error' => 1, 'msg' => $pay_result['msg']];
                }

                $result5 = $m->where(['id' => $request['id']])->save(['account_pay_history_id' => $pay_result['data']]);
                if($result5===false){
                    $m->rollback();
                    return ['error' => 1, 'msg' => '审核失败'];
                }
            }
            $ordersave=orderStatus($request['order_sn']);
            if( !empty($ordersave) && is_array($ordersave) ){
                $result_order=M('order')->where(['order_sn'=>$request['order_sn']])->save($ordersave);
                if($result_order===false){
                    $m->rollback();
                    return ['error'=>1,'msg'=>'审核失败'];
                }
            }

            $m->commit();
            return ['error' => 0, 'msg' => '审核成功'];
        }
    }

    /**
     *
     * @desc 水单审核,添加定单支付记录
     *
     */
    public function userOrderPayHistoryAdd($request,$is_startTrans=true){
        if(!$request['pay_amount']) return ['error'=>1,'msg'=>'参数错误2'];

        $order_where= [
            'order_sn'=>$request['order_sn'],
            'pay_status'=>['neq',2]
        ];
        if($request['img_type']==1){//定金
            $order_where['deposits_pay_type']=5;
            $order_where['order_type']=1;
            $order_where['deposits_pay_status']=0;
            $order_where['total_deposits']=['gt',0];
        }else if($request['img_type']==2){//尾款
            $order_where['pay_type']=5;
        }

        $order=$this->baseList(M('order'),$order_where);
        if($order['error']!=0) return ['error'=>1,'msg'=>'订单信息错误'];
        $oneOrder=$orderInfo=$order['data']['list'][0];
        $pay_type_config=C('PAY_TYPE');

        if($request['img_type']==1){//定金
            $hasPayDeposits=M('order_pay_img')->field('sum(pay_amount) as amount_count')->where(['order_sn'=>$request['order_sn'],'pay_type'=>0,'img_type'=>1,'status'=>2])->find();
            $hasPayDeposits=$hasPayDeposits['amount_count']?:0;

            if($orderInfo['total_deposits']<$hasPayDeposits){
                return ['error'=>1,'msg'=>'定金已全部支付完成'];
            }

            $orderChange=[
                'already_paid'=>$request['pay_amount']+$orderInfo['already_paid'],
                'pay_status'=>1,
                'order_status'=>2
            ];

            if($request['pay_amount']+$hasPayDeposits>$orderInfo['total_deposits']){//本次支付定金支付完全
                //更新的订单数据
                $orderChange['deposits_pay_status']=1;
            }

        }else if($request['img_type']==2){//尾款

            $orderPayHistory=M('order_pay_history')->field('sum(pay_amount) as amount_count')->where(['order_sn'=>$request['order_sn']])->find();
            $amount_count=(float)$orderPayHistory['amount_count'];

            $orderEnd=D('Admin/Order')->orderPayTotalEnd([$request['order_sn']]);
            if($orderEnd['error']!==0) return $orderEnd;
            $totalEnd=$orderEnd['data'][$request['order_sn']]['totalEnd'];

            $pay_status=1;

            $amount_count_end=round($totalEnd-$amount_count,2);
            if((int)($request['pay_amount']*100)>(int)(100*($amount_count_end))) return ['error'=>1,'msg'=>"支付金额已超过定单未付金额,定单未付金额:".($totalEnd-$amount_count)];
            if((float)$request['pay_amount']==(float)($totalEnd-$amount_count)) $pay_status=2;

            //更新的订单数据
            $orderChange=[
                'order_status'=>2,
                'pay_status'=>$pay_status,
                'already_paid'=>$request['pay_amount']+$oneOrder['already_paid']
            ];
            $orderChange2=orderStatus($request['order_no']);
            !empty($orderChange2) && is_array($orderChange2) && $orderChange=array_merge($orderChange,$orderChange2);
        }


        $payHistory=[//更新的支付数据
             'order_sn'=>$oneOrder['order_sn'],
             'order_total'=>$oneOrder['total'],
             'pay_amount'=>$request['pay_amount'],
             'type'=>$request['pay_type'],
             'pay_name'=>$pay_type_config[$request['pay_type']],
             'sys_uid'=>session('adminId'),
        ];

        $m=M('order_pay_history');
        if($is_startTrans) $m->startTrans();
        $result2=M('order')->where(['order_sn'=>$oneOrder['order_sn']])->save($orderChange);
        if($result2===false){
            if($is_startTrans) $m->rollback();
            return ['error'=>1,'msg'=>'订单数据更新失败'];
        }
        $result3=$m->add($payHistory);
        if(!$result3){
            if($is_startTrans) $m->rollback();
            return ['error'=>1,'msg'=>'支付记录保存失败'];
        }
		$ordersave=orderStatus($request['order_no']);
        if( !empty($ordersave) && is_array($ordersave) ){
			$result4=M('order')->where(['order_sn'=>$oneOrder['order_sn']])->save($ordersave);
			if($result4===false){
				if($is_startTrans) $m->rollback();
				return ['error'=>1,'msg'=>'更新订单支付状态失败'];
			}
		}
		if($is_startTrans) $m->commit();
		return ['error'=>0,'msg'=>'支付成功','data'=>$result3];
    }

    /**
     *
     * @desc 后台添加支付
     *
     */
    public function adminToPay($request){
        if(!$request['pay_amount']) return ['error'=>1,'msg'=>'参数错误2'];
        $order=$this->baseList(M('order'),['order_sn'=>$request['order_sn']]);
        if($order['error']!=0){
            return ['error'=>1,'msg'=>'订单信息错误'];
        }
        $oneOrder=$order['data']['list'][0];
        $pay_type=C('PAY_TYPE');

        $orderChange=[];
        if($oneOrder['order_type']==1){//定金订单支付
            if($oneOrder['deposits_pay_status']==0){
                if( (float)round($request['pay_amount'],2) != (float)round($oneOrder['total_deposits'],2) ){
                    return ['error'=>1,'msg'=>'定金金额不对'];
                }
                $orderChange['deposits_pay_status']=1;//定金支付状态
                $type=$oneOrder['deposits_pay_type'];// 支付方式
                if( $oneOrder['pay_type']==2 ) $pay_status=2;
                else $pay_status=1;
                if(!in_array($type,[4,5,6])) return ['error'=>1,'msg'=>'定金支付方式不对'];
            }else{
                return ['error'=>1,'msg'=>'定金已付'];
//                if((int)($oneOrder['already_paid']*100)+(int)($request['pay_amount']*100)!=(int)($oneOrder['total']*100)) return ['error'=>1,'msg'=>'尾款金额不对'];
//                if($oneOrder['pay_type']!=5) return ['error'=>1,'msg'=>'尾款支付方式不对5'];//只有银行转账的才可以在这里付尾款
            }
        }else{//全款订单支付
            return ['error'=>1,'msg'=>'只收定金'];
//            if((int)($request['pay_amount']*100)!=(int)($oneOrder['total']*100)) return ['error'=>1,'msg'=>'金额不对'];
//            $type=$oneOrder['pay_type'];// 支付方式
//            $pay_status=2;
//            if($oneOrder['pay_type']!=5) return ['error'=>1,'msg'=>'全款支付方式不对5'];//只有银行转账的才可以在这里付尾款
        }

        //更新的订单数据
        $orderChange['order_status']=2;
        $orderChange['pay_status']=$pay_status;
        $orderChange['already_paid']=$request['pay_amount']+$oneOrder['already_paid'];

        $payHistory=[//更新的支付数据
            'order_sn'=>$oneOrder['order_sn'],
            'order_total'=>$oneOrder['total'],
            'pay_amount'=>$request['pay_amount'],
            'type'=>$type,
            'pay_name'=>$pay_type[$type],
            'sys_uid'=>session('adminId'),
        ];

        $m=M('order_pay_img');
        $m->startTrans();

        if(($oneOrder['order_type']==0)||($oneOrder['order_type']==1&&$oneOrder['deposits_pay_status']==0)){//全款订单//定金支付完成
            $toErp=(new \Home\Model\ErpModel())->orderToErp($oneOrder['order_sn']);//订单同步到erp
            if($toErp['error']!=0){
                $m->rollback();
                return $toErp;
            }
            $storeResult=$this->productStoreAction($oneOrder['order_sn']);//更新库存
            if($storeResult['error']!=0){
                $m->rollback();
                return $storeResult;
            }
        }

        $result2=M('order')->where(['order_sn'=>$oneOrder['order_sn']])->save($orderChange);
        if($result2===false){
            $m->rollback();
            return ['error'=>1,'msg'=>'订单数据更新失败'];
        }
        $result3=M('order_pay_history')->add($payHistory);
        if(!$result3){
            $m->rollback();
            return ['error'=>1,'msg'=>'支付记录保存失败'];
        } else {
            $m->commit();
            return ['error'=>0,'msg'=>'支付成功'];
        }
    }

    /**
     *
     * @desc 更新商品库存
     *
     */
    public function productStoreAction($order_sn){
        $list=$this->baseList(M('order_goods'),['order_sn'=>$order_sn]);
        if($list['error']!=0) return $list;
        $data=$list['data']['list'];
        $fitmeno_arr=$pId_arr=[];
        foreach($data as $k=>$v){
            $fitmeno_arr[]=$v['fitemno'];
            $pId_arr=$v['p_id'];
        }

        $erp=$this->baseListType(M('product','erp_'),$fitmeno_arr,'ftem');
        if($erp['error']!=0) return $erp;
        $erpProduct=$erp['data']['list'];

        $shop=$this->baseListType(M('product'),$pId_arr,'id');
        if($erp['error']!=0) return $erp;
        $shopProduct=$shop['data']['list'];

        $m=M('product');
        $m->startTrans();
        $erpM=M('product','erp_');
        $error=0;
        foreach($data as $k=>$v){
            $erpChange=[
                'id'=>$erpProduct[$v['ftem']]['id'],
                'store'=>$erpProduct[$v['ftem']]['store']-$v['p_num'],
            ];
            if(($erpM->save($erpChange))===false){
                $error=1;
                break;
            }
            $shopChange=[
                'id'=>$v['p_id'],
                'sell_num'=>$shopProduct[$v['p_id']]['sell_num']+$v['p_num'],
            ];
            if(($m->save($shopChange))===false){
                $error=2;
                break;
            }
        }

        if($error){
            $m->rollback();
            return ['error'=>1,'msg'=>'库存更新失败'];
        }else{
            $m->commit();
            return ['error'=>0,'msg'=>'库存更新成功'];
        }
    }

    /**
     * @desc 待确认帐期还款列表
     */
    public function accountPayList($where,$page,$pageSize){
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
                $must_where['u.user_id']=['in',$customers['data']];
            }
            //财务部门
            $productPowers=$userM->departmentDataPower('money',$session['adminInfo']['department_id'],$where,'sys_uid');
            if($productPowers['error']!=0) return $productPowers;
            if(isset($productPowers['data']['must_where'])){
                $customers=$userM->adminSaleCustomerPower($productPowers['data']['must_where'],'id');
                if($customers['error']!=0) return $customers;
                $must_where['u.user_id']=['in',$customers['data']];
            }
        }

        $must_where=array_merge($must_where,$where);
        $account_selected=M('order')->alias('u')
            ->field('oph.account_pay_selected,u.order_sn')
            ->join('left join dx_order_pay_history as oph on oph.order_sn=u.order_sn')
            ->where(array_merge($must_where,['oph.type'=>2]))
            ->order('oph.account_pay_selected desc')
            ->limit(($page-1)*$pageSize,$pageSize)
            ->select();
        if(empty($account_selected)){
            return ['error'=>1,'msg'=>'没有数据'];
        }
        $order_arr=$accountSelected_arr=[];
        foreach($account_selected as $k=>$v){
            $order_arr[]=$v['order_sn'];
            $accountSelected_arr[$v['order_sn']]=$v['account_pay_selected'];
        }

        $account_count=M('order')->alias('u')
            ->field('oph.account_pay_selected')
            ->join('left join dx_order_pay_history as oph on oph.order_sn=u.order_sn')
            ->where(array_merge($where,['oph.type'=>2]))
            ->count();

        $order=D('admin/order')->orderList(['order_sn'=>['in',$order_arr]],'','');

        foreach($order['data']['list'] as $k=>$v){
            $order_sort[]=$order['data']['list'][$k]['account_pay_selected']=$accountSelected_arr[$v['order_sn']]?$accountSelected_arr[$v['order_sn']]:0;
        }
        $order['data']['count']=$account_count;
        return $order;
    }

    //Event //取出数据
    public function toOrderInfo($where='',$field='',$limit=[0,10]){
        $list=M('order_pay_history')
            ->field($field)
            ->where($where)
            ->select();
        $this->list=$list;
        return $list;
    }

    /**
     * 订单数据格式化
     * @param array $name 格式化类型
     * $is_list 多个字段信息转换成一个信息,是否可换型号
     * @return array
     */
    public function toOrderType($is_list=true,$type=[]){
        if($this->list){
            $newList=[];
            foreach($this->list as $k=>$v){
                $newList[$v[$type[1]]][]=$v;
            }
            $this->list=$newList;
        }
        return $this;
    }



}