<?php

// +----------------------------------------------------------------------
// | FileName:   LoginEvent.class.php
// +----------------------------------------------------------------------
// | Dscription:客户订单表格下载
// +----------------------------------------------------------------------
// | Date:  2018/05/18 22:16
// +----------------------------------------------------------------------
// | Author: kelly  <466395102@qq.com>
// +----------------------------------------------------------------------

namespace Home\Design;


class OrderPayDesign implements PayInterfaceDesign {

    public $data;
    public $result;

    public function add(){
        $this->result=D('Home/Order')->orderPayStep($this->data,false);
        return $this;
    }

    public function set($key='',$value=''){
        if(is_array($key)){
            foreach($key as $k=>$v){ $this->data[$k]=$v; }
        }else{
            $this->data[$key]=$value;
        }

        return $this;
    }

}
