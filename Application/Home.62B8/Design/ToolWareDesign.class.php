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


class ToolWareDesign{

    public $ware;
    public $data;
    public $result;

    public function set($alias,$obj,$data=''){
        if(!isset($this->ware[$alias])||$this->ware[$alias]!==null){
            $this->ware[$alias]= $obj;
            $this->data[$alias]= $obj->data = $data;
        }
        return $this;
    }

    public function action($actionObj_arr='*'){
        if($actionObj_arr=='*'){
            foreach($this->ware as $k=>$v){
                $this->result[$k]=$v->action()->result;
            }
        }else{
            foreach($this->ware as $k=>$v){
                if(in_array($k,$actionObj_arr)){
                    $this->result[$k]=$v->action()->result;
                }
            }
        }
        return $this;
    }


}
