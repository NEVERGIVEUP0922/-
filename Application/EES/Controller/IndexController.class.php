<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-12-28
 * Time: 17:48
 */

namespace EES\Controller;

use EES\Event\CustinforEvent;
use EES\Event\ProductInfoEvent;
use EES\Sdk\Curl;
use Home\Controller\KdController;
use Think\Log;
use EES\System\Redis;

class IndexController extends EESController
{
    public function index()
    {
    	de(1);
//    	de( $this->getCustNoSyncOrder('K008743') );
//		$redis = Redis::getInstance('39.108.228.82','6379','sztjldaxinshuju');
//		$rs = $redis->sAdd( 'shopOrderSyncList', '1803291396181' );
//		de( $rs );
//    	$nt = new OrderController();
//		$model= M('order_sync');
//		$res = $model->field('s.order_sn')->alias('s')->join('__ORDER__ as o ON o.order_sn = s.order_sn','left')->join('__ORDER_SYNC_LOG__ as l ON l.order_sn = s.order_sn')->where('')->where(['s.sync_status'=>0,'o.order_status'=>['lt',100]])->select();
//    	de( $res );
		//$redis = Redis::getInstance();
		//$redis->sAdd( '123', 12313 );
//        $kd = new CustinforEvent();
//        $data = M('ees_action')->where(['act_no'=>1390])->find();
//        $res = $kd->insertStore($data);
//        de( $res );
    }
}