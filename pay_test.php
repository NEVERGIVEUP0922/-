<?php
require_once "access_token/jssdk.php";
define('APPID','wxb747e8d88c7e134f');
define('MCHID','gh_1ebff417d178');
define('APP_SECRET','7b63366c209a23beba774351f0e2d823');
$jssdk = new JSSDK(APPID, APP_SECRET);
$signPackage = $jssdk->GetSignPackage();



////获取code
//$redirect_uri=urlencode('http://www.longicmall.com/pay/wechat/callback_get_code.php');
//$state=md5(mt_rand(1,10000));
//$code_data=[
//    'appid'=>APPID,
//    'redirect_uri'=>$redirect_uri,
//    'response_type'=>'code',
//    'scope'=>'snsapi_login',
//    'state'=>$state,
//];
//获取code
//$redirect_uri='https%3A%2F%2Fpassport.yhd.com%2Fwechat%2Fcallback.do';
$redirect_uri='http://www.baidu.com';
$state=md5(mt_rand(1,10000));
$code_data=[
    'appid'=>'wxbdc5610cc59c1631',
    'redirect_uri'=>$redirect_uri,
    'response_type'=>'code',
    'scope'=>'snsapi_login',
    'state'=>$state,
];
$code=get_code($code_data);
print_r($code);


////获取openid
//$openid_url_data=[
//    'appid'=>APPID,
//    'secret'=>APP_SECRET,
//    'code'=>$code,
//    'grant_type'=>'authorization_code',
//];
//$openId=get_openid($openid_url_data);
//print_r($openId);





//统一下单
$create_order_data=[
    'appid'=>APPID,
    'mch_id'=>MCHID,
    'device_info'=>'013467007045764',
    'nonce_str'=>'5K8264ILTKCH16CQ2502SI8ZNMTM67VS',
    'sign'=>'C380BEC2BFD727A4B6845133519F3AD6',
    'sign_type'=>'MD5',
    'body'=>'腾讯充值中心-QQ会员充值',
    'detail'=>'123',
    'attach'=>'深圳分店',
    'out_trade_no'=>'20150806125346',
    'fee_type'=>'CNY',
    'total_fee'=>88,
    'spbill_create_ip'=>'123.12.12.123',
    'time_start'=>'20091225091010',
    'time_expire'=>'20091227091010',
    'goods_tag'=>'WXG',
    'notify_url'=>'http://www.weixin.qq.com/wxpay/pay.php',
    'trade_type'=>'JSAPI',
    'product_id'=>'12235413214070356458058',
    'limit_pay'=>'no_credit',
    'openid'=>'oUpF8uMuAJO_M2pxb1Q9zNjWeS6o',
    'scene_info'=>
        '{"store_info":{
            "id": "SZTX001",
            "name": "腾大餐厅",
            "area_code": "440305",
            "address": "科技园中一路腾讯大厦" 
        }}'
];










//获取code
function get_code($url_data){
    $url='https://open.weixin.qq.com/connect/qrconnect?';
    $url.= http_build_query($url_data);
    $result=httpGet($url.'#wechat_redirect');
    return $result;
}

//获取用户openid
function get_openid($url_data){
    $url='https://api.weixin.qq.com/sns/oauth2/access_token?';
    return wechat_url_request($url,$url_data);
}

//统一下单
function unified_create_order($url_data){
    $url='https://api.mch.weixin.qq.com/pay/unifiedorder?';
    return wechat_url_request($url,$url_data);
}

//微信api请求
function wechat_url_request($url,$url_data){
    $url.= http_build_query($url_data);
    $result=httpGet($url);
    return $result;
}

function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
    // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
}









?>
