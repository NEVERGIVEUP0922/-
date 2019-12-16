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

class BrandLogic extends Model
{
    public $limit='0,10';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 品牌列表
     *
     */
    public function brandList($where,$field){
        if(!$field) $field='first,brand_name,id,logo';

        $list=M('brand')->where($where)->field($field)->select();
        if(!$list) return ['error'=>-400,'msg'=>'没有数据'];

        $return_data=[];
        foreach($list as $k=>$v){
            $return_data[$v['first']][]=$v;
        }

        return ['error'=>0,'msg'=>'success','data'=>['list'=>$return_data]];
    }


}