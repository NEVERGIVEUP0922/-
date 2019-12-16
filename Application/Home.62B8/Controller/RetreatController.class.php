<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-12-25
 * Time: 14:54
 */

namespace Home\Controller;

use Admin\Controller\MsgController;
use EES\System\Redis;
use Home\Model\RetreatModel;
use http\Env\Url;

class RetreatController extends HomeController
{
    protected function _initialize()
    {
        parent::_initialize();
        !is_login() && redirect( U( 'Home/Account/login' ) );
    }

    public function index()
    {
        $re_sn = I( 're_sn', null );
        $order_sn = I( 'order_sn', null );
        empty( $re_sn ) && empty( $order_sn ) && $this->error( '缺少参数!请检查' );
        if ( $re_sn ) {
        

			
            $res = M( 'order_retreat' )->where( [ 're_sn' => $re_sn ] )->find();
            $res[ 'retreat_img' ] = json_decode( $res[ 'retreat_img' ], true );
            $data = $this->getOrderInfoByOrderNo( $res[ 'order_sn' ] );
            foreach ($data['order_goods'] as $order_k1=>$order_v1){
                if($order_v1['pay_subtotal']>0){

                }else{
                    unset($data['order_goods'][$order_k1]);
                }
            }
            $remoney=0.0;
            $reinfo=M('order_retreat')->where(['order_sn'=>$res[ 'order_sn' ],'handle_status'=>['not in',[3,7]],'re_sn'=>['neq',$re_sn]])->select();
            if($reinfo){
                foreach ($reinfo as $v){
                    $remoney+=$v['retreat_money'];
                }
            }
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
			//$hytt=$hymoney;
			$this->assign('hytt1',$order_has_pay);
			$hymoney=$order_has_pay-$remoney;
			$this->assign('hymoney',$hymoney);
			
            if ( $data[ 'user_id' ] != session( 'userId' ) ) {
                $this->error( '非法操作', U( 'Home/Order/myOrder' ) );
            }
            !$res && $this->error( 're_sn参数不正确' );
            $data[ 'retreat' ] = $res;
            $data[ 'log' ] = M( 'order_retreat_log' )->where( [ 're_sn' => $re_sn,'action_type'=>['neq',8] ] )->order( 'create_time desc' )->select();
            $handle_status = $res[ 'handle_status' ];
            $action = I( 'action' );
            //echo $action;die();
            if ( $action == 'edit' ) {
            	if($res[ 'handle_status' ] != 3 && $res[ 'handle_status' ] != 7){
					foreach ($data['order_goods'] as &$ov){
						$renum=M('order_retreat_goods')->field('p_num')->where(['re_sn'=>$re_sn,'p_id'=>$ov['p_id']])->find();
						$ov['retreat_num']=$ov['retreat_num']-$renum['p_num'];
					}
                    //print_r($data);
					$this->assign( 'data', $data );
					$this->assign( 'edit', 1 );
					$this->display( 'retreatCargo' );
					exit();
				}else{

                    $this->assign( 'data', $data );
                    $this->assign( 'edit', 1 );
                    $this->display( 'retreatCargo' );
                    exit();

                }
            
            }

            switch ( $handle_status ) {
                case 1:
                    $this->applyCargo( $data ); //处理状态1 正在申请等待客服处理
                    break;
                case 2:  // 处理状态2 客服已同意申请(仅退款 显示等待退款 退货退款 显示买家填写发货物流)  显示填写
                    if ( $action === 'writeDelivery' ) {
                        $this->retreatLogistic( $data,$re_sn, $res[ 'order_sn' ]);
                    }else{
                        if ( (int)$data[ 'retreat' ][ 'retreat_type' ] === 0 ) {
                            $this->retreatAgree( $data, true );
                        } else {
                            //填写物流信息
                            $this->retreatAgree( $data );
                        }
                    }

                    break;
                case 3: //处理状态3 驳回买家申请 显示驳回
                    $this->buyerReturn( $data );
                    break;
                case 4: //买家已发送货物 可查看具体物流信息
                case 5:
                    if ( $action == 'deliveryDetail' ) {
                        $this->checkLogistic( $data,$re_sn, $res[ 'order_sn' ]);
                    }else{
                        if ( (int)$data[ 'retreat' ][ 'retreat_type' ] === 0 ) {
                            $this->retreatAgree( $data, true );
                        } else {
                            //填写物流信息
                            $this->retreatAgree( $data );
                        }
                    }
                    break;
                case 6: //退款完成
                    $this->returnComplete( $data );
                    break;
                case 7: //退款取消 退款交易关闭
                    $this->returnComplete( $data, true );
                    break;
				case 8:
					$this->assign( 'data', $data );
					$this->display("Retreat/partialAgree");
					break;
                default:
                    $this->error( '页面未找到!' );
            }
        } else {
        	
        	
            $data = $this->getOrderInfoByOrderNo( $order_sn );
            foreach ($data['order_goods'] as $order_k1=>$order_v1){
                if($order_v1['pay_subtotal']>0){

                }else{
                    unset($data['order_goods'][$order_k1]);
                }
            }
//            de( $data );
			if ( $data[ 'user_id' ] != session( 'userId' ) ) {
				$this->error( '非法操作', U( 'Home/Order/myOrder' ) );
			}
			if($data['pay_status']==0 && $data['ship_status']==0){
				if($data['order_status']==1 || $data['pay_type']==1){
					$this->error( '非法操作', U( 'Home/Order/myOrder' ) );
				}
			}
			
            if ( $data[ 'pay_status' ] == 0 && $data[ 'ship_status' ] == 0 ) {
                $this->error( '该订单未付款未发货,不能申请退货退款', U( 'Home/Order/myOrder' ) );
            }
            
			if($data['pay_status']==0 && $data['ship_status']!=4){
				$this->error( '该订单未全部收货,如需要退货,请确认收货', U( 'Home/Order/myOrder' ) );
			}
			if($data['pay_status']!=0  ){
				if($data['ship_status']!=4 && $data['ship_status']!=0 ){
					
					$this->error( '该订单未全部收货,如需要退货退款,请确认收货', U( 'Home/Order/myOrder' ) );
				}
			}
	
			$remoney=0.0;
			$reinfo=M('order_retreat')->where(['order_sn'=>$order_sn,'handle_status'=>['not in',[3,7]]])->select();
			//print_r(M()->getLastSql());
			if($reinfo){
				foreach ($reinfo as $v){
					$remoney+=$v['retreat_money'];
				}
			}
			$pay=M('order_pay_history')->field('sum(pay_amount) as pay_amount_total')->where(['order_sn'=>$order_sn])->find();
			$pay['pay_amount_total']=$pay['pay_amount_total']?:0;
			$erpPay=M('order_sync_hy')->field('sum(fcxacount) as fcxacount_total')->where(['order_no'=>$order_sn])->find();
			$erpPay['fcxacount_total']=$erpPay['fcxacount_total']?:0;
			if($data['pay_type']==2){
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
			
			
			$this->assign('hytt1',$order_has_pay);
			$hymoney=$order_has_pay-$remoney;
			//echo $hymoney;
			$this->assign('hymoney',$hymoney);
			
			
			$res=M()->query("select * from dx_order_goods WHERE  erp_num>retreat_num and order_sn='{$order_sn}' and pay_subtotal>0");
			if(!$res){
					$this->error( '可退款数量为0', U( 'Home/Order/myOrder' ) );
			}
			
			if ($data['pay_type']==1){
				$hymoney=$data['total'];
			}
			$hytt=$hymoney;
			$this->assign('hytt',$hytt);
			$hymoney=$hymoney-$remoney;
			$this->assign('hymoney',$hymoney);
		
            $this->apply(); //申请页面
        }
    }

    /*
     * 退货退款申请页模板
     */
    public function apply( $da = [] )
    {
        $order_sn = I( 'order_sn' );
        ( empty( $order_sn ) ) && $this->error( '缺省订单编号参数!' );
        $data = $da ? $da : $this->getOrderInfoByOrderNo( $order_sn );
        empty( $data ) && $this->error( '订单数据未找到!' );
        $this->assign( 'data', $data );
        $this->display( 'Retreat/retreatCargo' ); // 创建退款申请首页
    }

    /**
     * POST 执行处理录入退款申请
     */
    public function storeRetreat()
    {
        //处理退款申请
        if ( IS_POST || IS_AJAX ) {

            $re_sn = I( 'post.re_sn', null );
            //订单编号
            $order_sn = I( 'post.order_sn' );
            //退款退货商品数据
            $goods = I( 'post.goods' );
            foreach ($goods as $kg=>$kv){
            	if($kv['pnum']==0){
            		unset($goods[$kg]);
				}
			}
			if(!$goods){
				$this->error( '申请的退款退货商品数量异常!' );
			}
            //退款方式
            $retreat_type = I( 'post.retreat_type' );
            //货物状态
            $cargo_status = I( 'post.cargo_status' );
//            if ( $retreat_type == 1 ) { //退货类型为退款退货  cargo_status 货物状态项数据无效
//                $cargo_status = ;
//            }
            //申请退款金额
            $retreat_money = I( 'post.retreat_money' );
            //申请退款说明
            $retreat_desc = I( 'post.retreat_desc' );
            //申请退款说明图片
            $retreat_img = !empty( I( 'post.retreat_img' ) ) ? json_encode( I( 'post.retreat_img' ) ) : '';
            //用户id
            $user_id = session( 'userId' );
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
					$this->error( '操作异常!' );
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
                  //	$order_has_pay=$erpPay['fcxacount_total'];
				}else{
					if($erpPay['fcxacount_total']>$pay['pay_amount_total']){
						$order_has_pay=$erpPay['fcxacount_total'];
					}else{
						$order_has_pay=$pay['pay_amount_total'];
					}
				}
				
				if($rmoney['remoney']>$order_has_pay){
					M()->rollback();
					$this->error( '申请的退款金额异常!' );
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
                        $this->error( '申请的退款退货商品数量异常!' );
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
                    $orderGoods = M('order_goods')->where( ['order_sn'=>$order_sn, 'p_id'=>$v['pid'],'pay_subtotal'=>['gt',0]] )->save(['retreat_num'=>$reNum]);
                    if( $orderGoods === false ){
                        M()->rollback();
                        $this->ajaxReturnStatus( 1002, '退款申请创建失败!订单数据处理错误!' );
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
                    $this->ajaxReturnStatus( 0000, '退款申请创建成功', [ 're_sn' => $re_sn ] );
                } else {
                    M()->rollback();
                    $this->ajaxReturnStatus( 1000, '退款交易修改失败' );
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
                    'retreat_money' => $retreat_money,
                    'retreat_desc'  => $retreat_desc,
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
					$this->error( '申请的退款金额异常!' );
				}
				
				
                foreach ( $goods as $k => $v ) {
                    $good = M( 'order_goods' )->where( [ 'order_sn' => $order_sn, 'p_id' => $v[ 'pid' ] ,'pay_subtotal'=>['gt',0]] )->find();
                    //echo M()->getLastSql();
                    $num = $good[ 'erp_num' ];
                    $retreat_num = $good[ 'retreat_num' ];
                    if ( $num - $retreat_num < $v[ 'pnum' ] ) {
						M()->rollback();
                        $this->error( '申请的退款退货商品数量异常!' );
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
                            $this->ajaxReturnStatus( 1000, '退款申请创建失败!发票信息处理错误' );
                        }
                    }
                    //单个退款商品的实际金额 = (产品购买单价*产品退款数量) - (((产品购买单价*产品退款数量)/订单应付总额:包含积分抵扣的总金额)*积分抵扣金额)
//                    $p_reM = ( (float)$v['pid'] * (int)$v['pnum'] ) - ((( (float)$v['pid'] * (int)$v['pnum'] )/(float)$orderInfo['total_origin'] )*(float)$orderInfo['total_discount']);
//                    $reM +=$p_reM;
                    $orderGoods = M('order_goods')->where( ['order_sn'=>$order_sn, 'p_id'=>$v['pid'],'pay_subtotal'=>['gt',0]] )->save(['retreat_num'=>$retreat_num+$v['pnum']]);
                    if( $orderGoods === false ){
                        M()->rollback();
                        $this->ajaxReturnStatus( 1002, '退款申请创建失败!订单数据处理错误!' );
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

                    $this->ajaxReturnStatus( 0000, '退款申请创建成功', [ 're_sn' => $re_sn ] );
                } else {
                    M()->rollback();
                    $this->ajaxReturnStatus( 1000, '退款申请创建失败' );
                }
            }
        } else {
            $order_sn = I( 'order_sn' );

            return $this->redirect( 'Home/Retreat/index/order_sn/' . $order_sn );
        }
    }

    /*
     * 等待客服处理页面
     */
    public function applyCargo( $data = [] )
    {
        $this->assign( 'data', $data );
        $this->display( 'Retreat/applyCargo' );
    }

    /*
     * 客户同意页面
     */
    public function retreatAgree( $data = [], $retreat_type = false )
    {
        $this->assign( 'data', $data );
        $this->assign( 're_type', $retreat_type );
        $this->display( 'Retreat/retreatAgree' );
    }

    /*
     * 申请驳回页面
     */
    public function buyerReturn( $data = [] )
    {
        $this->assign( 'data', $data );
        $this->assign( 'again', 1 );
        $this->display( 'Retreat/buyerReturn' );
    }

    /*
     * 退货退款-填写物流单号页面
     */
    public function retreatLogistic( $data = [],$re_sn,$order_sn)
    {
        //查询物流公司列表
        $delivery = M( 'kd_delivery' )->field( 'id,kd_code,kd_name' )->select();
		$data['retreat_goods']=M()->query("select dr.*,do.p_name from dx_order_retreat_goods dr,dx_order_goods do WHERE  dr.p_id=do.p_id and do.order_sn= $order_sn and dr.re_sn=$re_sn and do.pay_subtotal>0");
		foreach($data['retreat_goods'] as &$v){
			$rimg=M()->query("select  from dx_product dp ,dx_product_package_img dpp WHERE  dp.package=dp.package AND dp.p_name='{$v['p_name']}'");
			if($rimg){
				$v['p_img']=$v['img'];
			}else{
				$v['p_img']='';
			}
		
		}
        $this->assign( 'delivery', $delivery );
        $this->assign( 'data', $data );
        $this->display( 'Retreat/retreatLogistic' );
    }

    /**
     * POST 处理买家退货填写的物流信息
     */
    public function storeWriteDelivery()
    {
        $re_sn = I( 'post.re_sn' );
        $save[ 're_delivery_id' ] = I( 'post.re_delivery_id' );
        $save[ 're_delivery_num' ] = I( 'post.re_delivery_num' );
        $save[ 're_delivery_phone' ] = I( 'post.re_delivery_phone' );
        $save[ 're_delivery_desc' ] = I( 'post.re_delivery_desc' );
		$save[ 're_delivery_img' ] = I( 'post.retreat_img' );
        empty( $re_sn ) && $this->error( '参数不正确' );
		if (!$save[ 're_delivery_img' ]){
			$this->error( '请上传图片' );
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
            $this->ajaxReturnStatus( 0000, '提交成功' );
        } else {
            $order_retreat->rollback();
            $this->ajaxReturnStatus( 1000, '物流信息提交失败' );
        }
    }

    /*
     * 退货退款  查看买家退货的物流详情
     */
    public function checkLogistic( $data = [],$re_sn,$order_sn )
    {
        //查询物流信息
		
        $retreat = $data[ 'retreat' ];
        $order_sn = $retreat[ 'order_sn' ];
        $delivery_id = $retreat[ 're_delivery_id' ];
        $delivery_num = $retreat[ 're_delivery_num' ];
//        de( $data['retreat'] );
        //查询物流编码
        $delivery = M( 'kd_delivery' )->where( [ 'id' => $delivery_id ] )->find();
        //$delivery['code'] = 'ZTO';
//        dd($delivery['code']);
        //查询物流信息
        //$data['traces'] = A( 'kd' )->info( $delivery[ 'kd_code' ], $delivery_num, $order_sn );
	
		$kd = new KdController();
		$kdInfoData = $kd->info($delivery[ 'kd_code' ], $delivery_num, $order_sn);
		$data['traces']  = $kdInfoData['traces'];
        //按物流时间节点排序
        $arr1 = array_column( $data[ 'traces' ], 'AcceptTime' );
        array_multisort( $arr1, SORT_DESC, $data[ 'traces' ] );
        $weekArray = [ "日", "一", "二", "三", "四", "五", "六" ];
        //时间格式化替换
        foreach ( $data[ 'traces' ] as $k => $v ) {
            $time = date( 'Y-m-d ', strtotime( $v[ 'AcceptTime' ] ) );
            $week = '星期' . $weekArray[ date( 'w', strtotime( $v[ 'AcceptTime' ] ) ) ];
            $AcceptTime = $time . '&nbsp;&nbsp;' . $week;
            $v[ 'AcceptStation' ] = date( 'H:i:s ', strtotime( $v[ 'AcceptTime' ] ) ) . $v[ 'AcceptStation' ];
            $data[ 'traces' ][ $AcceptTime ][] = $v[ 'AcceptStation' ];
            unset( $data[ 'traces' ][ $k ] );
        }
        $data['retreat']['retreat_delivery_name']=$delivery['kd_name'];

        $data['retreat_goods']=M("order_retreat_goods")->alias('dr')->field(" dr.*,do.p_name,pimg.img as p_img")->join("dx_order_goods as do on dr.p_id=do.p_id")->join("dx_product as p on p.id=dr.p_id")->join("LEFT JOIN dx_product_package_img as pimg on p.package=pimg.package")->where(['do.order_sn'=>$order_sn,'dr.re_sn'=>$re_sn,'do.pay_subtotal'=>['gt',0]])->select();

        $this->assign( 'data', $data );
        $this->display( 'Retreat/checkLogistic' );
    }

    /*
     * 退款完毕页面
     */
    public function returnComplete( $data = [], $isCancle = false )
    {
        //为true 则是用户撤销
        if ( $isCancle ) {
            $this->assign( 'isCancle', 1 );//用户撤销退款
        } else {
            $this->assign( 'isCancle', 0 );//退款完成
        }
        $this->assign( 'data', $data );
        $this->display( 'Retreat/returnComplete' );
    }

    /*
	 * 客户主动撤销退款申请
	 */
    public function cancleRetreat( $data = [] )
    {
        $re_sn = I( 're_sn' ) ? I( 're_sn' ) : $data[ 'retreat' ][ 're_sn' ];
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
                $orderG = M( 'order_goods' )->field( 'retreat_num' )->where( [ 'order_sn' => $v[ 'order_sn' ], 'p_id' => $v[ 'p_id' ],'pay_subtotal'=>['gt',0] ] )->find();
                $res = M( 'order_goods' )->where( [ 'order_sn' => $v[ 'order_sn' ], 'p_id' => $v[ 'p_id' ] ,'pay_subtotal'=>['gt',0]] )->save( [ 'retreat_num' => $orderG[ 'retreat_num' ] - $v[ 'p_num' ] ] );
                if ( $res === false ) {
                    $retreat->rollback();
                    if ( IS_AJAX ) {
                        $this->ajaxReturnStatus( 1000, '撤销退款失败' );
                    }
                    $this->error( '系统繁忙!撤销退款申请失败' );
                }
            }
        }
        //退款操作纪录
        $log = $this->retreatLog( $re_sn, 0, 7 );
        if ( $re !== false && $log ) {
            $retreat->commit();
            if ( IS_AJAX ) {
                $this->ajaxReturnStatus( 0000, '撤销退款成功' );
            }
            $this->success( '撤销退款申请成功!退款交易已关闭' );
        } else {
            $retreat->rollback();
            if ( IS_AJAX ) {
                $this->ajaxReturnStatus( 1000, '撤销退款失败' );
            }
            $this->error( '系统繁忙!撤销退款申请失败' );
        }
    }

    /*
     * 检查订单是否曾经退货退款 并获取订单产品的曾经退货数量
     */
    protected function checkOrderIsRetreat( $order_sn )
    {
        $exists = M( 'order' )->where( [ 'order_sn' => $order_sn, 'is_retreat' => 1 ] )->count();
        $isRetreat = $exists > 0 ? true : false;
        //查询曾经退款退货的产品的数量
        $goods = M( 'order_goods' )->field( 'p_id,p_name, p_num, retreat_num' )->where( [ 'order_sn' => $order_sn,'pay_subtotal'=>['gt',0] ] )->select();

        return [ 'isRetereat' => $isRetreat, 'goods' => $goods ];
    }

    /*
	 * 根据订单id和商品id 查询订单已及商品的信息
	 */
    protected function getOrderInfoByOrderNo( $order_sn )
    {
        if ( empty( $order_sn ) ) return false;

        //查询订单数据
        $where = [ 'order_sn' => $order_sn ];
        $order = M( 'order' )->where( $where )->find();
        //订单详情

        $order[ 'order_detail' ] = M( 'order_detail' )->where( $where )->find();
        $where = [ 'order_sn' => $order_sn ,'pay_subtotal'=>['gt',0]];
        $order[ 'order_goods' ] = M( 'order_goods' )->where( $where )->select();

        //查询该订单的所有产品信息
        foreach ( $order[ 'order_goods' ] as $k => $item ) {
            $joinp = '__PRODUCT__ AS p ON p.id = g.p_id';
            $where = [ 'g.order_sn' => $order_sn, 'g.p_id' => $item[ 'p_id' ],'pay_subtotal'=>['gt',0] ];
            $orderGoods[ $k ] = M( 'order_goods' )->alias( 'g' )->join( $joinp, 'LEFT' )->where( $where )->find();
            //产品图片
            $imgData = D( 'product_detail' )->field( 'img' )->where( [ 'p_id' => $item[ 'p_id' ] ] )->find();
            $orderGoods[ $k ][ 'p_img' ] = $imgData[ 'img' ];
        }
        $order[ 'order_goods' ] = $orderGoods;

        return $order;
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
        $add = [
            're_sn'       => $re_sn,
            'user_type'   => $user_type,
            'user_id'     => session( 'userId' ),
            'user_name'   => '会员' . session( 'userInfo.user_name' ),
            'action_type' => $action_type,
            'action_desc' => $action_desc,
        ];
        $re = D( 'order_retreat_log' )->add( $add );

//        //消息通知对应业务员
//        switch( (int)$action_type ){
//            case 0:
//                MsgController::writeMsgToUserSale( session( 'userId' ), '新退款订单!退款单号:'.$re_sn, '退款单号:'.$re_sn.'{}当前退款/退货进度:新退款订单' );
//                break;
//            case 1:
//                MsgController::writeMsgToHomeUser( session( 'userId' ), '退款订单新进度!退款单号:'.$re_sn, '退款单号:'.$re_sn.'{}当前退款/退货进度:玖隆已同意您的退款申请!' );
//                break;
//            case 2:
//                MsgController::writeMsgToHomeUser( session( 'userId' ), '退款订单新进度!退款单号:'.$re_sn, '退款单号:'.$re_sn.'{}当前退款/退货进度:玖隆驳回您的退款申请!请查看申请详情!' );
//                break;
//            case 3:
//                MsgController::writeMsgToUserSale( session( 'userId' ), '退款订单新进度!客户修改退款订单!', '退款单号:'.$re_sn.'{}当前退款/退货进度:客户修改退款订单' );
//                break;
//            case 4:
//                MsgController::writeMsgToUserSale( session( 'userId' ), '退款订单新进度!客户已填写退货物流信息!', '退款单号:'.$re_sn.'{}当前退款/退货进度:客户已填写退货物流信息'  );
//                break;
//            case 5:
//                MsgController::writeMsgToHomeUser( session( 'userId' ), '退款订单新进度!退款单号:'.$re_sn, '退款单号:'.$re_sn.'{}当前退款/退货进度:玖隆已确认收到您的退货货物!正在为您退款中!请稍后注意查看' );
//                break;
//            case 6:
//                MsgController::writeMsgToUserSale( session( 'userId' ), '退款订单已完成!', '退款单号:'.$re_sn.'{}当前退款/退货进度:退款已完成!订单关闭' );
//                break;
//            case 7:
//                MsgController::writeMsgToUserSale( session( 'userId' ), '退款订单已撤销!', '退款单号:'.$re_sn.'{}当前退款/退货进度:客户撤销退款申请' );
//                break;
//        }


        return $re;
    }

    /*
     *退货申请检测发票信息
     */
    public function retreatChangeInvoice( $order_sn, $pid )
    {
        $goods_info = D( 'order_goods' )->where( [ 'order_sn' => $order_sn, 'p_id' => $pid ,'pay_subtotal'=>['gt',0]] )->find();

        if ( !$goods_info ) return [ 'error' => 1, 'msg' => '订单商品信息错误' ];
        if ( !$goods_info[ 'user_invoice_id' ] ) return [ 'error' => 0, 'msg' => '没有发票申请' ];
        $user_invoice = D( 'user_invoice' )->where( [ 'id' => $goods_info[ 'user_invoice_id' ] ] )->find();
        if ( $user_invoice[ 'status' ] == 1 ) return [ 'error' => 0, 'msg' => '发票已经开出' ]; //发票已经开出

        D( 'order' )->startTrans();
        $goods_result = D( 'order_goods' )->where( [ 'user_invoice_id' => $goods_info[ 'user_invoice_id' ] ,'pay_subtotal'=>['gt',0]] )->save( [ 'user_invoice_id' => 0 ] );
        $invoice_result = D( 'user_invoice' )->where( [ 'id' => $goods_info[ 'user_invoice_id' ] ] )->delete();

        if ( $goods_result && $invoice_result ) {
            D( 'order' )->commit();

            return [ 'error' => 0, 'msg' => '发票更新成功' ];
        } else {
            D( 'order' )->rollback();

            return [ 'error' => 1, 'msg' => '发票更新失败' ];
        }
    }
}