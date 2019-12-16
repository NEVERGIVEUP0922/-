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

class ProductfitemnoModel extends BaseModel implements ModelEvent{

    //取出数据
    public function toList($where='',$field='',$limit=[0,10]){
        $page=(int)(($limit[0]-1)*$limit[1]);
        $pageSize=(int)$limit[1];
        $list=M('product_fitemno')->field($field)->where($where)->limit($page,$pageSize)->select();
        $this->list=$list;
        return $list;
    }

    /**
     * 订单数据
     * @param array $orderSn_arr 订单编号数组
     * @return array
     */
    public function toOrderInfo($where='',$field='',$limit=''){
        $list=M('product_fitemno')->alias('pf')
            ->join('left join dx_order_goods as og on pf.p_sign=og.p_name')
            ->where($where)
            ->field($field)
            ->group('order_sn,p_sign,fitemno')
            ->select();
        $this->list=$list;
        return $this;
    }

    /**
     * 订单数据格式化
     * @param array $orderSn_arr 订单编号数组
     * @return array
     */
    public function toOrderType($is_list=true,$type=[]){
        if($this->list){
            $newList=[];
            foreach($this->list as $k=>$v){
                $newList[$v['order_sn']][$v['p_sign']][]=$v;
            }
            $this->list=$newList;

            if(!$is_list){//多个字段信息转换成一个信息,是否可换型号
                $list_temporary=[];
                foreach($newList as $k=>$v){
                    $list_temporary[$k]=1;
                    foreach($v as $k2=>$v2){
                        if((int)count($v2)>1){
                            $list_temporary[$k]=count($v2);
                            break;
                        }
                    }
                }
                $this->list=[];
                $this->list=$list_temporary;
            }
        }
        return $this;
    }


}