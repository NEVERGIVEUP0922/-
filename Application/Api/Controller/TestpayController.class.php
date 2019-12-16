<?php
namespace Api\Controller;
use Think\Controller;
/**
 * 支付宝
 */
class TestpayController extends Controller{

    /**
     * 初始化方法
     */
    protected function _initialize(){
//        if(!APP_DEBUG) die();
    }

    public function orderPayTest(){
        $post=I('post.');
        if(!session('adminId')) die(json_encode(['error'=>1,'msg'=>'登陆信息错误']));
        if(!isset($post['order_sn'])) die(json_encode(['error'=>1,'msg'=>'参数错误']));

        $post['type']='weixinpay_order';
       $order=M('order')->where(['order_sn'=>$post['order_sn']])->find();
       if($order&&(($order['pay_type']==1&&$order['deposits_pay_status']!=2)||($order['deposits_pay_type']==1&&$order['deposits_pay_status']==0))){
           // die(json_encode(['error'=>1,'msg'=>'非在线支付']));
        }else{
            die(json_encode(['error'=>1,'msg'=>'非在线支付']));
       }
        $info=(new \Home\Controller\PayController())->orderPayInfo($post['order_sn']);
        if($info['error']!=0) die(json_encode($info));
        $data=$info['data'];

        switch ($post['type']){
            case 'weixinpay_order':
                $_POST=[
                    'appid' => 'wxb747e8d88c7e134f',
                    'bank_type' => 'CFT',
                    'cash_fee' => '1',
                    'fee_type' => 'CNY',
                    'is_subscribe' => 'Y',
                    'mch_id' => '1487651012',
                    'nonce_str' => 'test',
                    'openid' => 'o73DY05aOOo3Jni8nRZgaZEOFu7U',
                    'out_trade_no' => $data['out_trade_no'],
                    'result_code' => 'SUCCESS',
                    'return_code' => 'SUCCESS',
                    'time_end' => '20171020181819',
                    'total_fee'=>$data['price'],
                    'trade_type' => 'NATIVE',
                    'transaction_id' => '4200000037201801037359667611',
                ];
                $weixinpay=new WeixinpayController();
                $result=$weixinpay->notify($_POST,2355607503);
                break;
        }

        die(json_encode($result));
    }


}