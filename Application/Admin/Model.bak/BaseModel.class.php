<?php

// +----------------------------------------------------------------------
// | FileName:   ProductModel.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/7 12:47
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace  Admin\Model;

use Think\Model;

class BaseModel extends Model
{

    public $where=[];


    /**
     * @desc 数据库列表数据
     *
     */
    public function baseList($bargain,$where='',$page='',$pageSize='',$order='',$field='',$is_field=false){
        if($is_field) $bargain->field($field,true);
        else if($field) $bargain->field($field);
        else $bargain->field(['user_pass','password'],true);
        if($where) $bargain->where($where);
        if($page&&$pageSize) $bargain->limit(($page-1)*$pageSize,$pageSize);
        if($order) $bargain->order($order);
        $list=$bargain->select();

        if($where) $bargain->where($where);
        $count=$bargain->count();

        if($count==0) return ['error'=>1,'msg'=>'没有数据'];

        return ['error'=>0,'data'=>['list'=>$list,'count'=>$count]];
    }

    /**
     * @desc 数据库列表数据_type
     *
     */
    public function baseListType($bargain,$adminId_arr,$index,$field='',$is_field='',$return_arr=0){
        if($adminId_arr=='*') $where='';
        else $where=[$index=>['in',$adminId_arr]];
        $page=$pageSize='';
        $list=$this->baseList($bargain,$where,$page,$pageSize,'',$field,$is_field);
        if($list['error']!=0) return $list;
        $data=$list['data']['list'];
        $returnData=[];
        foreach($data as $k=>$v){
            if($return_arr===0) $returnData[$v[strtolower($index)]]=$v;
            else $returnData[$v[strtolower($index)]][]=$v;
        }
        return ['error'=>0,'data'=>['list'=>$returnData]];
    }

    /**
     * @desc 权限限制
     *
     */
    public function adminWhere($where){
        $adminWhere=[];
        if(is_array($where)) $where= array_merge($where,$adminWhere);
        return $where;
    }

    /**
     * @desc 搜索条件的where处理
     *
     */
    public function searchWhere($where){
        if(!is_array($where)) return ['error'=>1,'msg'=>'where条件错误'];
        $whereSql=[];
        foreach($where as $k=>$v){
            switch (trim($v['type'])){
                case 'like':$whereSql[$k]=['like',"%$v[value]%"];break;
                case 'in': $whereSql[$k]=['in',$v['value']];break;
                case 'egt': $whereSql[$k]=['egt',$v['value']];break;
                case 'elt': $whereSql[$k]=['elt',$v['value']];break;
                case 'neq': $whereSql[$k]=['neq',$v['value']];break;
                case 'between': $whereSql[$k]=['between',[$v['value'],$v['value2']]];break;
                default: $whereSql[$k]=$v['value'];
            }
        }
        return ['error'=>0,'data'=>$whereSql];
    }






}