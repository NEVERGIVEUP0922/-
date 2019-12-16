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


class OrderDisableDesign implements UserDisableDesign {

    protected $disableList;
    protected $isMaster;

    public function __construct()
    {
        $this->getConfig();
        $this->isMaster();
    }

    public function getConfig()
    {
        // TODO: Implement getConfig() method.

        $access = include(APP_PATH.'/Home/Conf/companySonAccess.php');
        $this->disableList=$access['MASTER_DISABLE'][__CLASS__];
        $disableList=[];
        array_walk($this->disableList,function($value,$index) use(&$disableList){
            $disableList[strtolower($index)]=$value;
        });
        $this->disableList=$disableList;
        return $this;
    }

    //是否是自已的订单
    public function isMaster(){
        $order_sn=I('request.order_sn');
        if($order_sn){
            $this->isMaster=D('Home/Order')->isMaster($order_sn);
        }
        return $this;
    }

    //功能限制
    public function isDisable(){
        if(!$this->disableList) return true;//没有限制
        if($this->isMaster) return true;//没有限制

        $disable_arr=array_keys($this->disableList);

        $path=strtolower(MODULE_NAME).'/'.strtolower(CONTROLLER_NAME).'/'.strtolower(ACTION_NAME);

        if(in_array($path,$disable_arr)){
            return false;
        }
        return true;//没有限制
    }

}
