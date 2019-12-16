<?php

// +----------------------------------------------------------------------
// | FileName:   OauthModel.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/10 15:51
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Small\Model;

use Think\Model;

class BaseModel extends Model
{
    protected $limit='0,10';//分页
    protected $fields=[];//查询条件
    protected $prefix='dx_';//表前辍
    public $list=[];//结果输出
    public $type=[];//结果输出类型

    /**
     * @desc 数据库查询
     *
     */
    public function toSql($fields=[],$limit=''){
        if(!$limit)  $limit=$this->limit;
        if(!$fields) $fields=$this->fields;
        if(!$fields) return ['error'=>1,'msg'=>'没有数据'];

        $m=M($this->tableName,$this->prefix)->alias('dp');
        $field_str='';
        foreach($fields as $v){
            if($v['where']) $m->where($v['where']);
            if(!$v['join']){
                $field_str=$v['field'];
                continue;
            }else{
                $field_str.=','.$v['field'];
            }

            $m->join($v['join']);
        }
        $list=$m->field($field_str)->limit($limit)->select();
        $this->list=$list?:[];

        return $this;
    }

    /**
     * @desc 列表分页
     *
     */
    public function toLimit($limit=null){
        if($limit) $this->limit=$limit;
        return $this;
    }

    /**
     * @desc sql原始数据
     *
     */
    public function toList($field=null,$limit=null){
        $this->listField($field)->toLimit($limit)->toSql();
        return $this;
    }



}