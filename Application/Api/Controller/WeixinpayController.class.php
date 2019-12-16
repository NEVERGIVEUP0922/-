<?php
namespace Api\Controller;
/**
 * 微信支付
 */
class WeixinpayController extends BaseController{

    /**
     * notify_url接收页面
     */
    public function notify($result2='',$code=''){
        $key_code='2355607503';
        if($code==$key_code){//测试用的
            $result=$result2;
        }else{
            // 导入微信支付sdk
            Vendor('Weixinpay.Weixinpay');
            $wxpay=new \Weixinpay();
            $result=$wxpay->notify();
        }
        $this_time=date('YmdHis',time());
//        $result=[
//            'appid' => 'wxb747e8d88c7e134f',
//            'bank_type' => 'CFT',
//            'cash_fee' => '1',
//            'fee_type' => 'CNY',
//            'is_subscribe' => 'Y',
//            'mch_id' => '1487651012',
//            'nonce_str' => 'test',
//            'openid' => 'o73DY05aOOo3Jni8nRZgaZEOFu7U',
//            'out_trade_no' => '151503815118010410865541',
//            'result_code' => 'SUCCESS',
//            'return_code' => 'SUCCESS',
//            'time_end' => '20171020181819',
//            'total_fee' => '800',
//            'trade_type' => 'NATIVE',
//            'transaction_id' => '4200000037201801037359667611',
//        ];

//        $result=[
//            'appid' => 'wxb747e8d88c7e134f',
//            'bank_type' => 'CFT',
//            'cash_fee' => '1',
//            'fee_type' => 'CNY',
//            'is_subscribe' => 'Y',
//            'mch_id' => '1487651012',
//            'nonce_str' => 'test',
//            'openid' => 'o73DY05aOOo3Jni8nRZgaZEOFu7U',
//            'out_trade_no' => '15153978077678232',
//            'result_code' => 'SUCCESS',
//            'return_code' => 'SUCCESS',
//            'time_end' => '20171027104453',
//            'total_fee' => '2',
//            'trade_type' => 'NATIVE',
//            'transaction_id' => '4200000007201710270580423864',
//        ];

        $destination = C('LOG_PATH').'pay_weixin_'.date('y_m_d').'.log';
        \Think\Log::write(serialize($result),'INFO','',$destination);

        if ($result) {
            // 验证成功 修改数据库的订单状态等 $result['out_trade_no']为订单号信息

            $alipay_config=C('WEIXINPAY_CONFIG');
            if($alipay_config['MCHID']!=$result['mch_id']){//mch_id验证
                $pay_m=new \Home\Controller\PayController();
                $pay_m->wechatCallbackNotify($result,['error'=>1,'msg'=>'mch_id验证错误']);//记录回调信息
                die();
            }

            $pay_type=substr($result['out_trade_no'],-1,1);

            if($pay_type==1){//商城订单微信扫码支付
                $order_sn=substr($result['out_trade_no'],10,-1);

                $pay_m=new \Home\Controller\PayController();
                $order_result=$pay_m->scanQRcodePayResult($result);//改变订单信息
                $pay_m->wechatCallbackNotify($result,$order_result);//记录回调信息
                S('wechat_notify_result',$order_sn);

                if($code==$key_code) return $order_result;//测试用的

            }else if($pay_type==2){//账期还款微信扫码支付
                $order_sn=substr($result['out_trade_no'],14,-1);

                $pay_m=new \Home\Controller\PayController();
                $account_result=$pay_m->QRcodeRepaymentAccountResult($result);//改变账期信息
                $pay_m->wechatCallbackNotify($result,$account_result);//记录回调信息
                S('wechat_notify_result',$order_sn);
            }
        }
    }

//    /**
//     * 公众号支付 必须以get形式传递 out_trade_no 参数
//     * 示例请看 /Application/Home/Controller/IndexController.class.php
//     * 中的weixinpay_js方法
//     */
//    public function pay(){
//        // 导入微信支付sdk
//        Vendor('Weixinpay.Weixinpay');
//        $wxpay=new \Weixinpay();
//        // 获取jssdk需要用到的数据
//        $data=$wxpay->getParameters();
//        // 将数据分配到前台页面
//        $assign=array(
//            'data'=>json_encode($data)
//            );
//        $this->assign($assign);
//        $this->display();
//    }

}