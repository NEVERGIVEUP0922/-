<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-12-28
 * Time: 17:41
 */

namespace EES\Controller;

use EES\Event\BaseEvent;
use EES\Model\ProductModel;
use EES\Sdk\Client;
use Think\Exception;
use Think\Log;
use EES\Event\EventFactory;
use EES\Event\OrderEvent;
use EES\System\Redis;

class NoticeController extends EESController
{
    /*
     * 接收来自EES的通知推送 并存储到数据库
     *
     */
    public function receive()
    {
        $data = I( 'post.' );
        if ( empty( $data ) ) {
            $this->ajaxReturn( [ 'status' => false, 'msg' => '通知数据为空' ] );
        }
        $requireVerify = [
            'act_no'   => '操作编码',
            'category' => '操作分类',
            'action'   => '操作类型',
            'pk_value' => '操作主键值',
        ];
        $requireRes = $this->checkRequire( $requireVerify, $data );
        if ( $requireRes[ 'code' ] > 0 ) {
            $this->ajaxReturn( [ 'status' => false, 'msg' => $requireRes[ 'msg' ] ] );
        }
        !empty( $data[ 'change' ] ) && $add[ 'change' ] = json_encode( $data[ 'change' ] );
        $add[ 'act_no' ] = $data[ 'act_no' ];
        $add[ 'category' ] = $data[ 'category' ];
        $add[ 'action' ] = $data[ 'action' ];
        $add[ 'pk_value' ] = $data[ 'pk_value' ];

        $isExists = M( 'ees_action' )->where( [ 'act_no' => $data[ 'act_no' ],'pk_value'=>$add['pk_value'] ] )->find();
        if ( !$isExists ) {
            $res = M( 'ees_action' )->add( $add );
        } else {
            if ( $isExists[ 'is_store' ] == 1 ) {
                $res = ( new OrderEvent() )->sendStoreResult( $data[ 'act_no' ] );
            } else {
                $res = true;
            }
        }
        //扫描处理事件通知(不接受结果 相当于异步)
		_curl( trim( C( 'SHOP_EES' ), '/' ) . '/Notice/storeReceive' );
        if ( $res === false ) {
            Log::write( '通知数据写入数据库失败.数据:'.json_encode($data), 'WARN' );
            $this->ajaxReturn( [ 'status' => false, 'msg' => '通知数据写入数据库失败' ] );
        } else {
            $this->ajaxReturn( [ 'status' => true ] );
        }
    }

    /**
     *
     * 获取未处理的事件条数
    **/
    public function getNoStoreListNum()
    {
        $res = count( $this->getNoStoreList() );
        $this->ajaxReturn(['num'=>$res]);
    }

    /*
     * 扫描数据库进行事件处理
     *
     */
    public function storeReceive()
    {
     
        $res = $this->getNoStoreList();
        if( empty( $res ) ){
            return  null;
        }
        foreach ( $res as $k => $v ) {
			$redis=Redis::getInstance();
			$v=@unserialize($v);
        	if ($v['category']=='product_qty'||$v['category']=='productInfo_change'||$v['category']=='product_price'){
        		    $acNo=$redis->hGet('shoperpsyncEvent2','product'.$v['change']['itemNo']);
        		    if($acNo){
        		    	if($acNo<$v['no']){
							$redis->hSet('shoperpsyncEvent2','product'.$v['change']['itemNo'],$v['no']);
						}elseif($acNo==$v['no']){
      						//continue;
						}else{
							$redis->sRem('shoperpsyncEvent',serialize($v));
							continue;
						}
					}else{
						$redis->hSet('shoperpsyncEvent2','product'.$v['change']['itemNo'],$v['no']);
					}
					$k='product'.$v['change']['itemNo'];
     
			}elseif($v['category']=='custinfor_debt'||$v['category']=='custinfor_info'){
				$acNo=$redis->hGet('shoperpsyncEvent2','custinfor'.$v['change']['fcustno']);
				if($acNo){
					if($acNo<$v['no']){
						$redis->hSet('shoperpsyncEvent2','custinfor'.$v['change']['fcustno'],$v['no']);
					}elseif($acNo==$v['no']){
					//	continue;
					}else{
						$redis->sRem('shoperpsyncEvent',serialize($v));
						continue;
					}
				}else{
					$redis->hSet('shoperpsyncEvent2','custinfor'.$v['change']['fcustno'],$v['no']);
				}
		
			}elseif ($v['category']=='empl_info'){
				$acNo=$redis->hGet('shoperpsyncEvent2','empl'.$v['change']['emplNo']);
				if($acNo){
					if($acNo<$v['no']){
						$redis->hSet('shoperpsyncEvent2','empl'.$v['change']['emplNo'],$v['no']);
					}elseif($acNo==$v['no']){
					//	continue;
//                        $is_l=$redis->hGet('shoperpsyncEvent3',serialize($v));
//                        if(!$is_l){
//                            continue;
//                        }
					}else{
						$redis->sRem('shoperpsyncEvent',serialize($v));
						continue;
					}
				}else{
					$redis->hSet('shoperpsyncEvent2','empl'.$v['change']['emplNo'],$v['no']);
				}
		
			}elseif ($v['category']=='order_sdoutcomplate'){
				$acNo=$redis->hGet('shoperpsyncEvent2','order_sdoutcomplate'.$v['change']['order_no']);
				if($acNo){
					if($acNo<$v['no']){
						$redis->hSet('shoperpsyncEvent2','order_sdoutcomplate'.$v['change']['order_no'],$v['no']);
					}elseif($acNo==$v['no']){
					//	  $is_l=$redis->hGet('shoperpsyncEvent3',serialize($v));
//                        if(!$is_l){
//                            continue;
//                        }
					}else{
						$redis->sRem('shoperpsyncEvent',serialize($v));
						continue;
					}
				}else{
					$redis->hSet('shoperpsyncEvent2','order_sdoutcomplate'.$v['change']['order_no'],$v['no']);
				}
				
				$acNo=$redis->hGet('shoperpsyncEvent2','order_credit'.$v['change']['order_no']);
				if($acNo){
					if($acNo>$v['no']){
						$v['change']['is_not']=1;
						//$redis->hSet('shoperpsyncEvent2','order_sdoutcomplate'.$v['change']['order_no'],$v['no']);
					}
				}
			}elseif ($v['category']=='order_credit'){
				$acNo=$redis->hGet('shoperpsyncEvent2','order_sdoutcomplate'.$v['change']['order_no']);
				if($acNo){
					if($acNo>$v['no']){
						$redis->sRem('shoperpsyncEvent',serialize($v));
						continue;
						//$redis->hSet('shoperpsyncEvent2','order_sdoutcomplate'.$v['change']['order_no'],$v['no']);
					}
				}
				$acNo=$redis->hGet('shoperpsyncEvent2','order_credit'.$v['change']['order_no']);
				if($acNo){
					if($acNo<$v['no']){
						$redis->hSet('shoperpsyncEvent2','order_credit'.$v['change']['order_no'],$v['no']);
					}elseif($acNo==$v['no']){
//                        $is_l=$redis->hGet('shoperpsyncEvent3',serialize($v));
//                        if(!$is_l){
//                            continue;
//                        }
					}else{
						$redis->sRem('shoperpsyncEvent',serialize($v));
						continue;
					}
				}else{
					$redis->hSet('shoperpsyncEvent2','order_credit'.$v['change']['order_no'],$v['no']);
				}
			}else{
				$acNo=$redis->hGet('shoperpsyncEvent2',$v['category'].$v['change']['order_no'].$v['change']['th_no']);
				if($acNo){
					if($acNo<$v['no']){

						$redis->hSet('shoperpsyncEvent2',$v['category'].$v['change']['order_no'].$v['change']['th_no'],$v['no']);
					}elseif($acNo==$v['no']){
//                        $is_l=$redis->hGet('shoperpsyncEvent3',serialize($v));
//                        if(!$is_l){
//                            continue;
//                        }
					}else{
						$redis->sRem('shoperpsyncEvent',serialize($v));
						continue;
					}
				}else{
					$redis->hSet('shoperpsyncEvent2',$v['category'].$v['change']['order_no'].$v['change']['th_no'],$v['no']);
				}
			}
            //根据不同事件分类 调用不同实例处理
//			$red=$redis->sAdd('shoperpsyncEvent1',serialize($v));
//			if (!$red){
//				continue;
//			}
           // print_r($v);die();
            $result = false;
            $event = EventFactory::getEventService( $v[ 'category' ] );
            !$event && Log::write( '实例化事件处理对象失败: ' . $v[ 'act_no' ] . '---' . $v[ 'category' ] );
            try {
                switch ( $v[ 'action' ] ) {
                    case 'update':
                        //执行处理
                        $result = $event->updateStore( $v );
                        break;
                    case 'insert':
                        $result = $event->insertStore( $v );
                        break;
                    case 'delete':
                        //执行处理
                        $result = $event->deleteStore( $v );
                        break;
                }
            } catch ( Exception $ex ) {
                Log::write( '通知事件' . $v[ 'act_no' ] . '处理失败' );
            }
            if( $result ){
                $redis->hDel('shoperpsyncEvent3',serialize($v));
            	if(isset($v['change']['is_not'])){
            		unset($v['change']['is_not']);
				}
                $event->sendStoreResult( $v );
            }
        }

        return true;
    }

    /*
     *  获取未处理的事件列表
     */
    public function getNoStoreList()
    {
		$redis=Redis::getInstance();
		$res=$redis->sRandMember( 'shoperpsyncEvent' ,50);
		return $res;
        $actions = [];
        $model = M('ees_action');
        $result = $model->field('pk_value,change,category,action,act_no,create_at')->where( [ 'is_store' => 0 ] )->order('create_at desc')->group('pk_value,category')->select();
        foreach( $result as $k=>$v ){
        	//检查在其之后是否有同样数据已处理的事件
			$where['pk_value'] = $v['pk_value'];
			$where['category'] = $v['category'];
			$where['action'] = $v['action'];
			$where['create_at']= ['egt', $v['create_at']];
			$where['is_store'] = 1;
			$storeRes = $model->field('act_no')->where($where)->find();
			//找到就删除这条事件
			if( $storeRes ){
				$model->where(['act_no'=>$v['act_no']])->delete();
			}else{
				$actions[] = $v;
			}
			//把之前相同的未处理的事件删掉
			$time = $v['create_at'];
			$map['pk_value'] = $v['pk_value'];
			$map['category'] = $v['category'];
			$map['action'] = $v['action'];
			$map['is_store'] = 0;
			$map['create_at'] = ['elt', $time];
			$map['act_no'] = ['neq',$v['act_no']];
			$model->where( $map  )->delete();
        }
        return $actions;
    }


    /*
     * 弃用
     *
     */
    public function getAllProduct()
    {
        set_time_limit( 0 );           // 设置执行不超时
        $pModel = new ProductModel();
        $items = M( '', 'erp_product' )->field( 'fitemno' )->where( 'id > 12445 ' )->select();
        foreach ( $items as $k => $v ) {
            $res = $this->requestEes( [ 'itemNo' => $v[ 'fitemno' ] ], 'App.Product.GetItemRealStockNum' );
            if ( $res[ 'status' ] ) {
                $pModel->changeQty( $v[ 'fitemno' ], $res[ 'data' ][ 'qty' ] );
            } else {
                continue;
            }
            $priceRes = $this->requestEes( [ 'itemNo' => $v[ 'fitemno' ] ], 'App.Product.GetItemStcostPrice' );
            if ( $priceRes[ 'status' ] ) {
                $pModel->changePrice( $v[ 'fitemno' ], $priceRes[ 'data' ][ 'price' ] );
            } else {
                continue;
            }
            break;
        }
    }


    public function testSameCust()
    {
        $cl = Client::create();
        $data = [
            'data'=>  [
                //user表的数据
                'user_name'=>'测试',
                'user_mobile'=> '17688862039',
                'user_type'=>2, //用户分类 1是个人 2是企业
                'user_email'=>'',//联系人邮箱
                //['info'] 里面 个人放normal表的数据 企业方company表的数据
                'info'=>[
                    'company_name'=>'深圳市百惠通科技有限公司', //企业名称
                    'company_people_num'=>130, //企业人数
                    'company_city'=>'',//企业所在城市
                    'company_address'=>'深圳南山区粤海街道南二道',//企业地址(完整地址)
                    'company_business_nature'=> '',//企业经营性质
                    'company_business_product'=>'',//企业经营产品
                    'company_business_brand'=>'',//企业经营品牌
                    'company_annual_turnover'=> '',//企业年营业额
                    'company_sales_channel'=> '',//企业销售渠道
                    'company_user_name'=>'浩昌',
                    'company_user_sector'=>'大芯数据', //联系人部门
                    'company_user_position'=>'web前端',//联系人职务
                    'company_phone_num'=>'0755-27857059',//联系人手机
                    'company_user_qq'  =>'1234556',//联系人QQ
                    'company_user_wechat'=>'wexhsisj',//联系人微信
                ],
                //['address'] 里面放收货地址的信息
                'address'=>[
                    [
                        'consignee'=>'叶浩昌',
                        'address'=>'富安科技大厦A302',        //请把商城当时下单时使用的收货地址信息放在 第一个数组元素内
                        'zipcode'=>'723100',
                        'mobile'=>'0755-27857059',
                    ],
                    [
                        'consignee'=>'叶昌',
                        'address'=>'富安科技大厦302',
                        'zipcode'=>'723100',
                        'mobile'=>'123441222',
                    ],
                    [
                        'consignee'=>'号昌',
                        'address'=>'富安科技大厦02',
                        'zipcode'=>'72300',
                        'mobile'=>'123441222',
                    ],
                ],
            ],
        ];
        $res = $cl->withHost('http://192.168.6.44/')
            ->withService('App.Custinfor.GetSameCustList')
            ->setData($data)
            ->send();
       de("SELECT TOP 20 fcustno, fcustname, fcustjc ,faddress, fshaddress,
        fcontactor1, fcontactor2, fshcontactor, ftel, fmobile1,
        fmobile2, ftel1, ftel2, fshtel, fshmobile, fqq1, fqq2, fmsn1, fmsn2 FROM t_custinfor WHERE 
        fcustname like '%深圳市百惠通科技有限公司%' OR fcustjc like '%深圳市百惠通科技有限公司%' 
        OR fcustname like '%深圳市百惠通%' OR fcustjc like '%深圳市百惠通%'
        OR faddress like '%深圳南山区粤海街道南二道%' OR fshaddress like '%深圳南山区粤海街道南二道%' 
        OR fcontactor1 like '%浩昌%' OR fcontactor2 like '%浩昌%' OR fshcontactor like '%浩昌%' 
        OR fcontactor1 like '%浩昌%' OR fcontactor2 like '%浩昌%' OR fshcontactor like '%浩昌%' 
        OR fmobile1='17688862039' OR ftel='17688862039' OR fmobile2='17688862039' OR ftel1='17688862039' OR ftel2='17688862039' OR fshtel='17688862039' OR fshmobile='17688862039' 
        OR fmobile1='0755-27857059' OR ftel='0755-27857059' OR fmobile2='0755-27857059' OR ftel1='0755-27857059' OR ftel2='0755-27857059' OR fshtel='0755-27857059' OR fshmobile='0755-27857059' 
        OR faddress like '%富安科技大厦A302%' OR fshaddress like '%富安科技大厦A302%' 
        OR fcontactor1 like '%叶浩昌%' OR fcontactor2 like '%叶浩昌%' OR fshcontactor like '%叶浩昌%' 
        OR ftel='0755-27857059' OR fmobile1='0755-27857059' OR fmobile2='0755-27857059' OR ftel1='0755-27857059' OR ftel2='0755-27857059' OR fshtel='0755-27857059' OR fshmobile='0755-27857059' 
        OR faddress like '%富安科技大厦302%' OR fshaddress like '%富安科技大厦302%' 
        OR fcontactor1 like '%叶昌%' OR fcontactor2 like '%叶昌%' OR fshcontactor like '%叶昌%' 
        OR ftel='123441222' OR fmobile1='123441222' OR fmobile2='123441222' OR ftel1='123441222' OR ftel2='123441222' OR fshtel='123441222' OR fshmobile='123441222' 
        OR faddress like '%富安科技大厦02%' OR fshaddress like '%富安科技大厦02%' 
        OR fcontactor1 like '%号昌%' OR fcontactor2 like '%号昌%' OR fshcontactor like '%号昌%' 
        OR ftel='123441222' OR fmobile1='123441222' OR fmobile2='123441222' OR ftel1='123441222' OR ftel2='123441222' OR fshtel='123441222' OR fshmobile='123441222' 
        OR fqq1='1234556' OR fqq2='1234556' 
        OR fmsn1='wexhsisj' OR fmsn2='wexhsisj'
        ");
    }
}