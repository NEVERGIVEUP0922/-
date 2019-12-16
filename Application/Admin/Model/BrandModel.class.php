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

class BrandModel extends BaseModel
{

    public $price_init_set;

    /*
     * 品牌列表
     *
     */
    public function brandList($where,$page,$pageSize){
        $brand=M('brand')->where($where)->limit(($page-1)*$pageSize,$pageSize)->select();
        $count=M('brand')->where($where)->count();
        if(!$brand) return ['error'=>1,'msg'=>'没有匹配到品牌'];
        else return ['error'=>0,'data'=>['list'=>$brand,'count'=>$count]];
    }

    /*
     * 品牌列表编辑或新增
     *
     */
    public function brandListAction($request){
        $check=[
            'first'=>1,
            'brand_name'=>1,
            'logo'=>1,
//            'is_hot'=>1,
//            'href'=>1,
        ];
        foreach($check as $k=>$v){
            if(!isset($request['data'][0][$k])) return ['error'=>1,'msg'=>'参数错误'];
        }

        $action=$request['action'];
        $brand=D('brand');
        $error=0;
        if($action=='edit'){
            $brands=M('brand')->where(['id'=>['in',$request['id']]])->select();
            if(count($brands)!=count($request['data'])) return ['error'=>1,'msg'=>'品牌信息错误'];

            $brand->startTrans();
            foreach($request['data'] as $k=>$v){
                $one_result=M('brand')->where(['id'=>$v['id']])->save($v);
                if($one_result===false) $error=1;
            }
            if($error){
                $brand->rollback();
                return ['error'=>1,'msg'=>'更新失败'];
            }else{
                $brand->commit();
                return ['error'=>0,'msg'=>'更新成功'];
            }

        }else if($action=='add'){
            $brand->startTrans();
            foreach($request['data'] as $k=>$v){
                if(isset($v['id'])) unset($v['id']);
                $one_result=M('brand')->add($v);
                if(!$one_result) $error=1;
            }
            if($error){
                $brand->rollback();
                return ['error'=>1,'msg'=>'添加失败'];
            }else{
                $brand->commit();
                return ['error'=>0,'msg'=>'添加成功'];
            }
        }

    }

    /*
     * 品牌删除
     *
     */
    public function brandsDelete($brandsId_arr){
        $result=M('brand')->where(['id'=>['in',$brandsId_arr]])->delete();
        if($result) return ['error'=>0,'msg'=>'删除成功'];
        return ['error'=>1,'msg'=>'删除失败'];
    }

    /*
     * 品牌id
     *
     */
    public function brandListId($where){
        $brands=$this->brandList($where);
        if($brands['error']!=0) return $brands;
        $brandIds=[];
        foreach($brands['data']['list'] as $k=>$v){
            $brandIds[]=$v['id'];
        }
        return ['error'=>0,'data'=>['list'=>$brandIds]];
    }






}