<?php
namespace Small\Controller;
use Think\Controller;
class LibraryController extends BaseController {

    /**
     * @desc 快递公司
     *
     */
    public function kdCompany(){
        $request=$this->post;
        $where=$relation_where=[];

        $page=$request['page']?:1;
        $pageSize=$request['pageSize']?:C('PAGE_PAGESIZE');
        $limit=($page-1)*$pageSize.','.$pageSize;
        $list=D('library','Logic')->kdCompany($where,$limit);

        $list['data']['page']=$page;
        $list['data']['pageSize']=$pageSize;
        $this->return_data['data']=$list['data'];
        $this->return_data['statusCode']=$list['error'];
        $this->return_data['msg']=$list['msg'];
        $this->ajaxReturn($this->return_data);
    }



}