<?php
namespace Small\Controller;
use Think\Controller;
class UserPayController extends BaseController {

    /**
     * @desc 用户去支付
     *
     */
    public function userPay(){
        $request=$this->post;

        $userInfo=$this->getUserInfo();
        $where['user_id']=$userInfo['id'];

        $order_sn=$index=$request['order_sn'];
        $type=$request['type']?:1;//定单支付
        $result=D('pay','Logic')->toUserPay($userInfo['id'],$type,$index);

        if($result['error']<0||$request['action']!='userPay'){
            $this->return_data['data']=$result['data'];
            $this->return_data['statusCode']=$result['error'];
            $this->return_data['msg']=$result['msg'];
            $this->ajaxReturn($this->return_data);
        }

        if($request['pay_select_type']=='weixin'){//微信支付
            //统一下单
            $api=$this->getCustomer($request['session_token']);
            $time=time();
            $orderInfo=M('order')->where(['order_sn'=>$order_sn])->find();
            if($orderInfo['order_type']==1&&!(float)$orderInfo['already_paid']) $order_balances=$orderInfo['delivery_price']+$orderInfo['total_deposits'];//选择支付定金未付定金的，支付定金
            else $order_balances=$orderInfo['total']-$orderInfo['already_paid'];//支付全部未付款
            $appid=$api[2];
            $openid=$api[0];
            $key=C('WEIXINPAY_CONFIG.KEY');
            $mch_id=C('WEIXINPAY_CONFIG.MCHID');
//            $notify_url=C('WEIXINPAY_CONFIG.NOTIFY_URL');
            $notify_url= 'https://www.longicmall.com/index.php/api/Weixinpaytest/notify/token/654321';// 异步接收支付状态通知的链接
            $order_ip=get_client_ip();

            $out_trade_no=$time.$order_sn.'1';//微信扫码订单号组成：时间挫-商城订单号-支付类型(长度小于等于32)
            $body='玖隆芯城订单号：'.$order_sn;
            $total_fee=(int)(round($order_balances,2)*100);

            $result=D('WeiXinPay','Logic')->weiXinOrder($appid, $openid, $mch_id, $key, $out_trade_no, $body, $total_fee, $notify_url, $order_ip);
            $result['data']['total_fee']=$total_fee;
        }

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }
}