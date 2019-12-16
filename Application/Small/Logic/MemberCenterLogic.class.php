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

class MemberCenterLogic extends BaseLogic
{
    public $limit='0,10';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 会员中心
     *
     */
    public function memberCenter($where=[],$limit='',$field='',$order='',$relation=false){
        if(!$field) $field='nick_name,user_name,id';
        if(!$limit) $limit=$this->limit;
        if($where) $where=[$where,'order_status'=>['neq',100]];
        else $where=['order_status'=>['neq',100]];

        $m=M('order');
        $count=$m->where($where)->count();

        $field_order_count='pay_status,ship_status,is_comment,is_retreat,order_status';
        $list=$m->field($field_order_count)->where($where)->select();

        if(!$list) return ['error'=>0,'msg'=>'没有数据','data'=>['count'=>[
            'pay_num'=>0,
            'ship_num11'=>0,
            'ship_num12'=>0,
            'comment_num'=>0,
            'retreat_num'=>0,
            ]]];
        $pay_num=$ship_num11=$ship_num12=$comment_num=$retreat_num=0;
        $oneOrder=[];
        foreach($list as $k=>$v){
            $oneOrder=$this->orderStatus($v);
            if(isset($oneOrder['pay_num'])&&$oneOrder['pay_num']==1){
                $pay_num++;
            }
            if(isset($oneOrder['ship_num11'])&&$oneOrder['ship_num11']==1){
                $ship_num11++;
            }
            if(isset($oneOrder['ship_num12'])&&$oneOrder['ship_num12']==1){
                $ship_num12++;
            }
            if(isset($oneOrder['comment_num'])&&$oneOrder['comment_num']==1){
                $comment_num++;
            }
            if(isset($oneOrder['retreat_num'])&&$oneOrder['retreat_num']==1){
                $retreat_num++;
            }
        }
        $count=[
            'pay_num'=>$pay_num?$pay_num:0,
            'ship_num11'=>$ship_num11?$ship_num11:0,
            'ship_num12'=>$ship_num12?$ship_num12:0,
            'comment_num'=>$comment_num?$comment_num:0,
            'retreat_num'=>$retreat_num?$retreat_num:0,
        ];
        return ['error'=>0,'msg'=>'success','data'=>['count'=>$count?:[]]];
    }

    /**
     * @desc 我的：收藏，浏览历史,收贷地址
     *
     */
    public function my($where=[],$limit='',$field='',$order='',$relation='',$where_relation){
        if(!$field) $field='id';
        if(!$limit) $limit=$this->limit;
        $where['is_online']=1;

        $m=D('user');
        $m->where($where)->field($field);
        if($order) $m->order($order);
        if($relation) $m->$relation($where_relation)->relation(true);
        $list=$m->limit($limit)->select();

        if($relation=='orderAddress'){
            return ['error'=>0,'msg'=>'success','data'=>['list'=>$list]];
        }
        $favorite=$list[0][$relation];
        if(!$favorite) return ['error'=>-400,'msg'=>'没有'.$relation];

        $favorite=json_decode($favorite,true);
        $whereProductList=[
            'id'=>['in',$favorite]
        ];

        $listProductList=D('Product','Logic')->productList($whereProductList,$limit='',$field='',$order='',$relation='productList');
        return $listProductList;
    }

    /**
     * @desc 我的：收藏，浏览历史,收贷地址
     *
     */
    public function myAction($relation,$request){
        $m=D('user');
        $check='';
        if($request['action']!='delete'){
            $check=$relation.'Check';
            $check=$this->$check($request);
            if($check['error']<0) return $check;
        }else{
            $check['action']='delete';
        }

        $save=$relation.'Save';
        return D($relation)->$save($check,$request);
    }

    /**
     * @desc 我的数据检测：收贷地址
     *
     */
    public function orderAddressCheck($request){
        $request['status']=(int)$request['status']?1:0;

        $m=M('user_order_address');
        if($request['action']=='delete'){//删除
            return ['error'=>0,'msg'=>'pass','action'=>'delete'];
        }

        $action='add';
        if(isset($request['id'])){//编辑
            $action='edit';
            if(!$request['id']) return ['error'=>-1,'msg'=>'参数错误'];
            $one=[];
            $one=$m->where(['id'=>$request['id']])->find();
            if(!$one) return ['error'=>-1,'msg'=>'参数错误2'];
        }else{
            $count=M('user_order_address')->where(['user_id'=>$request['user_id']])->count();
            if($count>=5) return ['error'=>-1,'msg'=>'最多存5个地址'];
        }

        $must_key=['user_id','consignee','area_code','address','mobile'];
        $error_msg=':参数错误3';
        foreach($must_key as $v){
            if(APP_DEBUG) $error_msg=$v.$error_msg;

            if(!$request[$v]) return ['error'=>-1,'msg'=>$error_msg];
        }

        if($this->checkFormat(['value'=>$request['mobile'],'type'=>'mobile'])===false) return ['error'=>-1,'msg'=>'手机格式错误'];
        return ['error'=>0,'msg'=>'pass','action'=>$action];
    }

    /**
     * @desc 我的数据检测：我的收藏
     *
     */
    public function myCollectCheck($request){
        $pId_arr=M('product')->field('id')->where([['id'=>['in',$request['p_id']]]])->select();
        if(!is_array($request['p_id'])||count($pId_arr)!=count($request['p_id'])) return ['error'=>-1,'msg'=>'产品信息错误2'];
        return ['error'=>0,'msg'=>'pass'];
    }

    /**
     * @desc 我的数据检测：我的收藏
     *
     */
    public function myHistoryCheck($request){
        $one=M('product')->field('id')->where([['id'=>$request['p_id']]])->find();
        if(!$one) return ['error'=>-1,'msg'=>'产品信息错误'];
        return ['error'=>0,'msg'=>'pass'];
    }



}