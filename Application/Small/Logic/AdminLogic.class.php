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

use Think\Model;

class AdminLogic extends Model
{
    public $limit='0,10';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 管理员信息
     *
     */
    public function adminList($where=[],$field=''){
        if(!$where) return ['error'=>-1,'msg'=>'没有搜索条件'];
        if(!$field) $field='su.nickname,su.uid,su.department_id,su.fullname,sud.department_level';

        $list=M('user','sys_')->alias('su')
            ->join('left join sys_user_department as sud on sud.id=su.department_id')
            ->where($where)
            ->field($field)
            ->select();

        if(!$list) return ['error'=>-1,'msg'=>'没有数据'];
        return ['error'=>0,'msg'=>'success','data'=>['list'=>$list]];
    }





}