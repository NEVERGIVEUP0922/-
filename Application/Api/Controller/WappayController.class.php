<?php
namespace Api\Controller;
/**
 * 银联支付
 */
class WappayController extends BaseController{

    /**
     * notify_url接收页面
     */
    public function notify(){
        $post=I('post.');
      file_put_contents("818.txt",json_encode($post));
        //测试数据
//        $post=[
//            'accNo' => '6216***********0018',
//            'accessType' => '0',
//            'bizType' => '000201',
//            'currencyCode' => '156',
//            'encoding' => 'utf-8',
//            'merId' => '777290058110048',
//            'orderId' => '151728438118013010054221',
//            'payCardType' => '01',
//            'payType' => '0001',
//            'queryId' => '201711161109043076818',
//            'respCode' => '00',
//            'respMsg' => '成功[0000000]',
//            'settleAmt' => '14740',
//            'settleCurrencyCode' => '156',
//            'settleDate' => '1116',
//            'signMethod' => '01',
//            'signPubKeyCert' =>'',
//            'traceNo' => '307681',
//            'traceTime' => '1116110904',
//            'txnAmt' => '14740',
//            'txnSubType' => '01',
//            'txnTime' => '20171116110904',
//            'txnType' => '01',
//            'version' => '5.1.0',
//            'signature' =>'',
//        ];
        $path__='/ThinkPHP/Library/Vendor/Wappay';
        include_once $_SERVER ['DOCUMENT_ROOT'] .$path__ .'/upacp_demo_b2c/sdk/acp_service.php';

        echo 200;

        $result=$post;
        $result['out_trade_no']=$result['orderId'];
        $result['total_fee']=$post['txnAmt'];
        $type='wappay';
        $pay_name='银联网关支付';

        $destination = C('LOG_PATH').'pay_wappay_'.date('y_m_d').'.log';
        \Think\Log::write(serialize($result),'INFO','',$destination);

        //验证签名
        $verify=\com\unionpay\acp\sdk\AcpService::validate ( $_POST ) ? true : false;
        if(!$verify){//merId验证
            $pay_m=new \Home\Controller\PayController();
            $pay_m->wechatCallbackNotify($result,['error'=>1,'msg'=>'验证签名错误'],$type);//记录回调信息
            die();
        }

        $wappay_config=C('WAPPAY_CONFIG');
        if($wappay_config['MERID']!=$result['merId']){//merId验证
            $pay_m=new \Home\Controller\PayController();
            $pay_m->wechatCallbackNotify($result,['error'=>1,'msg'=>'merId验证错误'],$type);//记录回调信息
            die();
        }

        $pay_type=substr($result['out_trade_no'],-1,1);
        $order_sn=substr($result['out_trade_no'],10,-1);

        if($pay_type==1){//商城订单微信扫码支付
            $pay_m=new \Home\Controller\PayController();
            $order_result=$pay_m->scanQRcodePayResult($result,$type,$pay_name);//改变订单信息
            $pay_m->wechatCallbackNotify($result,$order_result,$type);//记录回调信息
        }else if($pay_type==2){//账期还款微信扫码支付
            $pay_m=new \Home\Controller\PayController();
            $account_result=$pay_m->QRcodeRepaymentAccountResult($result,$type,$pay_name);//改变账期信息
            $pay_m->wechatCallbackNotify($result,$account_result,$type);//记录回调信息
        }
    }


}