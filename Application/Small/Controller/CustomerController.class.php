<?php
namespace Small\Controller;
use Think\Controller;
class CustomerController extends BaseController {

    /**
     * @desc 客户信息
     *
     */
    public function customerInfo(){
        $customer=$this->getPCCustomer();

        $this->return_data['data']=$customer['data'];
        $this->return_data['statusCode']=$customer['error'];
        $this->return_data['msg']=$customer['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 用户评论
     *
     */
    public function userComment(){
        $request=$this->post;

        $userInfo=$this->getUserInfo();
        $request['user_id']=$userInfo['id'];

        $result=D('user','Logic')->userComment($request);

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 发票列表
     *
     */
    public function userInvoiceList(){
        $request=$this->post;

        $userInfo=$this->getUserInfo();
        $request['user_id']=$userInfo['id'];

        $result=D('user','Logic')->userInvoiceList($request);

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 发票抬头
     *
     */
    public function userInvoiceHeader(){
        $request=$this->post;

        $userInfo=$this->getUserInfo();
        $request['user_id']=$userInfo['id'];

        $result=D('user','Logic')->userInvoiceHeader($request);

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 用户分享二维码信息，记录
     *
     */
    public function userQRcodeShareSave(){
        $request=$this->post;
        kelly_log(json_encode($request),'xcx_share','INFO');

        $userInfo=$this->getUserInfo();
        $request['user_id']=$userInfo['id'];
        $request['xcx_user_id']=$userInfo['xcx_user_id'];

        $result=D('user','Logic')->userQRcodeShareSave($request);

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }

}