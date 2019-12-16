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


class SearchProductDesign implements SearchDesign
{
    protected $searchIndex_1='dx_search';
    protected $searchIndex_2;
    public $result;

    public function __construct(){
        $this->searchIndex_2=S($this->searchIndex_1)?:[];//二级键数组
    }

    //保存搜索条件(搜索字段，索引)
    public function saveSearch($data='',$list=[]){
        if(!$data['search']) return $this;

        //保存最终的搜索条件
        $sKey=$this->createKey3($data['search_type'],$data['search']);

        $val=S($sKey);
        $val['number']++;
        S($sKey,['search'=>$sKey,'number'=>$val['number'],'sql_index'=>$data['search']]);

        //更新搜索条件索引
        $current_createKey2=$this->createKey2($data['search_type'])?$this->createKey2($data['search_type']):'';

        if(!in_array($current_createKey2,$this->searchIndex_2)){
            array_push($this->searchIndex_2,$current_createKey2);
            S($this->searchIndex_1,$this->searchIndex_2);//二级键数组
        }

        //三级键数组
        $searchIndex_1_arr=S($this->searchIndex_1);
        $current_createKey2_val=S($current_createKey2)?:[];
        if(!in_array($sKey,$current_createKey2_val)){
            $current_createKey2_val[]=$sKey;
            S($current_createKey2,$current_createKey2_val);
        }

        return $this;
    }

    //使用搜索条件
    public function indexSearch($data=''){
        //当前搜索的
//        print_r($data);
//        $current_search_type=$this->createKey2($data['search_type']);
//        $current_key_source=S($current_search_type);
//        print_r($current_key_source);
//
//        foreach($current_key_source as $k=>$v){
//            print_r(S($v));
//        }

        //全部的搜索
        $data_key1=$this->searchIndex_1;
        $data_key2=S($this->searchIndex_1);
        $return_data=[];
        foreach($data_key2 as $k=>$v){
            $return_data[$v]=[];
            $one_data_key3=S($v);//一类搜索
            foreach($one_data_key3 as $k2=>$v2){
                $value3=S($v2);
                $return_data[$v][]=$value3;
            }
        }

        $return_data_sort=[];
        foreach($return_data as $k=>$v){
            if(!$v){
                unset($return_data[$k]);
                continue;
            }
            foreach($v as $k2=>$v2){
                $return_data_sort[$k][]=$v2['number'];
            }
        }
        foreach($return_data as $k=>$v){
            array_multisort($return_data_sort[$k],SORT_DESC,$v);
            $return_data[$k]=array_slice($v,0,10);
        }
        $this->result=$return_data;
        return $this;
    }

    //绶存的key
    public function createKey3($search_type='',$search=''){
        return $this->searchIndex_1.$search_type.$search;
    }

    public function createKey2($search_type=''){
        return $this->searchIndex_1.$search_type;
    }

}