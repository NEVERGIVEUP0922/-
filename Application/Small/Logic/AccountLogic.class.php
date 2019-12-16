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

class AccountLogic extends BaseLogic
{

    /**
     * @desc 注册商城用户
     *
     */
    public function registerPC($request){
        $check=$this->registerPCCheck($request);
        if($check<0) return $check;

        $result=D('user')->registerPC($request);//注册商城用户
        if($result['error']<0) return $result;

        $request['user_id']=$result['data']['user_id'];
        $result=D('customer')->customerToLongicmall($request); //帐号绑定

        return $result;
    }

    /**
     * @desc 注册商城用户：数据检测
     *
     */
    public function registerPCCheck($request){
        if($this->checkFormat(['value'=>$request['mobile'],'type'=>'mobile'])===false) return ['error'=>-1,'msg'=>'手机格式错误'];
        if($this->checkFormat(['value'=>$request['password'],'type'=>'password'])===false) return ['error'=>-1,'msg'=>'不小于6位的子母或数字组成的字符'];
        if($this->checkFormat(['value'=>$request['user_name'],'type'=>'password'])===false) return ['error'=>-1,'msg'=>'不小于6位的子母或数字组成的字符'];

        $one=M('user')->field('id')->where([['user_mobile'=>$request['mobile']]])->find();
        if($one) return ['error'=>-1,'msg'=>'手机号码已注册'];

        return ['error'=>0,'msg'=>'pass'];
    }


}