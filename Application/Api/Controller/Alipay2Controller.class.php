<?php
namespace Api\Controller;
use Home\Controller\SolutionVipController;

/**
 * 支付宝
 */
class Alipay2Controller extends BaseController{

	/**
	 * return_url接收页面
	 */
	public function alipay_return(){
		// 引入支付宝
		vendor('Alipay.AlipayNotify','','.class.php');
		$config=$config=C('ALIPAY_CONFIG2');
		$notify=new \AlipayNotify($config);
		// 验证支付数据
		$status=$notify->verifyReturn();

		if($status){
			// 下面写验证通过的逻辑 比如说更改订单状态等等 $_GET['out_trade_no'] 为订单号；
//			list($time_,$order_sn,$pay_type)=explode('_',$_GET['out_trade_no']);
			$order_sn = $_GET['out-trade_no'];
			$this->redirect('Home/SolutionVip/vipIndex',['error'=>0,'msg'=>'支付成功']);
		}else{
			$this->redirect('Home/SolutionVip/vipIndex',['error'=>1,'msg'=>'支付失败']);
		}
	}

	/**
	 * notify_url接收页面
	 */
	public function alipay_notify(){
		// 引入支付宝
		$pay_m=new \Home\Controller\PayController();
		vendor('Alipay.AlipayNotify','','.class.php');
		$config=$config=C('ALIPAY_CONFIG2');
		$alipayNotify = new \AlipayNotify($config);
		$result=$_POST;
		// 验证支付数据
		$verify_result = $alipayNotify->verifyNotify();
		$this_time=date('YmdHis',time());
		$result = json_encode($result);
		file_put_contents("vipPay_alipay_log.html", "支付信息:".$result.'------'.PHP_EOL, FILE_APPEND);
//		$res_arr= json_decode($result);
//		$res = $res_arr['price'];
//		file_put_contents("vipPay_alipay_text_log.html", "支付信息:".$res.'------'.PHP_EOL, FILE_APPEND);
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
		//		$result = "{.
		//				discount:0.00							//折扣券
		//				payment_type:1,.						//交易类型
		//				trade_no:2019011822001413561012094560", //支付宝交易号
		//				subject:\u5929\u7396\u9686\u65b9\u6848\u4e2d\u5fc3VIP:\u6708\u4f1a\u5458",  //订单标题
		//				buyer_email:243***@qq.com,				//支付者的一些信息
		//				gmt_create:2019-01-18 17:54:23,			//交易创建时间
		//				notify_type:trade_status_sync,			//通知类型
		//				 17:54:31,
		//				trade_status:TRADE_SUCCESS,				//交易状态
		//				is_total_fee_adjust:N,					//?
		//				total_fee:0.01,							//金额
		//				gmt_payment:2019-01-18 17:54:31,		//付款时间
		//				seller_email:18902476196@189.cn,		//卖家信息
		//				price:0.01,								//没有说明(价格)?
		//				buyer_id:2088702270813561,				//买家支付宝用户号
		//				notify_id:2019011800222175431013561036517853,//通知校验ID
		//				use_coupon:N,							//?
		//				sign_type:MD5,							//商户生成签名字符串所使用的签名算法类型，目前支持RSA2和RSA，推荐使用RSA2
		//				sign:d15545c4de99c4dffb56156426426853	//签名
		//				}";

//		$destination = C('LOG_PATH').'pay_alipay_'.date('y_m_d').'.log';
//		\Think\Log::write(serialize($result),'INFO','',$destination);

		if($verify_result) {
			//		if($result != false){
			$type = 1;//支付类型(支付宝1,微信2,银联3)
			$weixinPay = new SolutionVipController(654321);
			//		file_put_contents("vipPay_code_log3.html","保存信息:'dasda'".PHP_EOL,FILE_APPEND);
			$re = $weixinPay->scanPayResult($result, $type);/*修改订单状态*/
			$rez = json_decode($re, true);
			if($rez['error'] != 0){
				file_put_contents("vipPay_error_log.html", "修改信息错误:".$re.'---'.$this_time.PHP_EOL, FILE_APPEND);
			}
		}else{
			file_put_contents("vipPay_error_log.html","支付错误--alipay--".$this_time.PHP_EOL,FILE_APPEND);
		}

//			if($result['trade_status']=="TRADE_SUCCESS"){
//				echo 'success';
//			}else{
//				echo "success";
//				// 下面写验证通过的逻辑 比如说更改订单状态等等 $_POST['out_trade_no'] 为订单号；
//
//				$alipay_config=C('ALIPAY_CONFIG');
//				if($alipay_config['partner']!=$result['seller_id']){
//					file_put_contents("vipPay_error_log.txt",'seller_id与partner不一致'."-------------".date('Y:m:d-H:i:s',time()).PHP_EOL,FILE_APPEND);
//				}else{
//					$type='alipay';//$type = 1;
//					$check_result = new solutionVipController;
//					$re = $check_result->scanPayResult($result,$type);/*更改订单状态和会员信息*/
//					if($re === false ||(isset($re)&&$re['error']==1)){
//						file_put_contents("vipPay_error_log.txt","错误信息-----".$re['msg']."-------------".date('Y:m:d-H:i:s',time()).PHP_EOL,FILE_APPEND);
//					}
//				}
//			}
//		}
	}
}