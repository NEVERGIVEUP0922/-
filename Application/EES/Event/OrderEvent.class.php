<?php

// +----------------------------------------------------------------------
// | FileName:   OrderEvent.class.php
// +----------------------------------------------------------------------
// | Dscription:   ERP订单货运信息及付款状态变更监控事件处理
// +----------------------------------------------------------------------
// | Date:  2017-12-29 15:19
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace EES\Event;

use EES\Model\OrderModel;
use Home\Controller\KdController;
use Common\Controller\KdApiController as KdApi;
use Think\Log;

class OrderEvent extends BaseEvent
{
	/*
	 *  订单通知事件处理
	 *
	 */
	public function knotorder($data){
		$change = $data['change'];
		//file_put_contents('11.txt',$data['change']['orderentry'][0]['fqty']);
		$res=M('order')->where(['order_sn'=>$change['order_no']])->find();
		if($res){
			$sum=0;
			M()->startTrans();
			foreach($data['change']['orderentry'] as $v){
			    if($v['fissample']=='Y'){
                    $res2=M('order_goods')->where(['fitemno_sync'=>$v['fitemno'],'order_sn'=>$change['order_no'],'pay_subtotal'=>['elt',0]])->save(['knot_num'=>$v['fcancelqty']]);
                }else{
                    $res2=M('order_goods')->where(['fitemno_sync'=>$v['fitemno'],'order_sn'=>$change['order_no'],'pay_subtotal'=>['gt',0]])->save(['knot_num'=>$v['fcancelqty']]);
                }
				if($res2===false){
					M()->rollback();
					return false;
				}
				$sum+=$v['fcancelqty'];
			}
			if($sum>0){
				$res1=M("order")->where(['order_sn'=>$change['order_no']])->save(['knot'=>2]);
				if($res1===false){
					M()->rollback();
					return false;
				}
				$save=orderStatus($change['order_no']);
				//file_put_contents('990220.txt',json_encode($save));
				if ( !empty( $save ) ) {
					//订单状态信息写入
					$res = M('order')->where(['order_sn' => $change['order_no']])->save( $save );
					//file_put_contents("777.txt",M()->getLastSql());
					if ( $res === false ) {
						Log::write( '订单' . $change[ 'order_no' ] . '的订单货运状态/付款状态写入失败' );
						M()->rollback();
						return false;
					}
				}
			}
			M()->commit();
			return true;
		}else{
			return true;
		}
	}
	
	
    public function updateStore( $data )
    {
     
    	if($data[ 'category' ]=='order_knotorder'){
    		$res=$this->knotorder($data);
    		if($res){
    			return true;
			}else{
    			return false;
			}
		}
		
		$category = strpos( $data[ 'category' ], '_' ) !== false ? trim( strstr( $data[ 'category' ], '_' ), '_' ) : $data[ 'category' ];
		$change = $data['change'];
		$order = new OrderModel();
		$hyModel = M('order_sync_hy');
		$orderInfo = $order->where( [ 'order_sn' => $change[ 'order_no' ] ] )->find();
		if ( empty( $orderInfo ) ) {
			Log::write( '同步信息:' . $category . $change[ 'order_no' ] . '的订单信息未找到!同步数据:' . json_encode( $change ) );
			return true;
		}
	
		$save = [];
		//开启事务
		$Model=M();
		$Model->startTrans();
	
		//货运信息
		if ( !empty( $change[ 'hyInfo' ] ) && is_array( $change[ 'hyInfo' ] ) ) {
			
			$hyInfo  = [
				'order_no'=>$change[ 'order_no' ],
				'hy_shipvia'=>$change['hyInfo']['fshipvia'],
				'hy_name'=>$change['hyInfo']['fhycompanyname'],
				'hy_num'=>$change['hyInfo']['fhyno'],
				'hy_contactor'=>$change['hyInfo']['fhycontactor'],
				'hy_tel'=>$change['hyInfo']['fhytel'],
				'hy_note'=>$change['hyInfo']['fhynote'],
				'hy_date'=>$change['hyInfo']['fhydate']?$change['hyInfo']['fhydate']:null,
				'hy_etd'=>$change['hyInfo']['fhyetd']?$change['hyInfo']['fhyetd']:null,
				'erp_th_no'=>$change['th_no'],
				'ct'=>(int)$change['ct'],
                'fskstatus'=>$change['outinfo']['fskstatus'],
                'fjzdebit'=>$change['outinfo']['fjzdebit'],
                'fcxacount'=>$change['outinfo']['fcxamount'],
			];
			if($hyInfo['hy_shipvia']=='快递寄付'&&!$hyInfo['hy_num']){
            	$Model->commit();
				return true;
            }
            if($hyInfo['hy_shipvia']=='快递到付'&&!$hyInfo['hy_num']){
                  $Model->commit();
                  return true;
             }
			//判断是否为快递
			if ( $hyInfo['hy_name'] ) {
				$kdName = $hyInfo['hy_name'] ;
				$normal = [
					'快递','快运','速运','物流','Express'
				];
				//file_put_contents('88.txt',$hyInfo['hy_shipvia']);
				foreach( $normal as $value ){
					if( strpos( $hyInfo['hy_shipvia'], $value ) !== false) {
						$hyInfo['is_kd'] = 1;
					}
				}
				//根据货运名称 从数据库查询是否 能够查询 到快递名称对应的编码
				$kdSName = substr( $kdName, 0,2 );
				$res = M( 'kd_delivery' )->where( "kd_name like '%{$kdSName}%' " )->find();
				$res && $hyInfo['is_kd'] = 1;
				$res && $kdCode = $res[ 'kd_code' ];
			} else {
				if( $hyInfo['hy_num'] ){
					$kdNum = $hyInfo['hy_num'];
					$kd = KdApi::getInit();
					$kdArr= $kd::getKdCodeByKdNum( $kdNum );
					if( $kdArr ){
						$kdCode = $kdArr['kdCode'];
						$kdName = $kdArr['kdName'];
						$hyInfo['is_kd'] = 1;
					}
				}
			}
            $normal = [
                '快递','快运','速运','物流','Express'
            ];
            //file_put_contents('88.txt',$hyInfo['hy_shipvia']);
            foreach( $normal as $value ){
                if( strpos( $hyInfo['hy_shipvia'], $value ) !== false) {
                    $hyInfo['is_kd'] = 1;
                }
            }
			//如果为快递   执行添加快递列表
			if ( isset( $kdCode ) && $kdCode ) {
				$isExists = M( 'kd_delivery' )->where( ['kd_code'=>$kdCode] )->find();
				if( empty( $isExists ) && !empty($kdName) ){
					M('kd_delivery')->add([
						'kd_name'=>$kdName,
						'kd_code'=>$kdCode,
					]);
				}
				$hyInfo['kd_code'] = $kdCode;
			}
			
			//查询之前是否已有纪录
			$isEx = $hyModel->where(['order_no'=>$change['order_no'], 'erp_th_no'=>$change['th_no']])->find();
          //file_put_contents("777.txt",M()->getLastSql());
			if( $isEx ){
				if($isEx['is_lock']==1 && $change['hyInfo']['flock']=='Y'){
					$hyInfo['is_lock']=1;
				}elseif($isEx['is_lock']==1 && $change['hyInfo']['flock']=='N'){
					$hyInfo['is_lock']=2;
				}elseif($isEx['is_lock']==0 && $change['hyInfo']['flock']=='N'){
					$Model->commit();
					return true;
				}elseif($isEx['is_lock']==0 && $change['hyInfo']['flock']=='Y'){
                    $hyInfo['update_time']=strtotime("+5 day");
					$hyInfo['is_lock']=1;
				}elseif($isEx['is_lock']==2 && $change['hyInfo']['flock']=='Y'){
                    $hyInfo['update_time']=strtotime("+5 day");
					$hyInfo['is_lock']=1;
				}
				if ((isset($hyInfo['is_kd'])&&$hyInfo['is_kd']==1) ||($change['hyInfo']['fstatus']!='F')){
					$hyInfo['is_recive']=0;
				}elseif($change['hyInfo']['fstatus']=='F'){
					$hyInfo['is_recive']=1;
				}
              	if($isEx['c_recive']==1){
					$hyInfo['is_recive']=1;
				}
              //file_put_contents("ppp.txt",$isEx['detail']);
              $detail1=json_decode($isEx['detail'],true);
              			 foreach ($detail1 as $k1=>&$v1){
                             if($v1['fissample']=='Y'){
                                 $res=M('order_goods')->where(['order_sn'=>$change['order_no'],'fitemno_sync'=>$v1['fitemno'],'pay_subtotal'=>['elt',0]])->find();
                                 $sqty=(int)$v1['fqty'];
                                 $sqty=$res['erp_num']-$sqty;
                                 $res1=M('order_goods')->where(['order_sn'=>$change['order_no'],'fitemno_sync'=>$v1['fitemno'],'pay_subtotal'=>['elt',0]])->save(['erp_num'=>$sqty]);
                             }else{
                                 $res=M('order_goods')->where(['order_sn'=>$change['order_no'],'fitemno_sync'=>$v1['fitemno'],'pay_subtotal'=>['gt',0]])->find();
                                 $sqty=(int)$v1['fqty'];
                                 $sqty=$res['erp_num']-$sqty;
                                 $res1=M('order_goods')->where(['order_sn'=>$change['order_no'],'fitemno_sync'=>$v1['fitemno'],'pay_subtotal'=>['gt',0]])->save(['erp_num'=>$sqty]);
                             }

                              //$sql="update dx_order_goods set erp_num=erp_num+{$sqty} WHERE  order_sn='{$change['order_no']}' AND fitemno_sync='{$v['fitemno']}'";
                             // $res1=M()->query($sql);
                              if ( $res1 === false ) {
                                  Log::write( '订单' . $change[ 'order_no' ] . '的订单erp_num数量更新失败' );
                                  $Model->rollback();
                                  return false;
                              }
                         }
                       $st='';
                        foreach ($change['detail'] as $k=>&$v){
                            if($v['fissample']=='Y'){
                                $res=M('order_goods')->where(['order_sn'=>$change['order_no'],'fitemno_sync'=>$v['fitemno'],'pay_subtotal'=>['elt',0]])->find();
                                $sqty=(int)$v['fqty'];
                                $sqty=$res['erp_num']+$sqty;
                                $res1=M('order_goods')->where(['order_sn'=>$change['order_no'],'fitemno_sync'=>$v['fitemno'],'pay_subtotal'=>['elt',0]])->save(['erp_num'=>$sqty]);
                                //$sql="update dx_order_goods set erp_num=erp_num+{$sqty} WHERE  order_sn='{$change['order_no']}' AND fitemno_sync='{$v['fitemno']}'";
                                // $res1=M()->query($sql);
                            }else{
                                $res=M('order_goods')->where(['order_sn'=>$change['order_no'],'fitemno_sync'=>$v['fitemno'],'pay_subtotal'=>['gt',0]])->find();
                                $sqty=(int)$v['fqty'];
                                $sqty=$res['erp_num']+$sqty;
                                $res1=M('order_goods')->where(['order_sn'=>$change['order_no'],'fitemno_sync'=>$v['fitemno'],'pay_subtotal'=>['gt',0]])->save(['erp_num'=>$sqty]);
                                //$sql="update dx_order_goods set erp_num=erp_num+{$sqty} WHERE  order_sn='{$change['order_no']}' AND fitemno_sync='{$v['fitemno']}'";
                                // $res1=M()->query($sql);
                            }
//                           $res=M('order_goods')->where(['order_sn'=>$change['order_no'],'fitemno_sync'=>$v['fitemno']])->find();
//                           $sqty=(int)$v['fqty'];
//                           $sqty=$res['erp_num']+$sqty;
//                           $res1=M('order_goods')->where(['order_sn'=>$change['order_no'],'fitemno_sync'=>$v['fitemno']])->save(['erp_num'=>$sqty]);
                            //$sql="update dx_order_goods set erp_num=erp_num+{$sqty} WHERE  order_sn='{$change['order_no']}' AND fitemno_sync='{$v['fitemno']}'";
                           // $res1=M()->query($sql);
							if ( $res1 === false ) {
								Log::write( '订单' . $change[ 'order_no' ] . '的订单erp_num数量更新失败' );
								$Model->rollback();
								return false;
							}
                            if($res){
                                $v['p_name']=$res['p_name'];
                                if($st){
                                    $st.='|'.' 数量'.(int)$v['fqty'].'-'.'型号'.$res['p_name'];
                                }else{
                                    $st.='数量'.(int)$v['fqty'].'-'.'型号'.$res['p_name'];
                                }
                            }
                        }
                        $hyInfo['strl']=addslashes ($st);
                        $hyInfo['detail']=json_encode($change['detail']);
              
				$hyInsertRes = $hyModel->where(['order_no'=>$change['order_no'], 'erp_th_no'=>$change['th_no']])->save( $hyInfo );
               
			}else{
              
                if($change['hyInfo']['flock']=='Y'){
                        $st='';
                        foreach ($change['detail'] as $k=>&$v){
                            if($v['fissample']=='Y'){
                                $res=M('order_goods')->where(['order_sn'=>$change['order_no'],'fitemno_sync'=>$v['fitemno'],'pay_subtotal'=>['elt',0]])->find();
                                $sqty=(int)$v['fqty'];
                                $sqty=$res['erp_num']+$sqty;
                                $res1=M('order_goods')->where(['order_sn'=>$change['order_no'],'fitemno_sync'=>$v['fitemno'],'pay_subtotal'=>['elt',0]])->save(['erp_num'=>$sqty]);
                            }else{
                                $res=M('order_goods')->where(['order_sn'=>$change['order_no'],'fitemno_sync'=>$v['fitemno'],'pay_subtotal'=>['gt',0]])->find();
                                $sqty=(int)$v['fqty'];
                                $sqty=$res['erp_num']+$sqty;
                                $res1=M('order_goods')->where(['order_sn'=>$change['order_no'],'fitemno_sync'=>$v['fitemno'],'pay_subtotal'=>['gt',0]])->save(['erp_num'=>$sqty]);
                            }
                            //$sql="update dx_order_goods set erp_num=erp_num+{$sqty} WHERE  order_sn='{$change['order_no']}' AND fitemno_sync='{$v['fitemno']}'";
                           // $res1=M()->query($sql);
							if ( $res1 === false ) {
								Log::write( '订单' . $change[ 'order_no' ] . '的订单erp_num数量更新失败' );
								$Model->rollback();
								return false;
							}
                            if($res){
                                $v['p_name']=$res['p_name'];
                                if($st){
                                    $st.='|'.' 数量'.(int)$v['fqty'].'-'.'型号'.$res['p_name'];
                                }else{
                                    $st.='数量'.(int)$v['fqty'].'-'.'型号'.$res['p_name'];
                                }
                            }
                        }
                        $hyInfo['strl']=addslashes ($st);
                        $hyInfo['detail']=json_encode($change['detail']);
						$hyInfo['update_time']=strtotime("+5 day");
						$hyInfo['is_lock']=1;
					if ((isset($hyInfo['is_kd'])&&$hyInfo['is_kd']==1) ||($change['hyInfo']['fstatus']!='F')){
						$hyInfo['is_recive']=0;
					}elseif($change['hyInfo']['fstatus']=='F'){
						$hyInfo['is_recive']=1;
					}
                    $hyInsertRes = $hyModel->add( $hyInfo );
		     	}else{
					$Model->commit();
                	return true;
				}
		  
			}
	
			if ( $hyInsertRes === false ) {
				Log::write( '同步信息:' . $category . $change[ 'order_no' ] . '写入快递编码信息失败!' );
				$Model->rollback();
				return false;
			}
			
			$save=orderStatus($change['order_no']);
          	//file_put_contents('990220.txt',json_encode($save));
			if ( !empty( $save ) ) {
				//订单状态信息写入
				$res = M('order')->where(['order_sn' => $change['order_no']])->save( $save );
              	 file_put_contents("777.txt",M()->getLastSql());
				if ( $res === false ) {
					Log::write( '订单' . $change[ 'order_no' ] . '的订单货运状态/付款状态写入失败' );
					$Model->rollback();
					return false;
				}
              //积分到账
              	if($save["order_status"]==3&&0){
                    M('intergral_all','wa_')->where(["order_sn"=>$change['order_no']])->delete();
                    $u_all=M("order")->where(["order_sn"=>$change['order_no']])->find();
				    $o_all=M("order_goods")->where(["order_sn"=>$change['order_no']])->select();
				    foreach ($o_all as $o_value){
				        if($o_value["wa_intergral"]){
                            $o_price=($o_value["p_num"]-$o_value["knot_num"])*$o_value["p_price_true"];
                            $this->erpIntergral(json_decode($o_value["wa_intergral"],true),$change['order_no'],'',$u_all["user_id"],$o_price);
                          //	file_put_contents("666.txt","2223232332");
                            file_put_contents("1122.txt",$o_value["wa_intergral"]."---".$change['order_no'].'---'.$u_all["user_id"]."---".$o_price);
                        }
                    }
                 
                }
              
              if ($save['pay_status']==2&&$orderInfo['pay_type']==2&&$orderInfo['total']!=0.00){
			$pay_history = M('order_pay_history')->field('id,pay_amount,account_pay_id,account_pay_selected')->where(['order_sn'=>$change['order_no'], 'type'=>2])->find();
            $hymoney=M('order_sync_hy')->field('sum(fcxacount) as acount')->where(['order_no'=>$change['order_no']])->find();
			$hymoney['acount']=$hymoney['acount']?:0;
			if($pay_history){
				if(!$pay_history['account_pay_id']){
					if($pay_history['account_pay_selected']){
						$account_pay_save = M('account_pay_history')->where(['id'=>$pay_history['account_pay_selected']])->save([
							'user_id'=>$orderInfo['user_id'],
							'total'=> $hymoney['acount'],
							'pay_type'=>100,
							'step'=>2,
						]);
						if($account_pay_save===false){
							Log::write( '订单' . $change[ 'order_no' ] . '的付款状态写入失败' );
							$Model->rollback();
							return false;
						}else{
							$pay_order=M('order_pay_history')->where(['account_pay_selected'=>$pay_history['account_pay_selected']])->save(['account_pay_selected'=>0]);
							if($pay_order===false){
								Log::write( '订单' . $change[ 'order_no' ] . '的付款状态写入失败' );
								$Model->rollback();
								return false;
							}else{
								$save_pay=M('order_pay_history')->where(['id'=>$pay_history['id']])->save(['account_pay_id'=>$pay_history['account_pay_selected'],'pay_amount'=>$hymoney['acount']]);
								if($save_pay===false){
									Log::write( '订单' . $change[ 'order_no' ] . '的付款状态写入失败' );
									$Model->rollback();
									return false;
								}
								
							}
						}
						
					}else{
						$account_pay_insert = M('account_pay_history')->add([
							'user_id'=>$orderInfo['user_id'],
							'total'=> $hymoney['acount'],
							'pay_type'=>100,
							'step'=>2,
						]);
						if($account_pay_insert===false){
							Log::write( '订单' . $change[ 'order_no' ] . '的付款状态写入失败' );
							$Model->rollback();
							return false;
						}else{
							$pay_order=M('order_pay_history')->where(['id'=>$pay_history['id']])->save(['account_pay_id'=>$account_pay_insert,'pay_amount'=>$hymoney['acount']]);
							if($pay_order===false){
								Log::write( '订单' . $change[ 'order_no' ] . '的付款状态写入失败' );
								$Model->rollback();
								return false;
							}
						}
					}
				}
			}
		}
			}
			
			//结束
		}
	
		
		
		
		
		
		//付款完成
		if ( isset( $change[ 'isSk' ] ) && $change[ 'isSk' ]) {
			
			if( ((int)$change[ 'isPart' ] === 2  || (int)$change[ 'isPart' ] === 3 ) ){
				
				//如果是账期支付 订单完成付款 那么将还款纪录写入dx_account_pay_history数据表
				//查询订单需账期支付的金额
      			$pay_history = M('order_pay_history')->field('pay_amount')->where(['order_sn'=>$change['order_no'], 'type'=>2])->find();
				if( $pay_history ){
					if( (int)$orderInfo['pay_type'] === 2 ){
						$account_pay_insert = M('account_pay_history')->add([
							'user_id'=>$orderInfo['user_id'],
							'total'=> $pay_history['pay_amount'],
							'pay_type'=>100,
							'step'=>2,
						]);
						if( $account_pay_insert === false || $account_pay_insert === 0 ){
							Log::write( '同步账期信息:' . $category . $change[ 'order_no' ] . '账期还款历史信息新增纪录失败');
							$Model->rollback();
							return false;
						}
						$account_pay_id = $account_pay_insert;
					}
				}
				
				//支付完成纪录写入支付历史表
				$order_pay_his_add = [
					'order_sn'    => $change[ 'order_no' ],
					'order_total' => $orderInfo[ 'total' ],
					'pay_amount'  => $orderInfo[ 'total' ] - $orderInfo[ 'already_paid' ],
					'type'        => 100,
					'pay_name'    => 'ERP确认支付',
				];
				if( isset($account_pay_id)  ){
					$order_pay_his_add['account_pay_id'] = $account_pay_id;
				}
				$saveRes = M( 'order_pay_history' )->add( $order_pay_his_add );
				if ( $saveRes === false ) {
					Log::write( '同步信息:' . $category . '商城订单: ' . $change[ 'order_no' ] . '的支付纪录写入失败' );
					$Model->rollback();
					
					return false;
				}
				//分批未完成
			}
		}
		
		//账期预计收款日信息
		if( (int)$orderInfo[ 'pay_type' ] === 2 && ( isset( $change['yjPayDate'] ) && !is_null( $change['yjPayDate'] ) ) ){
			$yjDate = date( 'Y-m-d H:i:s', strtotime($change['yjPayDate']) );
			$orderSaveRes = M('order')->where([ 'order_sn'=>$change[ 'order_no' ] ])->save([
				'account_pay_time'=>$yjDate
			]);
			if ( $orderSaveRes === false ) {
				Log::write( '同步信息:' . $category . '商城订单: ' . $change[ 'order_no' ] . '的账期订单的预计付款日期写入失败' );
				$Model->rollback();
				return false;
			}
		}
		
	
		//更新完成
		$order->commit();
		
		return true;
		
	}
	
	public function insertStore( $data )
	{
	
	}
	
	public function deleteStore( $data )
	{
	
	}
	
	
	protected function getMaxLindId( $order_sn )
	{
		$id = M('order_sync_hy')->where(['order_no'=>$order_sn])->count();
		return $id+1;
	}
  
  	//返回可积分金额
    public  function erpIntergral($arr,$order_sn,$erp_th_no,$user_id,$th_money){
        if(isset($arr[101])){
            $return_result=$this->storeIntergral($arr[101],$user_id,$th_money);
            $add['user_id']=$user_id;
            $add['order_sn']=$order_sn;
            // $add['erp_th_no']=$erp_th_no;
            $add['integral_id']=$arr[101]['id'];
            $add['integral_amount']=$return_result["has_intergral_money"]*$arr[101]['scale'];
            $add["amount"]=$th_money;
            $add["has_integral_money"]=$return_result["has_intergral_money"];
            $add['status']=$return_result["status"];
            M('intergral_all','wa_')->add($add);
        }else{
            $addAll=[];
            foreach ($arr as $k=>$v){
              //  if($v["type"]==1&&$v["min_amount"]>$th_money){
                //      continue;
                //}
                //if($v["type"]==1&&$v["max_amount"]<$th_money){
                  //    continue;
                //}
              if($v["type"]==1){
              	$this->oneintergral($v,$user_id,$th_money,$order_sn);
                continue;
              }
                $return_result=$this->storeIntergral($v,$user_id,$th_money);
                $add['user_id']=$user_id;
                $add['order_sn']=$order_sn;
                // $add['erp_th_no']=$erp_th_no;
                $add['integral_id']=$v['id'];
                $add['integral_amount']=$return_result["has_intergral_money"]*$v['scale'];
                $add["amount"]=$th_money;
                $add["has_integral_money"]=$return_result["has_intergral_money"];
                $add['status']=$return_result["status"];
                $addAll[]=$add;
            }
            M('intergral_all','wa_')->addAll($addAll);
        }
    }
  
  //单笔
	public function  oneintergral($v,$user_id,$th_money,$order_sn){
        $allinter=M('intergral_all','wa_')->where(['order_sn'=>$order_sn,'integral_id'=>$v['id']])->select();
        $intmoney=0;
        $intstatus=0;
        $outmoney=0;
        $outstatus=0;
        foreach($allinter as $ak=>$av){
            if($av['status']==1){
                $intmoney=+$av['has_integral_money'];
                $intstatus=+$av['amount'];
            }else{
                $outmoney=+$av['has_integral_money'];
                $outstatus=+$av['amount'];
            }
        }
        if($outstatus==0){
            if($intstatus){
                if($v['max_amount']>$intmoney){
                    $a=$th_money-($v['max_amount']-$intmoney);
                    if($a>0){
                        $addData['user_id']=$user_id;
                        $addData['integral_amount']=($v['max_amount']-$intmoney)*$v['scale'];
                        $addData['has_integral_money']=$v['max_amount']-$intmoney;
                        $addData['amount']=$th_money;
                        $addData['integral_id']=$v['id'];
                        $addData['status']=1;
                        $addData['order_sn']=$order_sn;
                    }else{
                        $addData['user_id']=$user_id;
                        $addData['integral_amount']=$th_money*$v['scale'];
                        $addData['has_integral_money']=$th_money;
                        $addData['amount']=$th_money;
                        $addData['integral_id']=$v['id'];
                        $addData['status']=1;
                        $addData['order_sn']=$order_sn;
                    }
                }else{
                    $addData['user_id']=$user_id;
                    $addData['integral_amount']=0;
                    $addData['has_integral_money']=0;
                    $addData['amount']=$th_money;
                    $addData['integral_id']=$v['id'];
                    $addData['order_sn']=$order_sn;
                }

            }else{
                if($v['min_amount']>$th_money){
                    $addData['user_id']=$user_id;
                    $addData['integral_amount']=0;
                    $addData['has_integral_money']=0;
                    $addData['amount']=$th_money;
                    $addData['integral_id']=$v['id'];
                    $addData['order_sn']=$order_sn;
                }else{
                    if($v['max_amount']>=$th_money){
                        $addData['user_id']=$user_id;
                        $addData['integral_amount']=$th_money*$v['scale'];
                        $addData['has_integral_money']=$th_money;
                        $addData['amount']=$th_money;
                        $addData['integral_id']=$v['id'];
                        $addData['status']=1;
                        $addData['order_sn']=$order_sn;
                    }else{
                        $addData['user_id']=$user_id;
                        $addData['integral_amount']=$v['max_amount']*$v['scale'];
                        $addData['has_integral_money']=$v['max_amount'];
                        $addData['amount']=$th_money;
                        $addData['integral_id']=$v['id'];
                        $addData['status']=1;
                        $addData['order_sn']=$order_sn;
                    }
                }
            }

           // M('intergral_all','wa_')->add($addData);
        }else{
            if($intstatus){
                $addData['user_id']=$user_id;
                $addData['integral_amount']=0;
                $addData['has_integral_money']=0;
                $addData['amount']=$th_money;
                $addData['integral_id']=$v['id'];
                $addData['order_sn']=$order_sn;
            }else{
                if($v['min_amount']>$outstatus+$th_money){
                    $addData['user_id']=$user_id;
                    $addData['integral_amount']=0;
                    $addData['has_integral_money']=0;
                    $addData['amount']=$th_money;
                    $addData['integral_id']=$v['id'];
                    $addData['order_sn']=$order_sn;
                }else{
                    if($v['max_amount']>$outstatus+$th_money){
                        $addData['user_id']=$user_id;
                        $addData['integral_amount']=$th_money*$v['scale'];
                        $addData['has_integral_money']=$th_money;
                        $addData['amount']=$th_money;
                        $addData['status']=1;
                        $addData['integral_id']=$v['id'];
                        $addData['order_sn']=$order_sn;
                        //更新
                    }else{
                        $addData['user_id']=$user_id;
                        $addData['integral_amount']=($v['max_amount']-$outstatus)*$v['scale'];
                        $addData['has_integral_money']=$v['max_amount']-$outstatus;
                        $addData['amount']=$th_money;
                        $addData['status']=1;
                        $addData['integral_id']=$v['id'];
                        $addData['order_sn']=$order_sn;
                        //更新
                    }
                    foreach($allinter as $ak=>$av){
                        M('intergral_all','wa_')->where(["id"=>$av['id']])->save(["status"=>1,"integral_amount"=>$av["amount"]*$v["scale"],"has_integral_money"=>$av["amount"]]);
                    }
                }

            }
        }
        M('intergral_all','wa_')->add($addData);
    }
  
  
  
//  从无到有
    public function storeIntergral($arr,$user_id,$th_money){
        //积分规则ID
        $where["intergral_id"]=$arr["id"];
        $arr["intergral_id"]=$arr["id"];
        //用户ID
        $where["user_id"]=$user_id;
        //1代表到账    0代表未到账
        $where["status"]=1;
        //积分百分比
        $scale=$arr['scale'];
        //积分金额（到账）
        $intergral_all_ok["sumAmount"]=0.00;
        //已积分金额（到账）
        $intergral_all_ok["sumHasAmount"]=0.00;
        //积分金额与可积分金额不相等
        $intergral_odds=[];
        //到账积分列表
        $intergral_all=M('intergral_all','wa_')->where($where)->select();
        foreach ($intergral_all as $v){

            $intergral_all_ok["sumAmount"]+=$v["amount"];

            $intergral_all_ok["sumHasAmount"]+=$v["has_intergral_money"];

            if($v["amount"]!=$v["has_intergral_money"]){
                $intergral_odds[]=$v;
            }

        }
        $where["status"] = 0;

        if ($intergral_odds) {
            //存在已积分金额小于积分金额
            if ($intergral_all_ok["sumAmount"] < $arr["min_amount"]) {
                //积分金额小于最小积分金额
                //没到账的积分金额
                $intergral_all_no = M('intergral_all','wa_')->field("sum(amount) as sumAmount")->where($where)->find();
                if(!$intergral_all_no["sumAmount"]){
                    $intergral_all_no["sumAmount"]=0.00;
                }
                if (($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]) < $arr["min_amount"]) {
                    //未到账的积分金额+到账积分金额  小于 最小积分金额

                    if (($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"] + $th_money) < $arr["min_amount"]) {
                        //现在订单（该订单提货+未到账积分金额+到账积分金额  小于 最小积分金额）
                        $one_result = M('intergral_all','wa_')->where(["intergral_id" => $arr["intergral_id"], "user_id" => $user_id])->save(["status" => 0, "has_intergral_money" => 0, "intergral_amount" => 0]);
                        if ($one_result === false) {
                            return false;
                        } else {
                            return ["status" => 0, "has_intergral_money" => 0];
                        }
                    } else {
                        //现在订单（该订单提货+未到账积分金额+到账积分金额  大于 最小积分金额）
                        if (($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"] + $th_money) <= $arr["max_amount"]) {
                            //现在订单（该订单提货+未到账积分金额+到账积分金额  小于 最大积分金额）
                            //$two_result=M('intergral_all')->where(["intergral_id"=>$arr["intergral_id"],"user_id"=>$user_id])->save(["status"=>1,"has_intergral_money"=>]);
//                                $two_result = M()->query("update wa_intergral_all set status=1,has_intergral_money=amount,intergral_amount=amount*{$scale} WHERE intergral_id={$arr["intergral_id"]} and  user_id={$user_id}");
//                                if ($two_result === false) {
//                                    return false;
//                                } else {
//                                    return ["status" => 1, "has_intergral_money" => $th_money];
//                                }

                            $intergral_one=M('intergral_all','wa_')->where(['intergral_id'=>$arr["intergral_id"],'user_id'=>$user_id])->select();

                            foreach ($intergral_one as $inter_one){
                                $f_result=M('intergral_all','wa_')->where(['id'=>$inter_one['id']])->save(['status'=>1,'has_intergral_money'=>$inter_one['amount'],'intergral_amount'=>$inter_one['amount']*$scale]);
                                if ($f_result === false) {
                                    return false;
                                }
                            }
                            return ["status" => 1, "has_intergral_money" =>$th_money];




                        } else {
//                                $three_result = M()->query("update wa_intergral_all set status=1,has_intergral_money=amount,intergral_amount=amount*{$scale} WHERE intergral_id={$arr["intergral_id"]} and  user_id={$user_id}");
//                                if ($three_result === false) {
//                                    return false;
//                                } else {
//                                    return ["status" => 1, "has_intergral_money" => $arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"])];
//                                }


                            $intergral_one=M('intergral_all','wa_')->where(['intergral_id'=>$arr["intergral_id"],'user_id'=>$user_id])->select();

                            foreach ($intergral_one as $inter_one){
                                $f_result=M('intergral_all','wa_')->where(['id'=>$inter_one['id']])->save(['status'=>1,'has_intergral_money'=>$inter_one['amount'],'intergral_amount'=>$inter_one['amount']*$scale]);
                                if ($f_result === false) {
                                    return false;
                                }
                            }
                            if(($arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]))>$th_money){
                                $has_money=$th_money;
                            }else{
                                $has_money=$arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]);
                            }
                            return ["status" => 1, "has_intergral_money" =>$has_money];


                        }

                    }


                } else {
                    //---思考点
                    //(未到账积分金额+到账积分金额 大于 最小积分金额)
//                        $f_result = M()->query("update wa_intergral_all set status=1,has_intergral_money=amount,intergral_amount=amount*{$scale} WHERE intergral_id={$arr["intergral_id"]} and  user_id={$user_id} and status=1");
//                        if ($f_result === false) {
//                            return false;
//                        }

                    $intergral_one=M('intergral_all','wa_')->where(['intergral_id'=>$arr["intergral_id"],'user_id'=>$user_id,'status'=>1])->select();

                    foreach ($intergral_one as $inter_one){

                        $f_result=M('intergral_all','wa_')->where(['id'=>$inter_one['id']])->save(['status'=>1,'has_intergral_money'=>$inter_one['amount'],'intergral_amount'=>$inter_one['amount']*$scale]);
                        if ($f_result === false) {
                            return false;
                        }

                    }

                    $intergral_all_list = M('intergral_all')->where($where)->select();
                    $in_sum = $intergral_all_ok["sumAmount"];
                    foreach ($intergral_all_list as $k2 => $v2) {
                        $in_sum += $v2["amount"];
                        if ($in_sum < $arr["max_amount"]) {
                            $l_re = M('intergral_all','wa_')->where(["id" => $v2['id']])->save(["status" => 1, "has_intergral_money" => $v2["amount"], "intergral_amount" => $v2["amount"] * $v2["$scale"]]);
                            if ($l_re === false) {
                                return false;
                            }
                        } else {
                            $y_amount = $v2["amount"] - ($in_sum - $arr["max_amount"]);
                            $l_re = M('intergral_all','wa_')->where(["id" => $v2['id']])->save(["status" => 1, "has_intergral_money" => $y_amount, "intergral_amount" => $y_amount * $v2["$scale"]]);
                            if ($l_re === false) {
                                return false;
                            }
                            break;
                        }
                    }
                    if($in_sum < $arr["max_amount"]){
                        return ['status'=>1,"has_intergral_money"=>$arr["max_amount"]-$in_sum];
                    }else{
                        return ['status'=>0,"has_intergral_money"=>0];
                    }

                }

            } else {
                //到账积分金额等于大于最小积分金额




                $intergral_one=M('intergral_all','wa_')->where(['intergral_id'=>$arr["intergral_id"],'user_id'=>$user_id,'status'=>1])->select();
                $has_back=$intergral_all_ok["sumHasAmount"];
                foreach ($intergral_one as $inter_one){
                    $has_back+=$inter_one['amount']-$inter_one['has_intergral_money'];
                    if($has_back<$arr['max_amount']){
                        $f_result=M('intergral_all','wa_')->where(['id'=>$inter_one['id']])->save(['status'=>1,'has_intergral_money'=>$inter_one['amount'],'intergral_amount'=>$inter_one['amount']*$scale]);
                        if ($f_result === false) {
                            return false;
                        }
                    }else{
                        $f_result=M('intergral_all','wa_')->where(['id'=>$inter_one['id']])->save(['status'=>1,'has_intergral_money'=>$arr['max_amount']-($has_back-($inter_one['amount']-$inter_one['has_intergral_money'])),'intergral_amount'=>($arr['max_amount']-($has_back-($inter_one['amount']-$inter_one['has_intergral_money'])))*$scale]);
                        if ($f_result === false) {
                            return false;
                        }else{
                            return ['status'=>0,'has_intergral_money'=>0];
                        }
                    }
                }

                $intergral_all_no = M('intergral_all','wa_')->field("sum(amount) as sumAmount")->where($where)->find();
                if(!$intergral_all_no["sumAmount"]){
                    $intergral_all_no["sumAmount"]=0.00;
                }
                if (($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]) < $arr["max_amount"]) {
                    //(未到账积分金额+到账积分金额 小于 最大积分金额)
//                        $f_result = M()->query("update wa_intergral_all set status=1,has_intergral_money=amount,intergral_amount=amount*{$scale} WHERE intergral_id={$arr["intergral_id"]} and  user_id={$user_id}");
//                        if ($f_result === false) {
//                            return false;
//                        } else {
//                            return ["status" => 1, "has_intergral_money" => $arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"])];
//                        }

                    $intergral_one=M('intergral_all','wa_')->where(['intergral_id'=>$arr["intergral_id"],'user_id'=>$user_id,'status'=>0])->select();

                    foreach ($intergral_one as $inter_one){
                        $f_result=M('intergral_all','wa_')->where(['id'=>$inter_one['id']])->save(['status'=>1,'has_intergral_money'=>$inter_one['amount'],'intergral_amount'=>$inter_one['amount']*$scale]);
                        if ($f_result === false) {
                            return false;
                        }
                    }

                    if(($arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]))>$th_money){
                        $has_money=$th_money;
                    }else{
                        $has_money=$arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]);
                    }
                    return ["status" => 1, "has_intergral_money" =>$has_money];


                } else {
                    //(未到账积分金额+到账积分金额 大于 最大积分金额)
                    $intergral_all_list = M('intergral_all','wa_')->where($where)->select();
                    $in_sum = $intergral_all_ok["sumAmount"];
                    foreach ($intergral_all_list as $k2 => $v2) {
                        $in_sum += $v2["amount"];
                        if ($in_sum < $arr["max_amount"]) {
                            $l_re = M('intergral_all','wa_')->where(["id" => $v2['id']])->save(["status" => 1, "has_intergral_money" => $v2["amount"], "intergral_amount" => $v2["amount"] * $v2["$scale"]]);
                            if ($l_re === false) {
                                return false;
                            }
                        } else {
                            $y_amount = $v2["amount"] - ($in_sum - $arr["max_amount"]);
                            $l_re = M('intergral_all','wa_')->where(["id" => $v2['id']])->save(["status" => 1, "has_intergral_money" => $y_amount, "intergral_amount" => $y_amount * $v2["$scale"]]);
                            if ($l_re === false) {
                                return false;
                            }
                        }
                    }

                    return ['status'=>0,"has_intergral_money"=>0];


                }


            }


















        } else {



            if ($intergral_all_ok["sumAmount"] < $arr["min_amount"]) {
                //到账积分金额小于最小可积分金额
                //未到账积分金额
                $intergral_all_no = M('intergral_all','wa_')->field("sum(amount) as sumAmount")->where($where)->find();
                if(!$intergral_all_no["sumAmount"]){
                    $intergral_all_no["sumAmount"]=0.00;
                }
                if (($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]) < $arr["min_amount"]) {
                    //未到账积分金额+到账积分金额  小于 最小积分金额
                    if (($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"] + $th_money) < $arr["min_amount"]) {
                        //现在订单（该订单提货+未到账积分金额+到账积分金额  小于 最小积分金额）
                        $one_result = M('intergral_all')->where(["intergral_id" => $arr["intergral_id"], "user_id" => $user_id])->save(["status" => 0, "has_intergral_money" => 0, "intergral_amount" => 0]);
                        if ($one_result === false) {
                            return false;
                        } else {
                            return ["status" => 0, "has_intergral_money" => 0];
                        }



                    } else {

                        //现在订单（该订单提货+未到账积分金额+到账积分金额  大于 最小积分金额）
                        if (($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"] + $th_money) <= $arr["max_amount"]) {
                            //现在订单（该订单提货+未到账积分金额+到账积分金额  小于 最大积分金额）
                            //$two_result=M('intergral_all')->where(["intergral_id"=>$arr["intergral_id"],"user_id"=>$user_id])->save(["status"=>1,"has_intergral_money"=>]);
//                                $two_result = M()->query("update intergral_all set status=1,has_intergral_money=amount,intergral_amount=amount*{$scale} WHERE intergral_id={$arr["intergral_id"]} and  user_id={$user_id}");
//                                if ($two_result === false) {
//                                    return false;
//                                } else {
//                                    return ["status" => 1, "has_intergral_money" => $th_money];
//                                }

                            $intergral_one=M('intergral_all','wa_')->where(['intergral_id'=>$arr["intergral_id"],'user_id'=>$user_id,'status'=>0])->select();

                            foreach ($intergral_one as $inter_one){
                                $f_result=M('intergral_all','wa_')->where(['id'=>$inter_one['id']])->save(['status'=>1,'has_intergral_money'=>$inter_one['amount'],'intergral_amount'=>$inter_one['amount']*$scale]);
                                if ($f_result === false) {
                                    return false;
                                }
                            }
                            return ["status" => 1, "has_intergral_money" =>$th_money];




                        } else {
//                                $three_result = M()->query("update intergral_all set status=1,has_intergral_money=amount,intergral_amount=amount*{$scale} WHERE intergral_id={$arr["intergral_id"]} and  user_id={$user_id}");
//                                if ($three_result === false) {
//                                    return false;
//                                } else {
//                                    return ["status" => 1, "has_intergral_money" => $arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"])];
//                                }



                            $intergral_one=M('intergral_all','wa_')->where(['intergral_id'=>$arr["intergral_id"],'user_id'=>$user_id,'status'=>0])->select();

                            foreach ($intergral_one as $inter_one){
                                $f_result=M('intergral_all','wa_')->where(['id'=>$inter_one['id']])->save(['status'=>1,'has_intergral_money'=>$inter_one['amount'],'intergral_amount'=>$inter_one['amount']*$scale]);
                                if ($f_result === false) {
                                    return false;
                                }
                            }
                            if(($arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]))<$th_money){
                                $has_money=$arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]);
                            }else{
                                $has_money=$th_money;
                            }
                            return ["status" => 1, "has_intergral_money" =>$has_money];





                        }


                    }




                } else {

                    if (($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]) < $arr["max_amount"]) {
                        //现在订单（该订单提货+未到账积分金额+到账积分金额  小于 最小积分金额）
//                            $th_result = M()->query("update wa_intergral_all set status=1,has_intergral_money=amount,intergral_amount=amount*{$scale} WHERE intergral_id={$arr["intergral_id"]} and  user_id={$user_id}");
//                            if ($th_result === false) {
//                                return false;
//                            } else {
//                                return ["status" => 1, "has_intergral_money" => $arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"])];
//                            }

                        $intergral_one=M('intergral_all','wa_')->where(['intergral_id'=>$arr["intergral_id"],'user_id'=>$user_id,'status'=>0])->select();

                        foreach ($intergral_one as $inter_one){
                            $f_result=M('intergral_all','wa_')->where(['id'=>$inter_one['id']])->save(['status'=>1,'has_intergral_money'=>$inter_one['amount'],'intergral_amount'=>$inter_one['amount']*$scale]);
                            if ($f_result === false) {
                                return false;
                            }
                        }

                        if( ($arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]))>$th_money){
                            $has_money=$th_money;
                        }else{
                            $has_money=$arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]);
                        }
                        return ["status" => 1, "has_intergral_money" => $has_money];


                    } else {

                        $intergral_all_list = M('intergral_all')->where($where)->select();
                        $in_sum = $intergral_all_ok["sumAmount"];
                        foreach ($intergral_all_list as $k4 => $v4) {
                            $in_sum += $v4["amount"];
                            if ($in_sum < $arr["max_amount"]) {
                                $l_re = M('intergral_all','wa_')->where(["id" => $v4['id']])->save(["status" => 1, "has_intergral_money" => $v4["amount"], "intergral_amount" => $v4["amount"] * $v4["$scale"]]);
                                if ($l_re === false) {
                                    return false;
                                }
                            } else {
                                $y_amount = $v4["amount"] - ($in_sum - $arr["max_amount"]);
                                $l_re = M('intergral_all','wa_')->where(["id" => $v4['id']])->save(["status" => 1, "has_intergral_money" => $y_amount, "intergral_amount" => $y_amount * $v4["$scale"]]);
                                if ($l_re === false) {
                                    return false;
                                }
                            }
                        }

                        return ["status" => 0, "has_intergral_money" => 0];

                    }


                }


            }else {
                //到账积分金额等于大于最小积分金额
                $intergral_all_no = M('intergral_all','wa_')->field("sum(amount) as sumAmount")->where($where)->find();
                if(!$intergral_all_no['sumAmount']){
                    $intergral_all_no['sumAmount']=0.00;
                }
                if (($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]) < $arr["max_amount"]) {
                    //(未到账积分金额+到账积分金额 小于 最大积分金额)
//                        $f_result = M()->query("update wa_intergral_all set status=1,has_intergral_money=amount,intergral_amount=amount*{$scale} WHERE intergral_id={$arr["intergral_id"]} and  user_id={$user_id}");
//                        echo M()->getLastSql();
//                        if ($f_result === false) {
//                            echo 222;
//                            return false;
//                        }else{
//                            return ["status" => 1, "has_intergral_money" => $arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"])];
//                        }
                    $intergral_one=M('intergral_all','wa_')->where(['intergral_id'=>$arr["intergral_id"],'user_id'=>$user_id,'status'=>0])->select();

                    foreach ($intergral_one as $inter_one){
                        $f_result=M('intergral_all','wa_')->where(['id'=>$inter_one['id']])->save(['status'=>1,'has_intergral_money'=>$inter_one['amount'],'intergral_amount'=>$inter_one['amount']*$scale]);
                        if ($f_result === false) {
                            return false;
                        }
                    }
                    if(($arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]))>$th_money){
                        $has_money=$th_money;
                    }else{
                        $has_money=$arr["max_amount"] - ($intergral_all_no["sumAmount"] + $intergral_all_ok["sumAmount"]);
                    }
                    return ["status" => 1, "has_intergral_money" => $has_money];
                } else {
                    //(未到账积分金额+到账积分金额 大于 最大积分金额)
//                        $f_result = M()->query("update wa_intergral_all set status=1,has_intergral_money=amount,intergral_amount=amount*{$scale} WHERE intergral_id={$arr["intergral_id"]} and  user_id={$user_id} and status=1");
//                        if ($f_result === false) {
//                            return false;
//                        }
                    $intergral_all_list = M('intergral_all','wa_')->where($where)->select();
                    $in_sum = $intergral_all_ok["sumAmount"];
                    foreach ($intergral_all_list as $k2 => $v2) {
                        $in_sum += $v2["amount"];
                        if ($in_sum < $arr["max_amount"]) {
                            $l_re = M('intergral_all','wa_')->where(["id" => $v2['id']])->save(["status" => 1, "has_intergral_money" => $v2["amount"], "intergral_amount" => $v2["amount"] * $v2["$scale"]]);
                            if ($l_re === false) {
                                return false;
                            }
                        } else {
                            $y_amount = $v2["amount"] - ($in_sum - $arr["max_amount"]);
                            $l_re = M('intergral_all','wa_')->where(["id" => $v2['id']])->save(["status" => 1, "has_intergral_money" => $y_amount, "intergral_amount" => $y_amount * $v2["$scale"]]);
                            if ($l_re === false) {
                                return false;
                            }
                            break;
                        }
                    }

                    return ['status'=>0,"has_intergral_money"=>0];


                }


            }





















        }






    }
  
  
}