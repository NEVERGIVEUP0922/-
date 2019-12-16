<?php
namespace Small\Controller;
use Think\Controller;
class BrandController extends BaseController {

    /**
     * @desc 品牌列表
     *
     */
    public function brandList(){
        $request=$this->post;
        $where=[];

        if(isset($request['is_hot'])&&$request['is_hot']==1) $where[]=['is_hot'=>$request['is_hot']];
        if(isset($request['first'])&&$request['first']) $where['first']=$request['first'];

        $list=D('Brand','Logic')->brandList($where);
        if(isset($request['is_hot'])&&$request['is_hot']==1&&$list['data']){
            $toList=[];
            foreach($list['data'] as $k=>$v){
                foreach($v as $k2=>$v2){
                    $toList=$v2;
                }
            }
            $list['data']['list']=$toList;
        }else{
            //补全26个字母
            $AZ=range('A','Z');
            $AZ_full=[];
            foreach($AZ as $v){ $AZ_full[$v]=[]; }
            $list['data']['list']=array_merge($AZ_full,$list['data']['list']);
        }

        $this->return_data['data']=$list['data'];
        $this->return_data['statusCode']=$list['error'];
        $this->return_data['msg']=$list['msg'];
        $this->ajaxReturn($this->return_data);
    }



}