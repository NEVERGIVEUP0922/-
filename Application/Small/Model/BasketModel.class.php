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


class BasketModel extends LinkModel
{

    protected $_link = array(
    );

    /*
     * @desc 购物车商品列表
     *
     */
    public function basketGoods($relation_where=''){
        $this->_link=[
            'basket_detail'  =>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'basket_id',
                'mapping_key'  => 'basket_id',
                'mapping_fields'=>'basket_id,pid,type,num,status',
                'condition'=>$relation_where['basket_detail']?:'type=1'
            ],
            'basket_detail_sample'  =>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'basket_id',
                'mapping_key'  => 'basket_id',
                'mapping_fields'=>'basket_id,pid,type,num,status',
                'condition'=>$relation_where['basket_detail_sample']?:'type=1'
            ]
        ];
        return $this;
    }

    /*
     * @desc 购物车商品列表
     *
     */
    public function createGoods($relation_where=''){
        $this->_link=[
            'basket_detail'  =>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'basket_id',
                'mapping_key'  => 'basket_id',
                'mapping_fields'=>'basket_id,pid,type,num,status',
                'condition'=>$relation_where['basket_detail']?:'status=1 and type=1'
            ],
            'basket_detail_sample'  =>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'basket_id',
                'mapping_key'  => 'basket_id',
                'mapping_fields'=>'basket_id,pid,type,num,status',
                'condition'=>$relation_where['basket_detail_sample']?:'status=1 and type=1'
            ]
        ];
        return $this;
    }

    /**
     * @desc 添加到购物车
     * @param [pid,num,type,action]
     *
     */
    public function oneProductToBasket($basketId,$oneProduct){
        $type=$oneProduct['type']?:'';
        $action=$oneProduct['action']?:'';
        $oneProduct['basket_id']=$basketId;
        $where=['basket_id'=>$basketId,'pid'=>$oneProduct['pid']];

        $m='';
        if($type=='sample'){//样品
            $m=M('basket_detail_sample');
        }else{
            $m=M('basket_detail');
        }

        $result='';
        if($action=='delete'){//删除购物车商品
            $result=$m->where($where)->delete();

            if(!$result){
                return ['error'=>-1,'msg'=>$oneProduct['pid'].':failed'];
            }else{
                return ['error'=>0,'msg'=>'success'];
            }
        }

        $one=$m->field('id')->where($where)->find();
        if($one){
            $result=$m->where($where)->field('num')->save(['num'=>$oneProduct['num']]);
        }else{
            $result=$m->field('basket_id,num,pid')->add($oneProduct);
        }

        
		if($result===false){
			return ['error'=>-1,'msg'=>$oneProduct['pid'].':failed'];
		}else{
		
			if($oneProduct['num']<21&&$type=='sample'){
			
				$dres=M('basket_detail')->where($where)->delete();
				if($dres===false){
					return ['error'=>-1,'msg'=>$oneProduct['pid'].':failed'];
				}
			
			}
		
		}
	
	
		return ['error'=>0,'msg'=>'success'];
    }

    /**
     * @desc 购物车去结算
     * @param [pid]
     *
     */
    public function basketSettlement($productList,$basket_id){
        if(!$productList||!is_array($productList)) return ['error'=>-1,'msg'=>'商品信息错误'];

        $pId_arr=$pId_arr_sample=[];
        foreach($productList as $k=>$v){
            if($v['type']=='sample'){
                $pId_arr_sample[]=$v['pid'];
            }else{
                $pId_arr[]=$v['pid'];
            }
        }

        $m=M('basket_detail');
        $m->startTrans();
        if($pId_arr){
            $result=$m->where(['basket_id'=>$basket_id,'pid'=>['not in',$pId_arr]])->save(['status'=>0]);
            $result2=$m->where(['basket_id'=>$basket_id,'pid'=>['in',$pId_arr]])->save(['status'=>1]);
        }else{
            $result=$m->where(['basket_id'=>$basket_id])->save(['status'=>0]);
        }
        if($pId_arr_sample){
            $result3=M('basket_detail_sample')->where(['basket_id'=>$basket_id,'pid'=>['not in',$pId_arr_sample]])->save(['status'=>0]);
            $result4=M('basket_detail_sample')->where(['basket_id'=>$basket_id,'pid'=>['in',$pId_arr_sample]])->save(['status'=>1]);
        }else{
            $result3=M('basket_detail_sample')->where(['basket_id'=>$basket_id])->save(['status'=>0]);
        }

        if($result===false||$result2===false||$result3===false||$result4===false){
            $m->rollback();
            return ['error'=>-1,'msg'=>'failed'];
        }else{
            $m->commit();
            return ['error'=>0,'msg'=>'success'];
        }
    }

}