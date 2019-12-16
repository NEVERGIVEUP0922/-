<?php

// +----------------------------------------------------------------------
// | FileName:   ProductController.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2018/5/09 10:00
// +----------------------------------------------------------------------
// | Author: kelly <466395102@qq.com>
// +----------------------------------------------------------------------
namespace  Admin\Event;


abstract class TableEvent
{
    private $ObTables=[];//表对像
    public $lists=[];//表数据结果
    public $lists_count=[];//表数据结果统计
    public $lists_temporary=[];//临时数据
    public $results=[];//操作结果
    protected $wheres=[];//搜索条件
    protected $fields=[];//字段
    protected $types=[];//返回的数据处理方式，是否去掉key
    protected $limits=[];//返回的数据处理
    protected $actions=[];//update，insert

    //数据表对像
    function addObTable(ModelEvent $ObTable,$where='',$field='',$type='',$limit=[1,10000],$sort=''){
        $this->ObTables[]=$ObTable;
        $this->wheres[]=$where;
        $this->fields[]=$field;
        $this->types[]=$type;
        $this->limits[]=$limit;
        $this->sorts[]=$sort;
        return $this;
    }

    //添加编辑的数据表对像
    function addObTableAction(ModelEvent $ObTable,$where='',$list='',$field='',$action=''){
        $this->ObTables[]=$ObTable;
        $this->wheres[]=$where;
        $this->fields[]=$field;
        $this->lists[]=$list;
        $this->actions[]=$action;
        return $this;
    }

    //更新数据
    function toUpdates($to='toUpdate'){
        foreach($this->ObTables as $k=>$ObTable){
            $this->lists[$k]=$ObTable->$to($this->wheres[$k],$this->lists[$k],$this->fields[$k],$this->actions[$k]);
            $this->results[$k]=$ObTable->result;
        }
        return $this;
    }

    //显示数据
    function toLists(){
        foreach($this->ObTables as $k=>$ObTable){
            $this->lists[$k]=$ObTable->toList($this->wheres[$k],$this->fields[$k],$this->limits[$k],$this->sorts[$k]);
            $this->results[$k]=$this->ObTables[$k]->result;
            $this->lists_count[$k]=$this->ObTables[$k]->count;
        }
        return $this;
    }

    /**
     * 数据格式化
     */
    function toTypes($is_index=false){
        foreach($this->lists as $k=>$list){
            $this->ObTables[$k]->toType($this->types[$k],$is_index);
            $this->lists[$k]=$this->ObTables[$k]->list;
        }
        return $this;
    }

    //订单数据
    function toOrdersInfo(){
        foreach($this->ObTables as $k=>$ObTable){
            $this->lists[$k]=$ObTable->toOrderInfo($this->wheres[$k],$this->fields[$k],$this->limits[$k]);
        }
        return $this;
    }

    //订单数据格式化
    function toOrdersType($is_lists=true){
        foreach($this->ObTables as $k=>$ObTable){
            $this->ObTables[$k]->toOrderType($is_lists,$this->types[$k]);
            $this->lists[$k]=$this->ObTables[$k]->list;
        }
        return $this;
    }



}