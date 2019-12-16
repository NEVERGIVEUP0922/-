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

use Admin\Event\ModelEvent;
use Admin\Event\TableEvent;
use Think\Model;

class OrdersyncModel extends BaseModel{

    //取出数据
    public function toOrderInfo($where='',$field='',$limit=[0,10]){
        $page=(int)(($limit[0]-1)*$limit[1]);
        $pageSize=(int)$limit[1];
        $list=M('order_sync')
            ->field($field)
            ->where($where)
            ->limit($page,$pageSize)
            ->select();
        $this->list=$list;
        return $list;
    }



}