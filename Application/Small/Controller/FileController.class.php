<?php
namespace Small\Controller;
use Think\Controller;
class FileController extends BaseController {

    /**
     * @desc 文件上传
     *
     */
    public function uploadFile(){
        $result=fileUpload();

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }

    public function qrcode()
    {
        $request=$this->post;

        $url='https://www.longicmall.com/pid';
        $filePath=md5($url).'.jpg';
        $result=D('Small/Library','Logic')->qrcode($url,$filePath);

        $this->return_data['data']['qrcode']=$filePath;
        $this->return_data['statusCode']=0;
        $this->return_data['msg']='';
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 小程序二维码
     *
     */
    public function xcxQRCode(){
        $request=$this->post;

        $customer=$this->getCustomer($request['session_token']);

        $userInfo=$this->getUserInfo('',false);
        $user_id=$userInfo['id'];

        //检测商品是否存在
        $oneProduct=D('Small/Product','Logic')->productList(['id'=>$request['pid']],'','id');
        if($oneProduct['error']<0){
            $this->return_data['data']=$oneProduct['data'];
            $this->return_data['statusCode']=$oneProduct['error'];
            $this->return_data['msg']='产品信息错误';
            $this->ajaxReturn($this->return_data);
        }

        //生成分享二维码
        $result=D('Small/XCXAPI','Logic')->createQRcodeShare($userInfo['xcx_user_id'],$request['pid'],$customer[2],$customer[4],'01');

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }



}