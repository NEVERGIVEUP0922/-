<?php

// +----------------------------------------------------------------------
// | FileName:   ProductController.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2018/5/09 10:00
// +----------------------------------------------------------------------
// | Author: kelly <466395102@qq.com>
// +----------------------------------------------------------------------
namespace  Admin\Event;



class ProductEvent
{
    /**
     * @定单的可换erp型号列表
     * @param string $order_sn 订单编号
     * @return array  数据列表
     *
     */
    public function productsInfo(array $pSign_arr=[]){
        $Table=D('Admin/Action','Event');
        $where=["p.package"=>['in',$pSign_arr]];
        $Table->addObTable(D('Admin/Productpackageimg'),$where,'ppi.package,ppi.img',['index_arr','package']);
        $Table->toLists()->toTypes(true);
        return $Table->lists;
    }


}