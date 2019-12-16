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

class MessageLogic extends BaseLogic
{
    public $limit='0,10';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 构建发给小程序的消息
     *
     */
    public function buildMessage($userId,$content='',$type='text'){
        if(!$content) return ['error'=>-1,'msg'=>'消息不能为空'];

        $userIdToOpenId=$this->userIdToOpenId($userId);
        if($userIdToOpenId['error']<0) return $userIdToOpenId;
        $openId=$userIdToOpenId['data']['openId'];

        $message=[];
        switch ($type){
            case 'text'://文本消息
                $message=$this->textMessage($openId,$content);
                break;
            case 'image'://图片消息
                $message=$this->imageMessage($openId,$content);
                break;
            case 'link'://图片连接消息
                $message=$this->imageLinkMessage($openId,$content);
                break;
        }

        $result=$this->sendMessage($message,$userIdToOpenId['appid'],$userIdToOpenId['secret']); //发送消息
        return $result;
    }

    /**
     * @desc 图片连接消息
     * content=[
     *      title               标题
     *      ,description        文字说明
     *      ,thumb_url          显示图片
     *      ,url                跳转连接
     * ]
     */
    public function imageLinkMessage($openId,$content){
        $message = [
            "touser" => $openId,
            "msgtype" => "link",
            "link" => [
                "title"=> $content['title'],
                "description"=> $content['description'],
                "url"=> $content['url'],
                "thumb_url"=> $content['thumb_url']
            ]
        ];

        return $message;
    }

    /**
     * @desc 图片消息
     *
     */
    public function imageMessage($openId,$media_id){
        $message = [
            "touser" => $openId,
            "msgtype" => "image",
            "image" => [
                "media_id" => $media_id
            ]
        ];

        return $message;
    }

    /**
     * @desc 文本消息
     *
     */
    public function textMessage($openId,$content){
        $message = [
            "touser" => $openId,
            "msgtype" => "text",
            "text" => [
                "content" => $content
            ]
        ];

        return $message;
    }

    /**
     * @desc 给小程序用户回消息
     *
     */
    public function sendMessage($message,$appid,$secret)
    {
        $message=json_encode($message,JSON_UNESCAPED_UNICODE );

        $result = D('Small/XCXAPI', 'Logic')->getAccess_token($appid,$secret);
        $access_token = $result['data'];

        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $access_token;

        $opts = array(
            'http'=>array(
                'method'=>"POST",
                'content'=>$message,
                'header'=>"Accept-language: zh\r\n" .
                    "Cookie: foo=bar\r\n".
                    "Content-Type: text/html\r\n"
            )
        );

        $context = stream_context_create($opts);
        $file = file_get_contents($url, false, $context);
        $file=json_decode($file,true);

        $error=0;
        if($file['errcode']!=0) $error=-1;
        return ['error'=>$error,'msg'=>$file['errcode'].'------'.$this->errorMessage($file['errcode'])];
    }

    /**
     * @desc 给小程序用户回消息,错误返回码
     *
     */
    public function errorMessage($code){
        $message='';
        $message_arr=[
            '-1'=>'系统繁忙，此时请开发者稍候再试',
            '0'=>'消息发送成功',
            '40001'=>'获取 access_token 时 AppSecret 错误，或者 access_token 无效。请开发者认真比对 AppSecret 的正确性，或查看是否正在为恰当的小程序调用接口',
            '40002'=>'不合法的凭证类型',
            '40003'=>'不合法的 OpenID，请开发者确认OpenID否是其他小程序的 OpenID',
            '45015'=>'回复时间超过限制',
            '45047'=>'客服接口下行条数超过上限',
            '48001'=>'api功能未授权，请确认小程序已获得该接口',
        ];
        $messsage=isset($message_arr[$code])?$message_arr[$code]:'未知错误';
        return $message;
    }

    /**
     * @desc 用户的userId换openId
     *
     */
    public function userIdToOpenId($userId){
         $one=M('user','xcx_')->field('openid,appid')->where(['user_id'=>$userId])->find();
         if(!$one) return ['error'=>-1,'msg'=>'用户信息错误'];
         $customer_xcx=M('open_key','xcx_')->field('secret')->where(['appid'=>$one['appid']])->find();
         if(!$customer_xcx) return ['error'=>-1,'msg'=>'用户信息错误2'];

         return ['error'=>0,'data'=>['openId'=>$one['openid'],'appid'=>$one['appid'],'secret'=>$customer_xcx['secret']]];
    }

    /**
     * @desc 接收客服消息
     *
     */
    public function notifyMessage($request){

        //保存客服消息
        $result=$this->textNotifyMessage($request);
        return $result;
    }

    /**
     * @desc 文本客服消息
     *
     */
    public function textNotifyMessage($request){
        $data=[
            'from'=>$request['FromUserName'],
            'to'=>$request['ToUserName'],
            'content'=>$request['PicUrl']?:$request['Content'],
            'type'=>$request['MsgType'],
            'xcx_time'=>$request['CreateTime'],
            'origin'=>json_encode($request),
        ];

        $result=M('service_message','xcx_')->add($data);
        if(!$result) return ['error'=>-1,'msg'=>'field'];

        return ['error'=>0,'msg'=>'success'];
    }

    /**
     * @desc 小程序新增临时素材
     *
     */
    public function tempSource($userId,$file){
        $userIdToOpenId=$this->userIdToOpenId($userId);

        $result = D('Small/XCXAPI', 'Logic')->getAccess_token($userIdToOpenId['data']['appid'],$userIdToOpenId['data']['secret']);
        if($result['error']<0) return $result;

        $access_token = $result['data'];
        $url="https://api.weixin.qq.com/cgi-bin/media/upload?access_token=$access_token&type=image";

        $post_data = array(
            "foo" => "bar",
            "file" => "bar",
            "upload" => "C:/Users/Administrator/Desktop/d058ccbf6c81800a2b6cd143bd3533fa838b47f5.jpg" //要上传的本地文件地址
        );

        $ch = curl_init();
        curl_setopt($ch , CURLOPT_URL , $url);
        curl_setopt($ch , CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch , CURLOPT_POST, 1);
        curl_setopt($ch , CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        var_dump($output);

//        $opts = array(
//            'http'=>array(
//                'method'=>"POST",
//                'header'=>"Accept-language: zh\r\n" .
//                    "Content-Type: image/jpeg\r\n".
//                    'Content-Disposition: form-data; name="file"; filename="'.$file['file']['name'].'\r\n"'
//            )
//        );
//
//        $context = stream_context_create($opts);
//        $file = file_get_contents($url, false, $context);
//        var_dump($file);
//        $file=json_decode($file,true);

    }





    }