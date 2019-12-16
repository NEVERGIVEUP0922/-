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

class WeiXinPayLogic extends BaseLogic
{

    /**
     * @desc 小程序支付统一下单
     *
     */
    public function weiXinOrder($appid, $openid, $mch_id, $key, $out_trade_no, $body, $total_fee, $notify_url, $order_ip){
        Vendor("Xaochenxu.Pay.WeixinPay");

        $weixinpay = new \WeixinPay($appid, $openid, $mch_id, $key, $out_trade_no, $body, $total_fee, $notify_url, $order_ip);
        $return = $weixinpay->pay();
        if(!$return||$return['error']<0){
            //日志记录
            $log_str='weixin_return:----'.json_encode($return).';';
            $log_str.='-------weixin_request_argument:----'.json_encode([$appid, $openid, $mch_id, $key, $out_trade_no, $body, $total_fee, $notify_url, $order_ip]);
            kelly_log($log_str,'WeiXinXiaoChenXuPay_ERROR','ALTER');

            return ['error'=>-1,'msg'=>'统一下单失败'];
        }

        return ['error'=>0,'data'=>$return];
    }




}