<?php
namespace Small\Controller;
use Think\Controller;
class BasketController extends BaseController {

    public $basket_id;

    /**
     * @desc 购物车
     *
     */
    public function basket(){
        $request=$this->post;
        $where=[];
        $order='';
        $field='';
        $show_data=in_array($request['show_data'],['basketGoods'])?$request['show_data']:'basketGoods';//显示的数据类型

        $userInfo=$this->getUserInfo();
        $where['user_id']=$userInfo['id'];


        if($userInfo){//购物车商品是否可按样品结算更新
            $basket_id=$this->loginBasketId($userInfo['id']);
            $parentId=0;
            if(session('user_type')==20){
                $parent=M('user_son')->where(['user_id'=>$userInfo['id']])->find();
                $parentId=$parent['p_id'];
            }

            $where_detail=[
                'dbd.basket_id'=>$basket_id,
                'dbd.status'=>['in',[0,1]],
                'dupe.max_num>=dbd.num',
                'dupe.step'=>['gt',0],
                'dupe.uid'=>$parentId?:$userInfo['id'],
            ];
            $sampleId_arr=$sampleId_sample=[];
            $sampleId_result=$sampleId_result2=$sampleId_result3=$sampleId_result4='';
            M('basket_detail')->startTrans();

            $sampleId2=M('basket_detail_sample')->field('dbd.pid')->alias('dbd')->join('left join dx_user_product_example as dupe on dbd.pid=dupe.pid')->where($where_detail)->select();
            $sampleId2_arr=$sampleId2_sample=[];
            if(!$sampleId2){
                $basket_detail_sample=M('basket_detail_sample')->where(['basket_id'=>$basket_id])->select();
                if($basket_detail_sample){
                    foreach($basket_detail_sample as $k=>&$v){
                        unset($v['id']);
                    }
                    $sampleId_result3=M('basket_detail')->field('basket_id,type,pid,num')->addAll($basket_detail_sample);
                }
                $sampleId_result4=M('basket_detail_sample')->where(['basket_id'=>$basket_id])->delete();
            }else{
                foreach($sampleId2 as $k=>$v){
                    $sampleId2_arr[]=$v['pid'];
                    $sampleId2_sample[]=[
                        'basket_id'=>$basket_id,
                        'pid'=>$v['pid'],
                        'num'=>$v['num'],
                        'type'=>1,
                    ];
                }
                $sampleId_sample_last=M('basket_detail_sample')->field("basket_id,pid,num,type")->where(['basket_id'=>$basket_id,'pid'=>['not in',$sampleId2_arr]])->select();
                $sampleId_result3=M('basket_detail_sample')->where(['basket_id'=>$basket_id,'pid'=>['not in',$sampleId2_arr]])->delete();
                if($sampleId_sample_last){
                    foreach ($sampleId_sample_last as $sample_k=>$sample_last){
                        $sampleId_exit=M('basket_detail')->field("basket_id,pid,num,type")->where(['basket_id'=>$basket_id,'pid'=>$sample_last['pid']])->find();
                        if($sampleId_exit){
                            unset($sampleId_sample_last[$sample_k]);
                        }
                    }
                    if($sampleId_sample_last){
                        $sampleId_result4=M('basket_detail')->where(['pid'=>['not in',$sampleId_arr]])->addAll($sampleId_sample_last);
                    }
//                    $sampleId_result4=M('basket_detail')->where(['pid'=>['not in',$sampleId_arr]])->addAll($sampleId_sample_last);
//                    echo M()->getLastSql();
                }
            }

            $sampleId=M('basket_detail')->field('dbd.pid,dbd.num')->alias('dbd')->join('left join dx_user_product_example as dupe on dbd.pid=dupe.pid')->where($where_detail)->select();
            if($sampleId){
                foreach($sampleId as $k=>$v){
                    $sampleId_arr[]=$v['pid'];
                    $sampleId_sample[]=[
                        'basket_id'=>$basket_id,
                        'pid'=>$v['pid'],
                        'num'=>$v['num'],
                        'type'=>1,
                    ];
                }
                $sampleId_result=M('basket_detail')->where(['basket_id'=>$basket_id,'pid'=>['in',$sampleId_arr]])->delete();
                $sampleId_result2=M('basket_detail_sample')->addAll($sampleId_sample);
            }
            if($sampleId_result===false||$sampleId_result2===false||$sampleId_result3===false||$sampleId_result4===false){
                print_r($sampleId_result2);
                M('basket_detail')->rollback();
                $this->return_data['data']=[];
                $this->return_data['statusCode']=-1;
                $this->return_data['msg']='购物车更新失败';
                $this->ajaxReturn($this->return_data);

            }else{
                M('basket_detail')->commit();
            }
        }





        $page=$request['page']?:1;
        $pageSize=$request['pageSize']?:C('PAGE_PAGESIZE');
        $limit=($page-1)*$pageSize.','.$pageSize;
        $list=D('Basket','Logic')->basket($where,$limit,$field,$order,$show_data,$userInfo['id']);

        $list['data']['page']=$page;
        $list['data']['pageSize']=$pageSize;
        $this->return_data['data']=$list['data'];
        $this->return_data['statusCode']=$list['error'];
        $this->return_data['msg']=$list['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 购物车操作
     *
     */
    public function basketAction(){
        $request=$this->post;
        $userInfo=$this->getUserInfo();
        if($userInfo['error']<0){
            $this->return_data['data']=$userInfo['data'];
            $this->return_data['statusCode']=$userInfo['error'];
            $this->return_data['msg']=$userInfo['msg'];
            $this->ajaxReturn($this->return_data);
        }
        $request['user_id']=$userInfo['id'];
        $list=D('Basket','Logic')->basketAction($request);

        $this->return_data['data']=$list['data'];
        $this->return_data['statusCode']=$list['error'];
        $this->return_data['msg']=$list['msg'];
        $this->ajaxReturn($this->return_data);
    }
    /*
	 * 用户的basket_id
	 */
    public function loginBasketId($user_id=''){
        $loginBasketId='';
        $where=['user_id'=>$user_id];
        $basket=D('basket')->where($where)->find();
        if($basket){
            $loginBasketId=$basket['basket_id'];
        }else{
            $newBasketId=$this->newBasketId();
            D('basket')->data(['user_id'=>$user_id,'basket_id'=>$newBasketId])->add();
            $loginBasketId=$newBasketId;
        }
        return $loginBasketId;
    }


}