<?php
namespace Api\Controller;
use Home\Controller\SolutionVipController;
/**
 * 微信支付
 */
class Weixinpay2Controller extends BaseController{

	/**
	 * notify_url接收页面
	 */

	public function notify(){
		// 导入微信支付sdk
		vendor('vipPay.Weixinpay2');
		$wxpay = new \Weixinpay2();
		$result= $wxpay->notify();
//		$result = ["appid"=>"wxb747e8d88c7e134f",
//				"bank_type"=>"CFT",
//				"cash_fee"=>"1",
//				"fee_type"=>"CNY",
//				"is_subscribe"=>"Y",
//				"mch_id"=>"1487651012",
//				"nonce_str"=>"test",
//				"openid"=>"o73DY00rczjoTJjP7Rx-m8XrUCTI",
//				"out_trade_no"=>"1901181281591",
//				"result_code"=>"SUCCESS",
//				"return_code"=>"SUCCESS",
//				"time_end"=>"20190117172112",
//				"total_fee"=>"1",
//				"trade_type"=>"NATIVE",
//				"transaction_id"=>"4200000246201901174853455141"
//		];
		$this_time = date('Y-m-d H:i:s', time());
		if($result){
			$res_arr = json_encode($result);
			//		if($result != false){
			$type = 2;//支付类型(支付宝1,微信2,银联3)
			$weixinPay = new SolutionVipController(654321);
			//		file_put_contents("vipPay_code_log3.html","保存信息:'dasda'".PHP_EOL,FILE_APPEND);
			$re = $weixinPay->scanPayResult($res_arr, $type);/*修改订单状态*/
			$rez = json_decode($re, true);
			if($rez['error'] != 0){
				file_put_contents("vipPay_error_log.html", "修改信息错误:".$re.'------'.$this_time.PHP_EOL, FILE_APPEND);
			}
		}else{
			file_put_contents("vipPay_error_log.html","支付错误--".$this_time.PHP_EOL,FILE_APPEND);
		}
	}

}