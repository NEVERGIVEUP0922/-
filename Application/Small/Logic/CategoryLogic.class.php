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

class CategoryLogic extends Model
{
    /**
     * @desc 分类列表
     *
     */
    public function categoryList(){
        $list=D('Admin/Category')->productCategoryInfinite(M('category'));
        return ['error'=>$list['error'],'msg'=>'success','data'=>['list'=>$list['data']['category']]];
    }


}