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
namespace Small\Model;

use Think\Model;

class CustomerModel extends Model
{

    /**
     * @desc 用户绑定商城帐号
     *
     */
    public function customerToLongicmall($request,$one){
        $xcx_user=M('user','xcx_')->where(['id'=>$request['xcx_user_id']])->find();

        $user_data=[
            'user_id'=>$one['id'],
            'nick_name'=>$one['nick_name'],
            'mobile'=>$one['mobile']?:'',
        ];
        $dx_user_data=[
            'dx_user_id'=>$one['id'],'xcx_user_id'=>$request['xcx_user_id']
        ];
        $dx_user_history_data=[
            'dx_user_id'=>$one['id'],
            'xcx_user_id'=>$request['xcx_user_id'],
            'dx_user'=>json_encode($one)?:'',
            'xcx_user'=>json_encode($xcx_user)?:'',
        ];

        $m=M('user','xcx_');
        $m->startTrans();
        $result=$m->field('user_id,nick_name,mobile')->where(['appid'=>$request['appid'],'openid'=>$request['openid']])->save($user_data);
        if(!M('dx_user','xcx_')->where($dx_user_data)->find()) $result2=M('dx_user','xcx_')->add($dx_user_data);
        $result3=M('dx_user_history','xcx_')->add($dx_user_history_data);

        if($result===false||$result2===false||!$result3){
            $m->rollback();
            return ['error'=>-1,'msg'=>'绑定失败'];
        }else{
            $m->commit();
            return ['error'=>1,'msg'=>'绑定成功'];
        }
    }

    /**
     * @desc 用户第一次登陆小程序保存信息
     *
     */
    public function userFirstLoginAdd($request){
        $result=M('user','xcx_')->field('id')->where(['openid'=>$request['openid']])->find();
        if($result) return ['error'=>1,'msg'=>'老铁','data'=>['xcx_user_id'=>$result['id']]];

        $field='appid,openid,unionid,avatarUrl,city,country,language,nickName,province';
        $request['unionid']=$request['unionId'];
        $request['nickName']=$this->filter($request['nickName']);
        $result=M('user','xcx_')->field($field)->add($request);

        if(!$result) return ['error'=>-1,'msg'=>'failed'];
        return ['error'=>1,'msg'=>'success','data'=>['xcx_user_id'=>$result]];
    }

    /**
     * @desc 用户信息更新
     *
     */
    public function xcxUserInfoUpdate($request){
        $field='unionid,avatarUrl,city,country,language,nickName,province';
        $result=M('user','xcx_')->field($field)->where(['openid'=>$request['openid']])->save($request);
        if($result===false) return ['error'=>-1,'msg'=>'failed'];
        return ['error'=>1,'msg'=>'success'];
    }
    /**
     * $str  微信昵称
     **/
    public function filter($str) {
        if($str){
            $name = $str;
            $name = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '', $name);
            $name = preg_replace('/xE0[x80-x9F][x80-xBF]‘.‘|xED[xA0-xBF][x80-xBF]/S','?', $name);
            $return = json_decode(preg_replace("#(\\\ud[0-9a-f]{3})#ie","",json_encode($name)));
            if(!$return){
                return $this->jsonName($return);
            }
        }else{
            $return = '';
        }
        return $return;

    }



}