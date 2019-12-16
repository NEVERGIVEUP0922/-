<?php
namespace Api\Controller;

/**
 * 支付宝
 */
class AlipayController extends BaseController{

    /**
     * return_url接收页面
     */
    public function alipay_return(){
        // 引入支付宝
        vendor('Alipay.AlipayNotify','','.class.php');
        $config=$config=C('ALIPAY_CONFIG');
        $notify=new \AlipayNotify($config);
        // 验证支付数据
        $status=$notify->verifyReturn();

        if($status){
            // 下面写验证通过的逻辑 比如说更改订单状态等等 $_GET['out_trade_no'] 为订单号；
            list($time_,$order_sn,$pay_type)=explode('_',$_GET['out_trade_no']);
            $this->redirect('Home/Order/myOrder',['error'=>0,'msg'=>'支付成功','data'=>$order_sn]);
        }else{
            $this->redirect('Home/Order/myOrder',['error'=>1,'msg'=>'支付失败']);
        }
    }
    
    /**
     * notify_url接收页面
     */
    public function alipay_notify12(){
        // 引入支付宝  
      	$pay_m=new \Home\Controller\PayController();
        vendor('Alipay.AlipayNotify','','.class.php');
        $config=$config=C('ALIPAY_CONFIG');
        $alipayNotify = new \AlipayNotify($config);
        $result=$_POST;
        // 验证支付数据
        $verify_result = $alipayNotify->verifyNotify();
        $this_time=date('YmdHis',time());
        //数据测试
//        $verify_result=1;
//        $result=[
//            'discount' => '0.00',
//            'payment_type' => '1',
//            'trade_no' => '2018010421001004220220770525',
//            'subject' => '玖隆芯城订单号：2017110100003',
//            'buyer_email' => '157****6290',
//            'gmt_create' => '2017-11-03 17:46:52',
//            'notify_type' => 'trade_status_sync',
//            'quantity' => '1',
//            'out_trade_no' => '151505334318010411201441',
//            'seller_id' => '2088821327534465',
//            'notify_time' => '2017-11-04 03:11:49',
//            'trade_status' => 'TRADE_SUCCESS',
//            'is_total_fee_adjust' => 'N',
//            'total_fee' => '0.01',
//            'gmt_payment' => '2017-11-03 17:47:23',
//            'seller_email' => '18902476196@189.cn',
//            'price' => '0.01',
//            'buyer_id' => '2088522563745221',
//            'notify_id' => 'abcffc5fc1453359eb40de8236d8992hp6',
//            'use_coupon' => 'N',
//            'sign_type' => 'MD5',
//            'sign' => '3ab2fe23596d3768b0278b853eb9c08f',
//        ];

        $destination = C('LOG_PATH').'pay_alipay_'.date('y_m_d').'.log';
        \Think\Log::write(serialize($result),'INFO','',$destination);

        if($verify_result) {
            echo "success";
            // 下面写验证通过的逻辑 比如说更改订单状态等等 $_POST['out_trade_no'] 为订单号；
            $type='alipay';
            $pay_name='支付宝支付';

            $alipay_config=C('ALIPAY_CONFIG');
            if($alipay_config['partner']!=$result['seller_id']){//seller_id验证
                $pay_m=new \Home\Controller\PayController();
                $pay_m->wechatCallbackNotify($result,['error'=>1,'msg'=>'seller_id验证错误'],$type);//记录回调信息
                die();
            }

            $pay_type=substr($result['out_trade_no'],-1,1);
			
            if($pay_type==1){//商城订单微信扫码支付
                $order_sn=substr($result['out_trade_no'],10,-1);
                $pay_m=new \Home\Controller\PayController();
              file_put_contents("44.txt",$order_sn);
                $order_result=$pay_m->scanQRcodePayResult($result,$type,$pay_name);//改变订单信息
                file_put_contents("444.txt",$order_sn);
                $pay_m->wechatCallbackNotify($result,$order_result,$type);//记录回调信息
            }else if($pay_type==2){//账期还款微信扫码支付
                $order_sn=substr($result['out_trade_no'],14,-1);

                $pay_m=new \Home\Controller\PayController();
                $account_result=$pay_m->QRcodeRepaymentAccountResult($result,$type,$pay_name);//改变账期信息
                $pay_m->wechatCallbackNotify($result,$account_result,$type);//记录回调信息
            }
        }
    }


}