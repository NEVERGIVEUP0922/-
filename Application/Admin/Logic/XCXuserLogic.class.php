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
namespace Admin\Logic;

use Think\Model;

class XCXuserLogic
{
    public $limit='0,10';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 小程序用户列表
     *
     */
    public function userList($where,$limit='',$field=''){
        if(!$field) $field='id,nickname,user_id,avatarurl,city,country,province,language,country_code,create_at,update_at';

        $list=M('user','xcx_')->field($field)->where($where)->limit($limit)->select();
        $count=M('user','xcx_')->where($where)->count();
        if(!$list) return ['error'=>-1,'msg'=>'没有数据'];

        $userId_arr=[];
        foreach($list as $k=>$v){
            if($v['user_id'])$userId_arr[]=$v['user_id'];
        }
        if($userId_arr){
            $pcUser=[];
            $userId_arr=array_unique($userId_arr);
            $pcUser=D('customer')->customerList(['id'=>['in',$userId_arr]]);

            if($pcUser['data']['list']){
                $pcUserList=[];
                foreach($pcUser['data']['list'] as $k=>$v){
                    $pcUserList[$v['id']]=$v;
                }
            }

            foreach($list as $k=>$v){
                if($pcUserList[$v['user_id']]) $list[$k]=array_merge($v,$pcUserList[$v['user_id']]);
            }
        }

        return ['error'=>0,'msg'=>'success','data'=>['list'=>$list,'count'=>$count]];
    }


}