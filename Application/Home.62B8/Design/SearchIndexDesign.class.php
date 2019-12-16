<?php

// +----------------------------------------------------------------------
// | FileName:   LoginEvent.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2018/05/18 22:16
// +----------------------------------------------------------------------
// | Author: kelly  <466395102@qq.com>
// +----------------------------------------------------------------------

namespace Home\Design;


class SearchIndexDesign
{
    protected $searchM;
    public $result;

    //保存搜索条件
    function setSearch($searchM){
        $this->searchM=$searchM;
        return $this;
    }

    //保存搜索条件
    function saveSearch($data,$list){
        $this->searchM->saveSearch($data,$list);
        return $this;
    }

    //使用搜索条件
    function indexSearch($data){
        $this->searchM->indexSearch($data);
        $this->result=$this->searchM->result;
        return $this;
    }

}