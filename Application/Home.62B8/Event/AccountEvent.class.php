<?php
// +----------------------------------------------------------------------
// | FileName:   OrderEvent.class.php
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date:  2018-02-02 10:43
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Home\Event;

use Think\Log;

class AccountEvent
{
    public $rememberTime ='2592000';//记住密码30天

    /**
     * @desc 登陆时记住密码
     */
    public function rememberPasswordAction($user_name,$password,$createTime)
    {
        $rememberPassword=$this->rememberPassword($user_name,$password,$createTime);
        if($rememberPassword<0) return $rememberPassword;

        $token=$rememberPassword['data']['token'];

        $time=$this->rememberTime;
        cookie('access_token',$token,$time);
        return $rememberPassword;
    }

    /**
     * @desc 验证记住密码
     */
    public function checkRememberPasswordAction($user_name,$front_password)
    {
        $access_token=I('cookie.access_token');
        $rememberPassword=$this->checkRememberPassword($user_name,$front_password,$access_token);

        return $rememberPassword;
    }

    /**
     * @desc 删除记住密码
     */
    public function rememberPasswordDelete($user_name){
        $key=$this->passwordTokenKey($user_name);
        S($key,null);
        cookie('access_token',null);
        return ['error'=>0,'msg'=>'删除成功'];
    }

    /**
     * @desc 记住密码
     */
    public function rememberPassword($user_name,$password,$createTime)
    {
        $time=$this->rememberTime;
        $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $password_length=strlen($password)?:0;
        if($password_length===0) return ['error'=>-1,'msg'=>'密码不对'];
        $key=$this->passwordTokenKey($user_name);
        $token=$this->passwordTokenValue($user_name,$createTime);

        $front_password='';
        for($i=0;$i<$password_length;$i++){
            $front_password .= $char[mt_rand(0,62)];
        }

        $return_data=[
            'user'=>$user_name,
            'password'=>$front_password,
            'token'=>$token
        ];

        S($key,json_encode($return_data),$time);
        return ['error'=>0,'data'=>$return_data];
    }

    /**
     * @desc 保存登陆时记住密码key
     */
    public function passwordTokenKey($user_name,$key='longicmall_rememberkey'){
        $key=$user_name.$key;
        $arr=explode('',$key);
        sort($arr);
        $key=implode('',$arr);
        return md5($key);
    }

    /**
     * @desc 保存登陆时记住密码token
     */
    public function passwordTokenValue($user_name,$createTime,$key='longicmall_rememberToken'){
        $key=$user_name.$key.$createTime;
        $arr=str_split($key);
        sort($arr);
        $key=implode('',$arr);
        return md5($key);
    }

    /**
     * @desc 验证记住密码
     */
    public function checkRememberPassword($user_name,$front_password,$token){
        $user=M('user')->field('create_time')->where(['user_name'=>$user_name])->find();
        $create_time=$user['create_time'];

        $key=$this->passwordTokenKey($user_name);
        $rememberPassword=json_decode(S($key),true);

        if($rememberPassword['user']!=$user_name) return ['error'=>-1,'msg'=>'帐号错误'];
        if($rememberPassword['password']!=$front_password) return ['error'=>-1,'msg'=>'帐号错误2'];
        if($rememberPassword['token']!=$token) return ['error'=>-1,'msg'=>'帐号错误3'];

        return ['error'=>0,'msg'=>'pass'];
    }




}