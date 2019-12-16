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

class CustomerLogic extends BaseLogic
{
    public $limit='0,10';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 绑定商城帐号
     *
     */
    public function customerToLongicmall($request){
        $user=[];
        if($request['isBindAccount']=='isBindAccount'){//绑定已用商城用户
            $user=M('user')->where(['user_name'=>$request['user_name']])->find();
            if(!$user) return ['error'=>-1,'msg'=>'没有此用户'];
            if(!hash_check($request['password'],$user['user_pass'])) return ['error'=>-2,'msg'=>'用户密码错误'];
        }else{//新增商城用户
            if(!$this->checkFormat(['type'=>'mobile','value'=>$request['mobile']])) return ['error'=>-1,'msg'=>'手机号码格式错误'];
            if(!$this->checkFormat(['type'=>'password','value'=>$request['password']])) return ['error'=>-1,'msg'=>'密码格式错误'];

            $user=M('user')->field('id')->where(['user_name'=>$request['user_name'],'user_mobile'=>$request['mobile'],'_logic'=>'or'])->find();
            if($user) return ['error'=>-1,'msg'=>'用户名或手机已注册'];

            $request['user_type']=1;
            if(isset($request['user_type'])&&$request['user_type']=='company'){//企业帐号申请
                $request['user_type']=2;
                $result=D('user')->registerPCCompnay($request);
                if($result['error']<0) return $result;
            }else{//个人账户
                $result=D('user')->registerPC($request);
                if($result['error']<0) return $result;
            }
            $user=[
                'id'=>$result['data']['user_id'],
                'mobile'=>$request['mobile'],
            ];
        }

        $result=D('customer')->customerToLongicmall($request,$user);
        return $result;
    }




}