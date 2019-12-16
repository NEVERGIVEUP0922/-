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
namespace Small\Logic;

use Small\Logic\BaseLogic;

class BasketLogic extends BaseLogic
{
    public $limit='0,10';
    public $basketId='';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 购物车
     *
     */
    public function basket($where=[],$limit='',$field='',$order='',$relation=false,$user_id='',$relation_where=[],$is_invoice=1){
        if(!$field) $field='id,basket_id';
        if(!$limit) $limit=$this->limit;

        $m=D('basket');
        $m->where($where)->field($field);
        if($order) $m->order($order);
        if($relation) $m->$relation($relation_where)->relation(true);
        $list=$m->limit($limit)->select();

       if(!$list[0]['basket_detail']&&!$list[0]['basket_detail_sample']){
            return ['error'=>-1,'msg'=>'没有商品'];
       }

        $goodsList=array_merge($list[0]['basket_detail'],$list[0]['basket_detail_sample']);
        $pId=[];
        $k=$v=$k2=$v2=[];
        foreach($goodsList as $k=>$v){
            $pId[]=$v['pid'];
        }
        $pId=array_unique($pId);
        $productLogic=D('Small/Product','Logic');
        $productList=$productLogic->productList(['id'=>['in',$pId]],'','id,p_sign,discount_num,pack_unit,parameter,sell_num,fitemno,id,package,describe_image,is_tax,tax,earnest_scale,is_earnest','','backetGoods',$user_id);

        if($productList['error']<0) return ['error'=>-400,'msg'=>'没有数据','data'=>['list'=>'']];

        $pId_img=[];
        $k=$v=[];
        foreach($productList['data']['list'] as $k=>$v){
            $pId_img[$v['id']]=$v;
        }
        $k=$v=[];
        if($list[0]['basket_detail']){//付费商品
            foreach($list[0]['basket_detail'] as $k=>&$v){
                if(!$pId_img[$v['pid']]){
                    //商品不存在，或下线,删除购物车商品
                }
                if($pId_img[$v['pid']]) $v=array_merge($v,$pId_img[$v['pid']]);
            }
        }
        unset($v);

        if($relation=='createGoods'){ //结算价
            $productSettlement=$productLogic->productSettlementPrice($list[0]['basket_detail'],$is_invoice);
//            if($productSettlement['error']<0) return $productSettlement;
            $list[0]['basket_detail']=$productSettlement['data']?:[];
        }

        if($list[0]['basket_detail_sample']){//样品
            foreach($list[0]['basket_detail_sample'] as $k=>&$v){
                if($pId_img[$v['pid']]) $v=array_merge($v,$pId_img[$v['pid']]);
                if(!$pId_img[$v['pid']]){
                    //商品不存在，或下线,删除购物车商品
                }
            }
        }
        unset($v);

        return ['error'=>0,'msg'=>'success','data'=>['list'=>$list[0]]];
    }

    /**
     * @desc 购物车操作
     *
     */
    public function basketAction($request){
        $basketId=$this->userBasketId($request['user_id']);
        if(!$basketId) return ['error'=>-1,'msg'=>'basketId错误'];

        $productList=$request['productList'];
        $check=$this->basketActionCheck($productList,$request['user_id']);
        if($check['error']<0) return $check;
        $productList=$check['data']['list'];

        $basketM=D('Small/basket');
        if($request['action']=='settlement'){//选中商品去结算
            $settlementResult='';
            $settlementResult=$basketM->basketSettlement($request['productList'],$basketId);
            return $settlementResult;
        }

        $k=$v=[];
        foreach($productList as $k=>$v){
            $v['num']=$v['num']?(int)$v['num']:1;
            $one_result=[];
            $one_result=$basketM->oneProductToBasket($basketId,$v);
            if($one_result['error']<0){
                return $one_result;
            }
        }
        return ['error'=>0,'msg'=>'success'];
    }

    /**
     * @desc 用户新basket_id
     */
    public function newBasketId($key='daxin'){
        return md5(session_id().uniqid().$key);
    }

    /**
     * @desc 用户basket_id
     */
    public function userBasketId($user_id){
        if(!$user_id) return false;
        if($this->basketId) return $this->basketId;
        $basketId='';
        $m=M('basket');
        $basketId=$m->field('basket_id')->where(['user_id'=>$user_id])->find();
        $basketId['basket_id'];
        if(!isset($basketId['basket_id'])||!$basketId['basket_id']){
           $this->basketId=$basketId['basket_id']=$this->newBasketId('xcx');
           //添加到basket主表
           if(!$m->field('user_id,basket_id')->add(['user_id'=>$user_id,'basket_id'=>$this->basketId])) return false;
        }
        return $basketId['basket_id'];
    }

    /**
     * @desc 购物车操作商品前检测
     * @param [[pid,num]]
     *
     */
    public function basketActionCheck($productList,$user_id){
        if(!$productList) return ['error'=>-1,'msg'=>'商品错误'];

        $pId_arr=[];
        foreach($productList as $k=>$v){
            if($v['action']=='delete'){//删除商品,默认非样品
                continue;
            }
            $pId_arr[]=$v['pid'];

            //样品与非样品处理
            if(!$v['type']){
                if(M('user_product_example')->field('id')->where(['uid'=>$user_id,'pid'=>$v['pid'],'max_num'=>['egt',$v['num']],'step'=>['gt',0]])->find()){
                    $v['type']='sample';
                }else{
                    $v['type']='';
                }
            }
            $productList[$k]=$v;
        }
        $pId_arr=array_unique($pId_arr);
        $products=D('Small/Product','Logic')->productList(['id'=>['in',$pId_arr],'is_online'=>1],'','id,p_sign');
        if(count($pId_arr)!=$products['data']['count']) return ['error'=>-1,'msg'=>'商品错误2'];

        return ['error'=>0,'msg'=>'pass','data'=>['list'=>$productList]];
    }



}