<?php

// +----------------------------------------------------------------------
// | FileName:   ProductModel.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/7 12:47
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace  Admin\Model;

use Think\Model;

class ErpitemclassModel extends BaseModel
{

    /*
     * erp产品分类数据
     *
     */
    public function itemClass(){
//       $itemCls=M('itemcls','t_')->field('fclsname')->where(['FParentClass'=>'0'])->select();
        $itemCls=M('itemcls','t_')->field('fclsname')->select();
       print_r($itemCls);

    }










}