<?php

// +----------------------------------------------------------------------
// | FileName:   LoginEvent.class.php
// +----------------------------------------------------------------------
// | Dscription:请求的数据处理
// +----------------------------------------------------------------------
// | Date:  2018/05/18 22:16
// +----------------------------------------------------------------------
// | Author: kelly  <466395102@qq.com>
// +----------------------------------------------------------------------

namespace Home\Design;


class RequestMiddlewareDesign{

    public $request;//http请求
    public $verify;//验证
    public $supperData;//额外的数据
    public $action;//行动
    public $result=['error'=>0,'msg'=>'success','data'=>[]];//结果

    public function __construct($request,$action,$result,$supperData=null,$verify=null)
    {
        $this->request=$request;
        $this->verify=$verify;
        $this->action=$action;
        $this->result=$result;
        $this->supperData=$supperData;
    }



}
