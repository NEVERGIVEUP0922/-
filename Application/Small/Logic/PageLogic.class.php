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

class PageLogic extends Model
{
    public $limit='0,10';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 首页轮播图
     *
     */
    public function firstPageImg($where=[],$field=''){
        //广告图
        $list=M("advert_photo")->field('jump_url,photo_url')->where("status=1 and position=1")->order("sort desc")->limit(10)->select();
        return ['error'=>0,'msg'=>'success','data'=>['list'=>$list]];
    }





}