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

class XCXAPILogic extends BaseLogic
{
    const LENGTH_NUM=[ 1=>'a', 2=>'b', 3=>'c', 4=>'d', 5=>'e', 6=>'f', 7=>'g', 8=>'h', 9=>'i', 10=>'j', 11=>'k' ];

    /**
     * @desc 获取access_token
     *
     */
    public function getAccess_token($appid,$secret,$is_token=''){
        $session_token_key=md5('xcx_access_token_daxin');

        $session_token=S($session_token_key);
        if($session_token&&!$is_token) return ['error'=>0,'data'=>$session_token,'msg'=>'缓存'];

        $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret;
        $result=file_get_contents($url);
        $result=json_decode($result,true);

        if(!$result['access_token']) return ['error'=>-1,'msg'=>'从微信获取access_token失败'];
        S($session_token_key,null);
        S($session_token_key,$result['access_token'],$result['expires_in']-120);
        return ['error'=>1,'data'=>$result['access_token']];
    }

    /**
     * @desc 生成分享二维码
     *
     */
    public function createQRcodeShare($user_id,$pId,$appId,$secret,$share_type='01'){
        //文件信息
        $file_path=APP_PATH;
        $file_site_path='/Uploads/Product/xcxQRcode';
        $file_path.='..'.$file_site_path;
        $file_dir=$file_path;
        $file_name=$user_id.'-'.$pId.'-'.$share_type.'.png';
        $file_path.='/'.$file_name;
        if(file_exists($file_path)) return ['error'=>0,'data'=>['path'=>$file_site_path.'/'.$file_name]];

        $scene=$this->xcxQRcodeScene($user_id,$pId,$share_type);//页面标记
        $result=$this->getAccess_token($appId,$secret);
        $access_token=$result['data'];

        $url='https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token;
        $message=[
            'scene'=>$scene,
            'page'=>'pages/start/start',
            'is_hyaline'=>true,
        ];

        $message=json_encode($message);
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
        $is_token=json_decode($file,true);
        if(isset($is_token['errcode'])&&$is_token['errcode']==40001){

            $result=$this->getAccess_token($appId,$secret,1);
            $access_token=$result['data'];

            $url='https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token;
            $message=[
                'scene'=>$scene,
                'page'=>'pages/start/start',
                'is_hyaline'=>true,
            ];

            $message=json_encode($message);
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

        }
        if(!is_dir($file_dir)){
            mkdir($file_dir,0755,true);
            chmod($file_dir,0755);
        }

        $result=file_put_contents($file_path,$file);
        if(!$result) return ['error'=>-1,'msg'=>'二维码生成失败'];

        return ['error'=>0,'msg'=>'success','data'=>['path'=>$file_site_path.'/'.$file_name]];
    }

    /**
     * @desc 小程序二维码参数  max length 32 string
     * @param  share_type [01=>用户产品分享,] max length 2 string
     * @param  user_id 用户id
     * @param  pId 分享事件id
     *
     */
    public function xcxQRcodeScene($user_id,$pId,$share_type='01'){
        $time=date('ymd',time());//时间6位
        $length=self::LENGTH_NUM;

        //user_id补11位
        $userId_length=strlen("$user_id");
        $userId_length_a=$length[$userId_length];
        $userId_complete='';
        if((int)$userId_length!==11){
            $userId_complete_length=11-$userId_length-1;
            $userId_complete=mt_rand(pow(10,$userId_complete_length),pow(10,$userId_complete_length+1)-1);
        }

        //pid_id补11位
        $pId_length=strlen("$pId");
        $pId_length_a=$length[$pId_length];
        $pId_complete='';
        if((int)$pId_length!==11){
            $pId_complete_length=11-$pId_length-1;
            $pId_complete= mt_rand(pow(10,$pId_complete_length),pow(10,$pId_complete_length+1)-1);
        }

        $scene=$userId_length_a.$pId_length_a.$user_id.$userId_complete.$pId.$pId_complete.$time.$share_type;

        return $scene;
    }

    /**
     * @desc 小程序二维码参数  max length 32 string
     * @param  scene(场景)
     *
     */
    public function xcxQRcodeScenePare($scene){
        if((int)(strlen($scene))!==32) return ['error'=>-1,'msg'=>'假的'];
        $share_type= substr($scene,-2,2);
        $length_num=self::LENGTH_NUM;
        $length_num=array_flip($length_num);

        $userId_length=$length_num[substr($scene,0,1)];
        $pId_length=$length_num[substr($scene,1,1)];
        $userId=substr($scene,2,$userId_length);
        $pId=substr($scene,13,$pId_length);
        $return_data=[
            'pId'=>$pId,
            'userId'=>$userId,
            'share_type'=>$share_type,
        ];
        switch($share_type){
            case '01'://客户产品分享
                return ['error'=>0,'data'=>$return_data];
                break;
        }

        return ['error'=>-1,'msg'=>'解析失败'];
    }

}