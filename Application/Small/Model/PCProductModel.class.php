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
namespace Small\Model;

use Think\Model;

class PCProductModel extends BaseModel
{
    protected $tableName='product';

    /**
     * @desc 商品信息
     *
     */
    public function listField($field=null)
    {
        if ($field === null) {
            $field = [
                'dx_product' => [
                    'field' => 'p_sign,pack_unit,parameter,sell_num',
                ],
                'erp_product' => [
                    'field' => 'store',
                    'join' => 'left join erp_product ep on ep.ftem=dp.fitemno',
                ],
                'dx_product_package_img' => [
                    'field' => 'img',
                    'join' => 'left join dx_product_package_img dppi on dppi.package=dp.package',
                ],
                'dx_product_price' => [
                    'field' => 'line,lft_num,right_num,unit_price',
                    'join' => 'left join dx_product_price as dpp on dpp.p_id=dp.id',
                ]
            ];
        }
        $this->fields = $field;
        return $this;
    }

    /**
     * @desc 商品列表
     *
     */
    public function productList(){
        $this->toList();
        if(!$this->list) return ['error'=>1,'msg'=>'没有数据'];
        $list=[];
        $level2='line,right_num,unit_price,lft_num';
        $level_data=[];
        foreach($this->list as $k=>$v){
            $level_data1=$level2_data=[];
            foreach($v as $k2=>$v2){
                if(strpos($level2,$k2)!==false){//二级数据
                    $level2_data[$k2]=$v2;
                }else{//一级数据
                    $level_data1[$k2]=$v2;
                }
            }
            $list[$v['p_sign']][]=$level2_data;
            $level_data[$level_data1['p_sign']]=$level_data1;
            $level_data[$level_data1['p_sign']]['price']=$list[$level_data1['p_sign']];
        }
        sort($level_data);
        $this->list=$level_data;

        return ['error'=>0,'msg'=>'success','data'=>['list'=>$this->list]];
    }



}