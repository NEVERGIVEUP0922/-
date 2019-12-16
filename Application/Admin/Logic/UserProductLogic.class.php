<?php

// +----------------------------------------------------------------------
// | FileName:   LoginEvent.class.php
// +----------------------------------------------------------------------
// | Dscription:客户订单表格下载
// +----------------------------------------------------------------------
// | Date:  2018/05/18 22:16
// +----------------------------------------------------------------------
// | Author: kelly  <466395102@qq.com>
// +----------------------------------------------------------------------

namespace Admin\Logic;


class UserProductLogic {

    /**
     * @desc 样品申请
     *
     */
    public function userProductSample($request=[],$where=[]){
        $list=D('Home/UserProduct','Design')->userProductSample($request,$where);
//        if($request['action']=='check'&&$list['error']===0){//审核通过,添加客户样品
//
//        }
        return $list;
    }


}
