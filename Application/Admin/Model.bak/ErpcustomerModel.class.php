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

class ErpcustomerModel extends BaseModel
{


    /*
     * erp客户列表
     *
     */
    public function erpCustomerList($where='',$page='',$pageSize='',$order=''){
        $bargain=M('customer','erp_');
        $list=$this->baseList($bargain,$where,$page,$pageSize,$order);
        return $list;
    }

    /*
     * erp管理员列表
     *
     */
    public function erpAdminList($where='',$page='',$pageSize='',$order=''){
        $bargain=M('admin','erp_');
        $list=$this->baseList($bargain,$where,$page,$pageSize,$order);
        return $list;
    }













}