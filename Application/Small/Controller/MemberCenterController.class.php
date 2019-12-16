<?php
namespace Small\Controller;
use Think\Controller;
class MemberCenterController extends BaseController {

    /**
     * @desc 会员中心
     *
     */
    public function memberCenter(){
        $request=$this->post;
        $where=[];
        $order='';
        $field='';
        $show_data='';

        $userInfo=$this->getUserInfo();
        $where['user_id']=$userInfo['id'];
//        $where[]=[
//            'pay_status'=>0,
//            'is_comment'=>1,
//            '_logic'=>'or'
//        ];
        $show_data=in_array($request['show_data'],['memberCenter'])?$request['show_data']:'memberCenter';//显示的数据类型

        $page=$request['page']?:1;
        $pageSize=$request['pageSize']?:C('PAGE_PAGESIZE');
        $limit=($page-1)*$pageSize.','.$pageSize;
        $list=D('MemberCenter','Logic')->memberCenter($where,$limit,$field,$order,$show_data);

        $list['data']['userInfo']=$userInfo;
        $list['data']['page']=$page;
        $list['data']['pageSize']=$pageSize;
        $this->return_data['data']=$list['data'];
        $this->return_data['statusCode']=$list['error'];
        $this->return_data['msg']=$list['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 我的：收藏，浏览历史,收贷地址
     *
     */
    public function my(){
        $request=$this->post;
        $where=[];
        $order='';
        $field='';
        $show_data='';

        $userInfo=$this->getUserInfo();
        $request['user_id']=$where['id']=$userInfo['id'];
        $show_data=in_array($request['show_data'],['myCollect','myHistory','orderAddress'])?$request['show_data']:'myCollect';//显示的数据类型

        if(in_array($request['action'],['action','add','update','delete'])){//编辑信息
            $request['user_id']=$userInfo['id'];
            $result=D('MemberCenter','Logic')->myAction($show_data,$request);

            $this->return_data['statusCode']=$result['error'];
            $this->return_data['msg']=$result['msg'];
            $this->ajaxReturn($this->return_data);
        }

        $where_relation='';//关联查寻的条件
        if(isset($request['where_relation']['id'])&&$request['where_relation']['id']){
            $where_relation.='id='.$request['where_relation']['id'];
        }

        $page=$request['page']?:1;
        $pageSize=$request['pageSize']?:C('PAGE_PAGESIZE');
        $limit=($page-1)*$pageSize.','.$pageSize;
        $list=D('MemberCenter','Logic')->my($where,$limit,$field,$order,$show_data,$where_relation);

        $list['data']['userInfo']=$userInfo;
        $list['data']['page']=$page;
        $list['data']['pageSize']=$pageSize;
        $this->return_data['data']=$list['data'];
        $this->return_data['statusCode']=$list['error'];
        $this->return_data['msg']=$list['msg'];
        $this->ajaxReturn($this->return_data);
    }



}