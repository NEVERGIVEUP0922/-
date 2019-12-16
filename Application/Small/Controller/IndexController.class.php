<?php
namespace Small\Controller;
use Think\Controller;

use Common\Controller\SmsController as sms;

class IndexController extends BaseController {

    use sms;

    /**
     * @desc 验证码
     *
     */
    public function verificationCode(){
        $request=$this->post;
        $phone=$request['mobile'];

        $result=D('Small/Tool','Logic')->verificationCode($phone);

        $this->return_data['data']='';
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 首页轮播图
     *
     */
    public function firstPageImg(){
        $request=$this->post;

        $result=D('Small/Page','Logic')->firstPageImg();

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }

}