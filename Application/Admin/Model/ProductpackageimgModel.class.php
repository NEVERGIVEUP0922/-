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

class ProductpackageimgModel extends BaseModel{

    //取出数据
    public function toList($where='',$field='',$limit=[0,10]){
        $page=(int)(($limit[0]-1)*$limit[1]);
        $pageSize=(int)$limit[1];
        $list=M('product_package_img')->alias('ppi')
            ->field($field)
            ->join('left join dx_product p on p.package = ppi.package')
            ->where($where)
            ->group('ppi.package')
            ->limit($page,$pageSize)
            ->select();
        $this->list=$list;
        return $list;
    }



}