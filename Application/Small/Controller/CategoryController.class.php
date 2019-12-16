<?php
namespace Small\Controller;
use Think\Controller;
class CategoryController extends \Common\Controller\BaseController {

    public $return_data=[ 'data'=>'', 'statusCode'=>1, 'msg'=>'连接成功'];

    /**
     * @desc 分类树
     *
     */
    public function categoryTree(){
        $list=D('Category','Logic')->categoryList();

        $this->return_data['data']=$list['data'];
        $this->return_data['statusCode']=$list['error'];
        $this->return_data['msg']=$list['msg'];
        $this->ajaxReturn($this->return_data);
    }



}