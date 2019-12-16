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
use EES\System\Redis;
use Small\Model\LinkModel;
class UserSampleModel extends LinkModel
{
    protected $tableName = 'user_sample';
    protected $pk='id';

    public function setTableName($table_name){
        $this->tableName=$table_name;
    }
    protected $_link = array(
        'user_product_example'  =>[
            'mapping_type'  => self::HAS_MANY,
            'foreign_key'=>'id',
            'mapping_key'  => 'origin',
            'mapping_fields'=>'*',
        ],
    );

    public function userSampleList($arguments){
        $this->_link=[
            'user'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'id',
                'mapping_key'  => 'user_id',
                'as_fields'=>'nick_name,fcustjc,sys_uid',
            ],
        ];
        return $this;
    }
    public function sampleList(){
        $this->_link=[
            'product'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'id',
                'mapping_key'  => 'pid',
                'as_fields'=>'p_sign,cate_id,brand_id,package',
            ],
        ];
        return $this;
    }
    public  function  userSampleProductList($item){
        $item=json_decode($item,true);
        foreach ($item as $k=>$v){
            unset($where);
            if(isset($v['id'])&&$v['id']) $where['p.id']=$v['id'];
//            if(isset($v['p_sign'])&&$v['p_sign']) $where['p_sign']=$v['p_sign'];
//            if(isset($v['brand'])&&$v['brand']) $where[]=[
//                "brand_id IN (select id from dx_brand where brand_name='$v[brand]')"
//            ];
//            if(isset($v['package'])&&$v['package']) $where['package']=$v['package'];
            if(isset($where)&&$where){
                $item[$k]['productList']=M('product')->alias("p")->field("p.*,cate_name,brand_name")->join("dx_brand b on b.id=p.brand_id")->join("dx_category c on c.id=p.cate_id")->where($where)->select();
            }else{
                $item[$k]['productList']=[];
            }

        }
        return $item;
    }

    public function  homeSampleList($homeList){
            foreach ($homeList as &$v){
                $product_info=M('product')->alias("p")->field("cate_name,brand_name,p_sign,fitemno")->join("dx_brand b on b.id=p.brand_id")->join("dx_category c on c.id=p.cate_id")->where(['p.id'=>$v['pid']])->find();
                $v=array_merge($v,$product_info);
            }
            return $homeList;
    }



}