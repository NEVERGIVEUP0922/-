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

class KnotOrderLogic extends BaseLogic
{
    public $limit='0,10';
    public $user_id;
    public $user_name;

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 结单,退贷退款
     *
     */
    public function knotOrder($request){
//        $request['order_sn']='1809281140562';
//        $request['re_sn']='20181010180465872';
//        $request['action']='retreatDetail';
//        $request['relation']='retreatDetail';
        $this->user_id=$request['user_id'];
        $this->user_name=$request['user_name'];

        if($request['action']=='refundOrder'){//退贷退款
            $result=$this->refundOrderAction($request);
        }else if($request['action']=='cancelRetreat'){//取消申请
//            $order=M('order')->field('id')->where(['pay_status'=>['in',[1,2]],'pay_type'=>1,'order_sn'=>$request['order_sn']])->find();
//            if(!$order) return ['error'=>-1,'msg'=>'定单信息不对'];
            $result=$this->cancelRetreatAction($request);
        }else if($request['action']=='storeWriteDelivery'){//处理买家退货填写的物流信息
            $result=$this->storeWriteDelivery($request);
        }else if($request['action']=='retreatDetail'){//处理买家退货填写的物流信息
            $result=$this->retreatDetail($request);
        }else{//结单
            $result=$this->knotOrderAction($request);
        }
        return $result;
    }
    /**
     * @desc 退货退款详情
     *
     */
    public  function retreatDetail($request){
        $where['order_sn']=$request['order_sn'];
        $where['re_sn']=$request['re_sn'];

        $relation=$request['relation'];
        $m=D('OrderRetreat');
        $m->where($where);
        $relation_where=$request['relation_where']?$request['relation_where']:'';
        if($request['relation']) $m->$request['relation']($relation_where)->relation(true);
        $data['list']=$m->select();
        foreach ($data['list'] as &$v){
            $v['delivery_info']=[];
            if(isset($v['re_delivery_num'])&&$v['re_delivery_num']){
                $kdSn_arr=[$v['re_delivery_num']];
                $KDList=D('Small/KD','Logic')->KDList($kdSn_arr);
                $v['delivery_info']=$KDList['data'][$v['re_delivery_num']]['Traces']?$KDList['data'][$v['re_delivery_num']]['Traces']:[];
            }
            foreach ($v['order_retreat_goods'] as &$value){
                $value['cover_img']='';
                $packageArr=M('product')->alias('p')->field("img")->join("dx_product_package_img as pimg on p.package=pimg.package")->where(['p.id'=>$value['p_id']])->find();
                if($packageArr && $packageArr['img']){
                    $value['cover_img']=$packageArr['img'];
                }
                foreach ($v['order_goods'] as $ovalues){
                    if($ovalues['p_id']==$value['p_id']){
                        $value['good']=$ovalues;
                    }
                }
            }

        }
        return ['error'=>0,'data'=>$data,'msg'=>'success'];
    }
    /**
     * @desc 结单提交
     *
     */
    public function knotOrderAction($request){
        //拷贝home/order/knotOrder
        M()->startTrans();
        $order_sn=$request['order_sn'];
        if(!$order_sn) die(json_encode(['error'=>1,'msg'=>'参数错误']));
        $order_sync=M('order_sync')->where(['order_sn'=>$order_sn,'sync_status'=>['IN',[0,2]]])->find();
        if($order_sync){
            M()->rollback();
            return ['error'=>-1,'msg'=>'请稍后再试'];
        }
        $order=M('order');
        $order_hy=M('order_sync_hy')->where(['order_no'=>$order_sn,'is_recive'=>0])->find();
        if($order_hy){
            M()->rollback();
            return ['error'=>-1,'msg'=>'未确认收货'];
        }
        $res=$order->where(['order_sn'=>$order_sn,'knot'=>0])->find();

        if($res){
            $user_id=$res['user_id'];
            $res=$order->where(['order_sn'=>$order_sn,'knot'=>0])->save(['knot'=>1]);
            if($res){ //写入结单redis knotOrder
                $orderSync = M('knot_order');
                if(!($orderSync->where(['order_sn'=>$order_sn])->find())){
                    $res = $orderSync->add([ 'order_sn'=>$order_sn ]);
                    if($res === false ){
                        M()->rollback();
                        return ['error'=>-1,'msg'=>$order_sn.'----结单信息同步写入数据库失败'];
                    }
                }
                $redis = Redis::getInstance();
                $redRes = $redis->sAdd( 'knotOrder', $order_sn );
                if((int)$redRes===0){
                    M()->rollback();
                    die(json_encode(['error'=>-1,'msg'=>'该订单正在结单中......']));
                }
                M()->commit();
                \Admin\Controller\MsgController::writeMsgToUserSale( $user_id, '客户取消订单'.$order_sn, '订单编号:'.$order_sn.'{}取消时间:'.date('Y-m-d H:i:s') );
                return ['error'=>0,'msg'=>'结单提交成功'];
            }else{
                M()->rollback();
                return ['error'=>-1,'msg'=>'结单失败'];
            }
        }else{
            M()->commit();
            return ['error'=>-1,'msg'=>'结单信息错误'];
        }
    }

    /**
     * @desc 退贷退款提交
     *
     */
    public function refundOrderAction($request){
        //拷贝\Home\Controller\RetreatController
                //处理退款申请
                $re_sn =$request['re_sn']?:null;
                //订单编号
                $order_sn =$request['order_sn'];
                //退款退货商品数据
                $goods = $request['goods'];
                foreach ($goods as $kg=>$kv){
                    if($kv['pnum']==0){
                        unset($goods[$kg]);
                    }
                }
                if(!$goods){
                    return ['error'=>-1,'msg'=>'申请的退款退货商品数量异常!'];
                }
                //退款方式
                $retreat_type = $request['retreat_type'];
                //货物状态
               // $cargo_status = $request['cargo_status'];
                $cargo_status = $retreat_type==1?0:2;
//            if ( $retreat_type == 1 ) { //退货类型为退款退货  cargo_status 货物状态项数据无效
//                $cargo_status = ;
//            }
                //申请退款金额
                $retreat_money =$request['retreat_money'];
                //申请退款说明
                $retreat_desc = $request['retreat_desc'];
                //申请退款说明图片
                $retreat_img = !empty( $request['retreat_img'] ) ? json_encode( $request['retreat_img'] ) : '';
                //用户id
                $user_id =$request['user_id'] ;
                $order = M( 'order' );
                $reM = 0;
                $orderInfo = $order->where( ['order_sn'=>$order_sn] )->find();
                //开启事务

                M()->startTrans();
                //修改退款申请
                if ( $re_sn ) {
                    $save = [
                        're_sn'         => $re_sn,
                        'order_sn'      => $order_sn,
                        'user_id'       => $user_id,
                        'retreat_type'  => $retreat_type,
                        'cargo_status'  => $cargo_status,
                        'retreat_money' => $retreat_money,
                        'retreat_desc'  => $retreat_desc,
                        'retreat_img'   => $retreat_img,
                        'handle_status' => 1,
                    ];
                    $cz= M( 'order_retreat' )->where( [ 're_sn' => $re_sn,'handle_status' =>['not in',[3,7]]] )->find();
                    $update = M( 'order_retreat' )->where( [ 're_sn' => $re_sn ] )->save( $save );
                    if($update===false){
                        M()->rollback();
                        return ['error'=>-1,'msg'=>'操作异常!'];
                    }
                    //判断退款金额是否正常
                    $rmoney=M('order_retreat')->field("sum(retreat_money) as remoney")->where("order_sn='{$order_sn}' and handle_status not in (3,7)")->find();
//				if($orderInfo['pay_type']==1){
//					$hymoney['hym']=$orderInfo['total'];
//				}else{
//					$hymoney=M('order_sync_hy')->field("sum(fcxacount) as hym")->where("order_no='{$order_sn}'")->find();
//					if($hymoney['hym']===null){
//						$hymoney['hym']=0.0;
//					}
//				}

                    $pay=M('order_pay_history')->field('sum(pay_amount) as pay_amount_total')->where(['order_sn'=>$order_sn])->find();
                    $pay['pay_amount_total']=$pay['pay_amount_total']?:0;
                    $erpPay=M('order_sync_hy')->field('sum(fcxacount) as fcxacount_total')->where(['order_no'=>$order_sn])->find();
                    $erpPay['fcxacount_total']=$erpPay['fcxacount_total']?:0;
                    if($orderInfo['pay_type']==2){
                        $pay1=M('order_pay_history')->where(['order_sn'=>$order_sn])->select();
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

                    if($rmoney['remoney']>$order_has_pay){
                        M()->rollback();
                        return ['error'=>-1,'msg'=>'申请的退款金额异常!'];
                    }
                    //判断退货产品申请数量 是否正常
                    $goodsInsert = $vmIns = [];
                    foreach ( $goods as $k => $v ) {
                        $no_goods[]=$v['pid'];
                        $good = M( 'order_goods' )->where( [ 'order_sn' => $order_sn, 'p_id' => $v[ 'pid' ],'pay_subtotal'=>['gt',0] ] )->find();
                        if($cz){
                            $renum=M('order_retreat_goods')->field('p_num')->where(['re_sn'=>$re_sn,'p_id'=>$v['pid']])->find();
                        }else{

                            $renum['p_num']=0;
                        }


                        $num = $good[ 'erp_num' ];
                        $retreat_num = $good[ 'retreat_num' ];
                        if ( $num - ($retreat_num-$renum['p_num']) < $v[ 'pnum' ] ) {
                            M()->rollback();
                            return ['error'=>-1,'msg'=>'申请的退款退货商品数量异常!'];
                        } else {
                            $goodsInsert[] = [
                                're_sn'    => $re_sn,
                                'order_sn' => $order_sn,
                                'p_id'     => $v[ 'pid' ],
                                'p_num'    => $v[ 'pnum' ],
                                'p_price'  => $v[ 'pprice' ],
                            ];
                        }
                        //单个退款商品的实际金额 = (产品购买单价*产品退款数量) - (((产品购买单价*产品退款数量)/订单应付总额:包含积分抵扣的总金额)*积分抵扣金额)
                        //$p_reM = ( (float)$v['pid'] * (int)$v['pnum'] ) - ((( (float)$v['pid'] * (int)$v['pnum'] )/(float)$orderInfo['total_origin'] )*(float)$orderInfo['total_discount']);
                        // $reM += $p_reM;

                        //查询原来申请退货的产品的数量
                        if(!$cz){
                            $oldGood = M( 'order_retreat_goods' )->where( [ 're_sn' => $re_sn , 'p_id'=>$v['pid']] )->find();
                            $reNum = $retreat_num+$v['pnum'];
                        }else{
                            $oldGood = M( 'order_retreat_goods' )->where( [ 're_sn' => $re_sn , 'p_id'=>$v['pid']] )->find();
                            $reNum = $retreat_num-$oldGood['p_num']+$v['pnum'];
                        }
//                    $oldGood = M( 'order_retreat_goods' )->where( [ 're_sn' => $re_sn , 'p_id'=>$v['pid']] )->find();
//                    $reNum = $retreat_num-$oldGood['p_num']+$v['pnum'];
                        $orderGoods = M('order_goods')->where( ['order_sn'=>$order_sn, 'p_id'=>$v['pid'],'pay_subtotal'=>['gt',0] ] )->save(['retreat_num'=>$reNum]);
                        if( $orderGoods === false ){
                            M()->rollback();
                            return ['error'=>-1,'msg'=>'退款申请创建失败!订单数据处理错误!'];
                        }
                        //新
                        $goodsVm =M('product_fitemno')->field('person_liable as vm_id')->where(['p_sign'=>$good['p_name'],'fitemno'=>$good['fitemno']])->find();
                        //查询产品vm
                        //$goodsVm = M('product')->field('person_liable as vm_id')->where(['id'=>$v[ 'pid' ]])->find();
                        $vmInfo = M('','sys_user')->field('uid as vm_id,FEmplName as vm_name')->where(['uid'=>$goodsVm['vm_id']])->find();
                        $vmIns[]= [
                            're_sn'=>$re_sn,
                            'p_id'=>$v['pid'],
                            'vm_id'=>$vmInfo['vm_id'],
                            'vm_name'=>$vmInfo['vm_name'],
                        ];

                    }
                    if($no_goods){
                        $no_re=M( 'order_retreat_goods' )->where( [ 're_sn' => $re_sn , 'p_id'=>['not IN',$no_goods]] )->select();
                        foreach ($no_re as $no_v){

                            if($cz){
                                $no_good = M( 'order_goods' )->where( [ 'order_sn' => $order_sn, 'p_id' => $no_v[ 'p_id' ],'pay_subtotal'=>['gt',0] ] )->find();
                                $reNum = $no_good['retreat_num']-$no_v['p_num'];
                                $no_goodsres=M( 'order_goods' )->where( [ 'order_sn'=>  $order_sn,  'p_id'=>$no_v[ 'p_id' ],'pay_subtotal'=>['gt',0] ] )->save(['retreat_num'=>$reNum]);
                                if( $no_goodsres === false ){
                                    M()->rollback();
                                    return ['error'=>-1,'msg'=>'退款申请创建失败!订单数据更新失败!'];
                                }
                            }
                        }
                    }
                    if($_SESSION['userInfo']['user_type']==20){
                        $pid=M('user_son')->field('p_id')->where(['user_id'=> $user_id ])->find();
                        //file_put_contents('hh.txt',M()->getLastSql());
                        $yw=M()->query("select su.uid as vm_id,su.FEmplName as vm_name from sys_user su,dx_user du WHERE  du.id={$pid['p_id']} and du.sys_uid=su.uid LIMIT 1");
                        //file_put_contents('hh1.txt',M()->getLastSql());
                        if($yw){
                            $vmIns[]= [
                                're_sn'=>$re_sn,
                                'p_id'=>0,
                                'vm_id'=>$yw[0]['vm_id'],
                                'vm_name'=>$yw[0]['vm_name'],
                            ];
                        }
                    }else{
                        $yw=M()->query("select su.uid as vm_id,su.FEmplName as vm_name from sys_user su,dx_user du WHERE  du.id={$user_id} and du.sys_uid=su.uid LIMIT 1");
                        if($yw){
                            $vmIns[]= [
                                're_sn'=>$re_sn,
                                'p_id'=>0,
                                'vm_id'=>$yw[0]['vm_id'],
                                'vm_name'=>$yw[0]['vm_name'],
                            ];
                        }
                    }


                    //删除原来的产品数据
                    $del = M( 'order_retreat_goods' )->where( [ 're_sn' => $re_sn ] )->delete();
                    //计算的退款金额 小于 用户输入的退款申请金额
//                if( round( $reM,2 ) < round( $retreat_money, 2) ){
//					    $order->rollback();
//	                    $this->error( '申请的退款金额异常!请重新输入' );
//                }
                    //写入退款详细产品数据
                    $goodsUpdate = M( 'order_retreat_goods' )->addAll( $goodsInsert );


                    $del1 = M( 'order_retreat_vm' )->where( [ 're_sn' => $re_sn ] )->delete();
                    //写入vm纪录表
                    $vmInsert = M('order_retreat_vm')->addAll( $vmIns );

                    //记录退款日志
                    $log = $this->retreatLog( $re_sn, 0, 3 ); //记录日志 3 为修改操作
                    if ( $update !== false && $del!==false && $goodsUpdate !== false && $vmInsert !== false && $log&&$del1!==false) {
                        M()->commit();
                        return ['error'=>0,'msg'=>'退款申请创建成功!'];
                    } else {
                        M()->rollback();
                        return ['error'=>-1,'msg'=>'退款交易修改失败!'];
                    }
                } else {
                    //新建退款申请
                    $re_sn = date( 'YmdHi' ) . substr( time(), 5 );
                    $add = [
                        're_sn'         => $re_sn,
                        'order_sn'      => $order_sn,
                        'user_id'       => $user_id,
                        'retreat_type'  => $retreat_type,
                        'cargo_status'  => $cargo_status,
                        'retreat_money' => $retreat_money?:0,
                        'retreat_desc'  => $retreat_desc?:'',
                        'retreat_img'   => $retreat_img,
                        'handle_status' => 1,
                    ];
                    //写入退款申请主表
                    $insert = M( 'order_retreat' )->add( $add );
                    $goodsInsert = $vmIns = [];
                    //判断退货产品申请数量 是否正常
                    $rmoney=M('order_retreat')->field("sum(retreat_money) as remoney")->where("order_sn='{$order_sn}' AND handle_status NOT IN (3,7)")->find();

//				if($orderInfo['pay_type']==1){
//					$hymoney['hym']=$orderInfo['total'];
//				}else{
//					$hymoney=M('order_sync_hy')->field("sum(fcxacount) as hym")->where("order_no='{$order_sn}'")->find();
//					if($hymoney['hym']===null){
//						$hymoney['hym']=0.0;
//					}
//				}
//
//
//				if($rmoney['remoney']>$hymoney['hym']){
//					M()->rollback();
//					$this->error( '申请的退款金额异常!' );
//				}
                    $pay=M('order_pay_history')->field('sum(pay_amount) as pay_amount_total')->where(['order_sn'=>$order_sn])->find();
                    $pay['pay_amount_total']=$pay['pay_amount_total']?:0;
                    $erpPay=M('order_sync_hy')->field('sum(fcxacount) as fcxacount_total')->where(['order_no'=>$order_sn])->find();
                    $erpPay['fcxacount_total']=$erpPay['fcxacount_total']?:0;
                    if($orderInfo['pay_type']==2){
                        $pay1=M('order_pay_history')->where(['order_sn'=>$order_sn])->select();
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

                    if($rmoney['remoney']>$order_has_pay){
                        M()->rollback();
                        return ['error'=>-1,'msg'=>'申请的退款金额异常!'];
                    }

                    foreach ( $goods as $k => $v ) {
                        $good = M( 'order_goods' )->where( [ 'order_sn' => $order_sn, 'p_id' => $v[ 'pid' ],'pay_subtotal'=>['gt',0]  ] )->find();
                        //echo M()->getLastSql();
                        $num = $good[ 'erp_num' ];
                        $retreat_num = $good[ 'retreat_num' ];
                        if ( $num - $retreat_num < $v[ 'pnum' ] ) {
                            M()->rollback();
                            return ['error'=>-1,'msg'=>'申请的退款退货商品数量异常!'];
                        } else {
                            $goodsInsert[] = [
                                're_sn'    => $re_sn,
                                'order_sn' => $order_sn,
                                'p_id'     => $v[ 'pid' ],
                                'p_num'    => $v[ 'pnum' ],
                                'p_price'  => $v[ 'pprice' ],
                            ];
                            //改变发票状态
                            $invoice_result = $this->retreatChangeInvoice( $order_sn, $v[ 'pid' ] );
                            if ( $invoice_result[ 'error' ] !== 0 ) {
                                M()->rollback();
                                return ['error'=>-1,'msg'=>'退款申请创建失败!发票信息处理错误!'];
                            }
                        }
                        //单个退款商品的实际金额 = (产品购买单价*产品退款数量) - (((产品购买单价*产品退款数量)/订单应付总额:包含积分抵扣的总金额)*积分抵扣金额)
//                    $p_reM = ( (float)$v['pid'] * (int)$v['pnum'] ) - ((( (float)$v['pid'] * (int)$v['pnum'] )/(float)$orderInfo['total_origin'] )*(float)$orderInfo['total_discount']);
//                    $reM +=$p_reM;
                        $orderGoods = M('order_goods')->where( ['order_sn'=>$order_sn, 'p_id'=>$v['pid'],'pay_subtotal'=>['gt',0] ] )->save(['retreat_num'=>$retreat_num+$v['pnum']]);
                        if( $orderGoods === false ){
                            M()->rollback();
                            return ['error'=>-1,'msg'=>'退款申请创建失败!订单数据处理错误!'];
                        }

                        //新的
                        $ft=trim($good['fitemno']);
                        $ft = str_replace(array("\r\n", "\r", "\n"), "", $ft);
                        $goodsVm =M('product_fitemno')->field('person_liable as vm_id')->where(['fitemno'=>$ft])->find();
                        //查询产品vm
                        //echo M()->getLastSql();
                        //$goodsVm = M('product')->field('person_liable as vm_id')->where(['id'=>$v[ 'pid' ]])->find();
                        $vmInfo = M('','sys_user')->field('uid as vm_id,FEmplName as vm_name')->where(['uid'=>$goodsVm['vm_id']])->find();
                        $vmIns[]= [
                            're_sn'=>$re_sn,
                            'p_id'=>$v['pid'],
                            'vm_id'=>$vmInfo['vm_id'],
                            'vm_name'=>$vmInfo['vm_name'],
                        ];
                    }
                    if($_SESSION['userInfo']['user_type']==20){
                        $pid=M('user_son')->field('p_id')->where(['user_id'=> $user_id ])->find();
                        $yw=M()->query("select su.uid as vm_id,su.FEmplName as vm_name from sys_user su,dx_user du WHERE  du.id={$pid['p_id']} and du.sys_uid=su.uid LIMIT 1");
                        if($yw){
                            $vmIns[]= [
                                're_sn'=>$re_sn,
                                'p_id'=>0,
                                'vm_id'=>$yw[0]['vm_id'],
                                'vm_name'=>$yw[0]['vm_name'],
                            ];
                        }
                    }else{
                        $yw=M()->query("select su.uid as vm_id,su.FEmplName as vm_name from sys_user su,dx_user du WHERE  du.id={$user_id} and du.sys_uid=su.uid LIMIT 1");
                        if($yw){
                            $vmIns[]= [
                                're_sn'=>$re_sn,
                                'p_id'=>0,
                                'vm_id'=>$yw[0]['vm_id'],
                                'vm_name'=>$yw[0]['vm_name'],
                            ];
                        }
                    }
                    //计算的退款金额 小于 用户输入的退款申请金额
//                if( round( $reM,2 ) < round( $retreat_money, 2) ){
//					$order->rollback();
//                    $this->error( '申请的退款金额异常!请重新输入' );
//                }

                    //退款详细产品写入
                    $goodsInsert = M( 'order_retreat_goods' )->addAll( $goodsInsert );
                    //改变订单状态
                    $change = $order->where( [ 'order_sn' => $add[ 'order_sn' ] ] )->setField( 'is_retreat', 1 );//改变订单状态 有退款
                    //记录退款日志
                    $log = $this->retreatLog( $re_sn, 0, 0 ); //记录日志
                    //写入vm纪录表
                    $vmInsert = M('order_retreat_vm')->addAll( $vmIns );


                    //写入退款同步列表
//                $redis = Redis::getInstance();
//                $redRes = $redis->sAdd( 'retreatOrderSyncList', $re_sn );
                    //执行
                    //print_r($log);
                    if ( $insert!==false && $goodsInsert!==false && $change !== false  && $vmInsert !== false && $log!==false ) {
                        M()->commit();
                        return ['error'=>0,'msg'=>'退款申请创建成功'];
                    } else {
                        M()->rollback();
                        return ['error'=>-1,'msg'=>'退款申请创建失败'];
                    }
                }
    }

    /**
	 * @desc 客户主动撤销退款申请
	 */
    public function cancelRetreatAction($request)
    {
        //拷贝的
        $re_sn = $request['re_sn'];
        $save[ 'handle_status' ] = 7;//状态改为撤销状态
        $save[ 'close_time' ] = time(); //完结退款交易
        $retreat = M( 'order_retreat' );
        $retreat->startTrans();

        $map['re_sn']=$re_sn;
        $map['handle_status']= array('in',[3,7]);
        $r=M('order_retreat')->where($map)->find();

        $re = $retreat->where( [ 're_sn' => $re_sn ] )->save( $save );
        //获取退款订单申请的退货数量 将其恢复
        if(!$r){
            $goods = M( 'order_retreat_goods' )->where( [ 're_sn' => $re_sn ] )->select();
            foreach ( $goods as $k => $v ) {
                //查询旧数据
                $orderG = M( 'order_goods' )->field( 'retreat_num' )->where( [ 'order_sn' => $v[ 'order_sn' ], 'p_id' => $v[ 'p_id' ],'pay_subtotal'=>['gt',0]  ] )->find();
                $res = M( 'order_goods' )->where( [ 'order_sn' => $v[ 'order_sn' ], 'p_id' => $v[ 'p_id' ],'pay_subtotal'=>['gt',0]  ] )->save( [ 'retreat_num' => $orderG[ 'retreat_num' ] - $v[ 'p_num' ] ] );
                if ( $res === false ) {
                    $retreat->rollback();
                    return ['error'=>-1,'msg'=>'系统繁忙!撤销退款申请失败'];
                }
            }
        }
        //退款操作纪录
        $log = $this->retreatLog( $re_sn, 0, 7 );
        if ( $re !== false && $log ) {
            $retreat->commit();
            return ['error'=>0,'msg'=>'撤销退款成功'];
        } else {
            $retreat->rollback();
            return ['error'=>0,'msg'=>'系统繁忙!撤销退款申请失败'];
        }
    }

    /**
     * @desc 处理买家退货填写的物流信息
     */
    public function storeWriteDelivery($request)
    {
        //拷贝的
        $re_sn = $request['re_sn'];
        $save[ 're_delivery_id' ] = $request['re_delivery_id'];
        $save[ 're_delivery_num' ] = $request['re_delivery_num'];
        $save[ 're_delivery_phone' ] = $request['re_delivery_phone'];
        $save[ 're_delivery_desc' ] = $request['re_delivery_desc'];
        $save[ 're_delivery_img' ] = $request['retreat_img'];
        if(empty( $re_sn )) return ['error'=>-1,'msg'=>'参数不正确'];
        if (!$save[ 're_delivery_img' ]){
            return ['error'=>-1,'msg'=>'请上传图片'];
        }
        $save[ 'handle_status' ] = 4;
        $save[ 're_delivery_status' ] = 1;
        $save[ 're_delivery_img' ] && $save[ 're_delivery_img' ] = json_encode( $save[ 're_delivery_img' ] );
        $order_retreat = M( 'order_retreat' );
        $order_retreat->startTrans();
        //更新退款主表
        $re = $order_retreat->where( [ 're_sn' => $re_sn ] )->setField( $save );
        //写入退款记录表
        $log = $this->retreatLog( $re_sn, 0, 4 );

        if ( $re !== false && $log ) {
            $order_retreat->commit();
            return ['error'=>0,'msg'=>'提交成功'];
        } else {
            $order_retreat->rollback();
            return ['error'=>-1,'msg'=>'物流信息提交失败'];
        }
    }

    /**
    * @desc 退货申请检测发票信息
    */
    public function retreatChangeInvoice( $order_sn, $pid )
    {
        //拷贝过来的 home/retreat/retreatChangeInvoice
        $goods_info = D( 'order_goods' )->where( [ 'order_sn' => $order_sn, 'p_id' => $pid ,'pay_subtotal'=>['gt',0] ] )->find();

        if ( !$goods_info ) return [ 'error' => 1, 'msg' => '订单商品信息错误' ];
        if ( !$goods_info[ 'user_invoice_id' ] ) return [ 'error' => 0, 'msg' => '没有发票申请' ];
        $user_invoice = D( 'user_invoice' )->where( [ 'id' => $goods_info[ 'user_invoice_id' ] ] )->find();
        if ( $user_invoice[ 'status' ] == 1 ) return [ 'error' => 0, 'msg' => '发票已经开出' ]; //发票已经开出

        D( 'order' )->startTrans();
        $goods_result = D( 'order_goods' )->where( [ 'user_invoice_id' => $goods_info[ 'user_invoice_id' ] ,'pay_subtotal'=>['gt',0] ] )->save( [ 'user_invoice_id' => 0 ] );
        $invoice_result = D( 'user_invoice' )->where( [ 'id' => $goods_info[ 'user_invoice_id' ] ] )->delete();

        if ( $goods_result && $invoice_result ) {
            D( 'order' )->commit();

            return [ 'error' => 0, 'msg' => '发票更新成功' ];
        } else {
            D( 'order' )->rollback();

            return [ 'error' => 1, 'msg' => '发票更新失败' ];
        }
    }

    /**
     * 退款操作记录日志
     *
     * @param int    $re_sn [退款交易编号]
     * @param int    $user_type [用户类型   0为买家用户 1为玖隆方]
     * @param int    $action_type [操作类型  0为创建退款交易 1为玖隆批准 2为玖隆驳回 3为买家修改退款 4为买家发货 5为玖隆已收货 6为关闭退款交易 7为用户撤销退款 ]
     * @param string $action_desc default ''  [操作补充说明]
     * @return  mixed   true 写入数据成功,返回写入id  false 写入失败
     */
    protected function retreatLog( $re_sn, $user_type, $action_type, $action_desc = '' )
    {
        //拷贝过来的 home/retreat/retreatLog
        $add = [
            're_sn'       => $re_sn,
            'user_type'   => $user_type,
            'user_id'     => $this->user_id,
            'user_name'   => '会员' . $this->user_name,
            'action_type' => $action_type,
            'action_desc' => $action_desc,
        ];
        $re = D( 'order_retreat_log' )->add( $add );

        return $re;
    }





}