<?php
namespace Small\Controller;
use Think\Controller;

class MessageController extends BaseController {
    protected $appid;
    protected $secret;

    /**
     * @desc 给用户发消息
     *
     */
    public function sendMessageToUser(){
        $request=$this->post;

        $customer=$this->getCustomer($request['session_token']);

        $result=D('Small/XCXAPI','Logic')->getAccess_token($customer[2],$customer[4]);
        $access_token=$result['data'];

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }



}