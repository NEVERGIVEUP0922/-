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
        $erpProduct=M('product','erp_')->field('id,fstcb,store,vm,vm_name,create_time,last_time,ftem,ftem as fitemno')->where(['ftem'=>['in',$FItemNo]])->select();
        if(!$erpProduct) return ['error'=>1,'msg'=>'erp产品信息错误'];
        $product_itemno=[];
        foreach($erpProduct as $k=>$v){ $product_itemno[$v['ftem']]=$v; }
        return ['error'=>0,'data'=>$product_itemno];
    }


    /*
     * erp成本列表
     *
     */
    public function erpProductList($where='',$page='',$pageSize='',$order=''){
        $bargain=M('product','erp_');
        $list=$this->baseList($bargain,$where,$page,$pageSize,$order,'id,fstcb,store,vm,vm_name,create_time,last_time,ftem as fitemno');
        return $list;
    }

    /**
     * 产品负责人列表
     *
     */
    public function productPersonLiable($fitemno_arr){
        $list=M('product','erp_')->alias('ep')->field('ep.ftem,ep.vm,su.uid')
            ->join('left join sys_user as su on su.femplno=ep.vm')
            ->where(['ep.ftem'=>['in',$fitemno_arr]])
            ->select();
        if(!$list) return ['error'=>1,'msg'=>'没有数据'];

        $return=[];
        foreach($list as $k=>$v){
            $return[$v['ftem']]=$v['uid'];
        }
        return ['error'=>0,'data'=>$return];
    }














}