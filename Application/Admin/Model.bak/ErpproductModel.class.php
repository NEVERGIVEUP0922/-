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

class ErpproductModel extends BaseModel
{

    /*
   * erp产品信息
   * $FItemNo=[1,2,3];
   */
    public function erpProduct($FItemNo){
        $erpProduct=M('product','erp_')->where(['FItemNo'=>['in',$FItemNo]])->select();
        if(!$erpProduct) return ['error'=>1,'msg'=>'erp产品信息错误'];
        $product_itemno=[];
        foreach($erpProduct as $k=>$v){ $product_itemno[$v['fitemno']]=$v; }
        return ['error'=>0,'data'=>$product_itemno];
    }


    /*
     * erp成本列表
     *
     */
    public function erpProductList($where='',$page='',$pageSize='',$order=''){
        $bargain=M('product','erp_');
        $list=$this->baseList($bargain,$where,$page,$pageSize,$order);
        return $list;
    }















}