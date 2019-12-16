<?php

namespace Small\Controller;

use Think\Controller;
use Small\Tool\AccessTool as tool;

class XCXMessageNotifyController extends Controller
{
    use tool;

    private $token = 'daixnxiao111chenxu12';

    public function _initialize()
    {
    }

    /**
     * @desc 小程序的消息异步通知
     *
     */
    public function messageNotify()
    {
        $checkSignature = $this->checkSignature();
//        if($checkSignature) die($_GET['echostr']);//第一次接入时的

        if (!$checkSignature) {
            kelly_log(json_encode($_GET), 'xcx_messageNotify_error', 'ALTER');//验证不通过
            kelly_log(json_encode($_POST), 'xcx_messageNotify_error', 'ALTER');//验证不通过
        }

        $postData=$GLOBALS['HTTP_RAW_POST_DATA'];
        kelly_log('postData-------'.$postData, 'xcx_messageNotify', 'INFO');

        Vendor('Weixinpay.Weixinpay');
        $wxpay=new \Weixinpay();
        $result=$wxpay->toArray($postData);

        //客服消息处理
        $result=D('Small/Message','Logic')->notifyMessage($result);
        kelly_log('postData-------'.json_encode($result), 'xcx_messageNotify', 'INFO');

        if($result['error']>=0) die('success');
    }

    /**
     * @desc 第一次接入验证
     *
     */
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @desc 给小程序用户回消息
     *
     */
    public function sendMessage()
    {
        $post=I('post.');

        $userId=$post['userId'];//用户id
        $message=$post['message'];

        $result=D('Small/Message','Logic')->buildMessage($userId,$message);

        die(json_encode($result));
    }

    /**
     * @desc 小程序新增临时素材
     *
     */
    public function tempSource(){

        $post=I('post.');
        $userId=$post['userId'];//用户id

        $messageD=D('Small/Message','Logic');
//        $messageD->tempSource($userId);
        $userId=513;

        print_r($_FILES);

//        $xcx = $messageD->userIdToOpenId($userId);
        $result = $messageD->tempSource($userId,$_FILES['file']);
//
//        $result = D('Small/XCXAPI', 'Logic')->getAccess_token($xcx['data']['appid'],$xcx['data']['secret']);
//        $access_token = $result['data'];

//        $this->assign('ACCESS_TOKEN',$access_token);
        $this->display();
    }







}