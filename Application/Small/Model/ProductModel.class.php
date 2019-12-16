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
use Small\Model\ErpProductModel;

class ProductModel extends LinkModel
{

    protected $_link = array(
        'ErpProduct'  =>[
            'mapping_type'  => self::HAS_ONE,
            'foreign_key'=>'ftem',
            'mapping_key'  => 'fitemno',
            'as_fields'=>'store',
        ],
        'product_package_img'  =>[
            'mapping_type'  => self::HAS_ONE,
            'mapping_key'  => 'package',
            'foreign_key'=>'package',
            'as_fields'=>'img'
        ],
        'product_price'  =>[
            'mapping_type'  => self::HAS_MANY,
            'class_name'    => 'product_price',
            'mapping_fields'=>'line,lft_num,right_num,unit_price',
            'foreign_key'=>'p_id',
        ]
    );

    /**
     * @desc 商品列表
     *
     */
    public function productList(){
        $this->_link=[
            'ErpProduct'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'ftem',
                'mapping_key'  => 'fitemno',
                'as_fields'=>'store',
            ],
            'product_package_img'  =>[
                'mapping_type'  => self::HAS_ONE,
                'mapping_key'  => 'package',
                'foreign_key'=>'package',
                'as_fields'=>'img'
            ],
            'product_price'  =>[
                'mapping_type'  => self::HAS_MANY,
                'class_name'    => 'product_price',
                'mapping_fields'=>'line,lft_num,right_num,unit_price',
                'foreign_key'=>'p_id',
            ],
            'category'  =>[
                'mapping_type'  => self::HAS_ONE,
                'mapping_key'  => 'cate_id',
                'foreign_key'=>'id',
                'as_fields'=>'cate_name',
            ]
        ];
        return $this;
    }

    /**
     * @desc 商品详情
     *
     */
    public function productDetail(){
        $this->_link=[
            'ErpProduct'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'ftem',
                'mapping_key'  => 'fitemno',
                'as_fields'=>'store',
            ],
            'product_detail'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'p_id',
                'as_fields'=>'img_path,pdf',
            ],
            'product_package_img'  =>[
                'mapping_type'  => self::HAS_ONE,
                'mapping_key'  => 'package',
                'foreign_key'=>'package',
                'as_fields'=>'img'
            ],
            'product_price'  =>[
                'mapping_type'  => self::HAS_MANY,
                'class_name'    => 'product_price',
                'mapping_fields'=>'line,lft_num,right_num,unit_price',
                'foreign_key'=>'p_id',
            ],
            'user_comment'  =>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'p_id',
                'mapping_order'=>'star desc',
                'mapping_fields'=>'type,re_id,content,images,star,create_at,id',
            ],
            'brand'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'id',
                'mapping_key'  => 'brand_id',
                'as_fields'=>'brand_name',
            ],
            'product_attribute'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'id',
//                'as_fields'=>'current_start,current_end,voltage_input_start,voltage_input_end,voltage_output_start,voltage_output_end,volume_length',
                'as_fields'=>'current_start,current_end,voltage_input_start,voltage_input_end,voltage_output_start,voltage_output_end,volume_length,custom_start,volume_length,volume_width,custom_end,custom1_start,custom1_end,custom2_start,custom2_end,',
            ],
            'category'  =>[
                'mapping_type'  => self::HAS_ONE,
                'mapping_key'  => 'cate_id',
                'foreign_key'=>'id',
                'as_fields'=>'cate_name',
            ]
        ];
        return $this;
    }

    /**
     * @desc 定单显示
     *
     */
    public function orderDetail(){
        $this->_link=[
            'product_package_img'  =>[
                'mapping_type'  => self::HAS_ONE,
                'mapping_key'  => 'package',
                'foreign_key'=>'package',
                'as_fields'=>'img'
            ],
        ];
        return $this;

    }
    /**
     * @desc 购物车
     *
     */
    public function backetGoods($where=''){
        $this->_link=[
            'ErpProduct'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'ftem',
                'mapping_key'  => 'fitemno',
                'as_fields'=>'store,fstcb',
            ],
            'product_detail'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'p_id',
                'as_fields'=>'img_path',
            ],
            'product_price'  =>[
                'mapping_type'  => self::HAS_MANY,
                'class_name'    => 'product_price',
                'mapping_fields'=>'line,lft_num,right_num,unit_price',
                'foreign_key'=>'p_id',
            ],
//            'user_product_bargain'  =>[
//                'mapping_type'  => self::HAS_ONE,
//                'class_name'    => 'user_product_bargain',
//                'mapping_fields'=>'id,uid,p_id,sys_uid,discount_price,min_buy,discount_price_tax,discount_price_invoice_change,is_invoice_change,return_price,init_cost,init_suggest_price,fitemno',
//                'condition'=>$where['user_product_bargain'],
//                'foreign_key'=>'p_id',
//            ],
            'brand'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'id',
                'mapping_key'  => 'brand_id',
                'as_fields'=>'brand_name',
            ],
            'category'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'id',
                'mapping_key'  => 'cate_id',
                'as_fields'=>'cate_name',
            ],
            'product_package_img'  =>[
                'mapping_type'  => self::HAS_ONE,
                'mapping_key'  => 'package',
                'foreign_key'=>'package',
                'as_fields'=>'img'
            ],
        ];
        return $this;
    }
    /**
     * @desc Bomlist
     *
     */
    public function bomList($where=''){
        $this->_link=[
            'ErpProduct'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'ftem',
                'mapping_key'  => 'fitemno',
                'as_fields'=>'store,fstcb',
            ],

            'product_price'  =>[
                'mapping_type'  => self::HAS_MANY,
                'class_name'    => 'product_price',
                'mapping_fields'=>'line,lft_num,right_num,unit_price',
                'foreign_key'=>'p_id',
            ],
//            'user_product_bargain'  =>[
//                'mapping_type'  => self::HAS_ONE,
//                'class_name'    => 'user_product_bargain',
//                'mapping_fields'=>'id,uid,p_id,sys_uid,discount_price,min_buy,discount_price_tax,discount_price_invoice_change,is_invoice_change,return_price,init_cost,init_suggest_price,fitemno',
//                'condition'=>$where['user_product_bargain'],
//                'foreign_key'=>'p_id',
//            ],
//            'product_package_img'  =>[
//                'mapping_type'  => self::HAS_ONE,
//                'mapping_key'  => 'package',
//                'foreign_key'=>'package',
//                'as_fields'=>'img'
//            ],
            'category'  =>[
                'mapping_type'  => self::HAS_ONE,
                'mapping_key'  => 'cate_id',
                'foreign_key'=>'id',
                'as_fields'=>'cate_name',
            ],
            'brand'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'id',
                'mapping_key'  => 'brand_id',
                'as_fields'=>'brand_name',
            ]
        ];
        return $this;
    }
    /**
     * @desc 商品议价
     *
     */
    public function productBargain($where=''){
        $this->_link=[
            'ErpProduct'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'ftem',
                'mapping_key'  => 'fitemno',
                'as_fields'=>'store,fstcb',
            ],
            'product_detail'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'p_id',
                'as_fields'=>'img_path',
            ],
            'product_price'  =>[
                'mapping_type'  => self::HAS_MANY,
                'class_name'    => 'product_price',
                'mapping_fields'=>'line,lft_num,right_num,unit_price',
                'foreign_key'=>'p_id',
            ],
            'user_product_bargain'  =>[
                'mapping_type'  => self::HAS_ONE,
                'class_name'    => 'user_product_bargain',
                'mapping_fields'=>'id,uid,p_id,sys_uid,discount_price,min_buy,discount_price_tax,discount_price_invoice_change,is_invoice_change,return_price,init_cost,init_suggest_price,fitemno',
                'condition'=>$where['user_product_bargain'],
                'foreign_key'=>'p_id',
            ],
            'product_package_img'  =>[
                'mapping_type'  => self::HAS_ONE,
                'mapping_key'  => 'package',
                'foreign_key'=>'package',
                'as_fields'=>'img'
            ],
        ];
        return $this;
    }

    /**
     * @desc 样品列表
     *
     */
    public function customerProductExampleList($where='',$page=0,$pageSize=100){
        $m=new \Admin\Model\ProductbargainModel;
        $field='id,uid,pid,step,max_num,fitemno';
        $where['step']=['gt',0];
        $list=$m->customerProductSampleList($where,$page,$pageSize,'',$field);
        if($list['error']!==0) return ['error'=>1,'msg'=>$list];
        $returnList=[];
        foreach($list['data']['list'] as $k=>$v){
            $returnList[$v['pid']]=$v;
        }
        return ['error'=>0,'data'=>['list'=>$returnList,'count'=>$list['data']['count']]];
    }

    /**
     * @desc 样品结算
     *
     */
    public function customerProductExampleAction($uid,$parentId=0){
        $basket_id=M('basket')->field('basket_id')->where(['user_id'=>$uid])->find();
        if(!$basket_id) return ['error'=>1,'msg'=>'购物车信息错误'];
        $basket_id=$basket_id['basket_id'];
        $sample=M('basket_detail_sample')->field('pid,num')->where(['basket_id'=>$basket_id,'status'=>1])->select();
        if(!$sample) return ['error'=>1,'msg'=>'没有结算的样品'];

        $pid_arr=$basket_info=[];
        foreach($sample as $k=>$v){
            $pid_arr[]=$v['pid'];
            $basket_info[$v['pid']]=$v;
        }

        $whereExample=[
            'uid'=>$parentId?:$uid,
            'pid'=>['in',$pid_arr],
        ];
        $productExample=$this->customerProductExampleList($whereExample);
        if($productExample['error'] !== 0){
            M('basket_detail_sample')->where(['basket_id'=>$basket_id,'pid'=>['in',$pid_arr]])->delete();
            return ['error'=>1,'msg'=>'没有结算的样品'];
        }

        $sample_true=$sample_false=[];
        foreach ($sample as $k => $v) {
            $productExample['data']['list'][$v['pid']]['buy_num']=$basket_info[$v['pid']]['num'];
            if ( isset($productExample['data']['list'][$v['pid']]) && (int)$productExample['data']['list'][$v['pid']]['max_num'] >= (int)$v['num']) {//按样品结算
                $sample_true[]=$v['pid'];
            }else{
                $sample_false[]=$v['pid'];
            }
        }

        if(!empty($sample_false)){
            M('basket_detail_sample')->where(['basket_id'=>$basket_id,'pid'=>['in',$sample_false]])->delete();
            return ['error'=>1,'msg'=>'有失效的样品'];
        }

        return ['error'=>0,'data'=>$sample_true,'listId_arr'=>$productExample['data']['list']];
    }

}