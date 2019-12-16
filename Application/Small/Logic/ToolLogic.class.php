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
use Common\Controller\SmsController as sms;

class ToolLogic extends BaseLogic
{
    use sms;

    /**
     * @desc 验证码
     *
     */
    public function verificationCode($phone='',$content='',$active='companySon',$time=60,$action='registered'){
        if(!preg_match('/^1\d{10}$/',$phone)){
            return ['error'=>-1,'msg'=>'手机号码格式不对'];
        }

        $getCode=$this->getCode($phone,'xiaochenxu',true);
        $code=$getCode['code'];
        $key=$getCode['key'];
        if($action=='registered'){
            $user=M('user')->field('id')->where(['user_mobile'=>$phone])->find();
            if($user) return ['error'=>-1,'msg'=>'手机已注册'];
        }
        if(S($key)){
            return ['error'=>-1,'msg'=>'请不要重复发送'];
        }

        if(!$content)$content= '【天玖隆】亲爱的用户，新注册帐号的验证码为:'.$code.',该验证码'.$time.'秒内有效';
        $result=$this->sendSms($phone,$content,$active);
        if($result['error']===0){
            S($key,$code,$time);
        }

        return ['error'=>0,'msg'=>'success'];
    }

    /**
     * @desc 获取验证码内容
     *
     */
    public function getCode($phone,$key='xiaochenxu',$isClear=false){
        $key=md5($phone.$key);
        if($isClear) S($key,null);
        if(!$code=S($key)) $code=mt_rand(1000,9999);

        return ['key'=>$key,'code'=>$code];
    }


}