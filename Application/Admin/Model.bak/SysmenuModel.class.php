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

class SysmenuModel extends BaseModel
{

    /**
     * @desc 系统菜单
     *
     */
    public function sysMenuList($where='',$page='',$pageSize=''){
        $list=$this->baseList(M('menu','sys_'),$where,$page,$pageSize);
        return $list;
    }

    /**
     * @desc 系统菜单编辑或添加
     *
     */
    public function sysMenuAction($request){
        if($request['action']=='delete'){
            $menuM=M('menu','sys_');
            $hasSlave=$menuM->where(['menu_fid'=>$request['menu_id']])->find();
            if($hasSlave) return ['error'=>1,'msg'=>'有子类'];
            $result=$menuM->where(['menu_id'=>$request['menu_id']])->delete();
            if(!$result) return ['error'=>1,'msg'=>'删除失败'];
            else return ['error'=>0,'msg'=>'删除成功'];
        }
        $functionList=$this->baseList(M('function','sys_'),['function_id'=>$request['function_id']]);
        if($functionList['error']!=0) return ['error'=>1,'msg'=>'菜单功能错误'];
        $oneFunction=$functionList['data']['list'][0];
        if($request['menu_fid']!=0){
            $menuList=$this->baseList(M('menu','sys_'),['menu_id'=>$request['menu_fid'],'menu_level'=>1]);
            if($menuList['error']!=0) return ['error'=>1,'msg'=>'菜单父级信息错误'];
            $oneMenu=$menuList['data']['list'][0];
            $menu_level=2;
        }else{
            $menu_level=1;
        }

        $addData=[
            'function_id'=>$request['function_id'],
            'menu_name'=>$request['menu_name'],
            'icon'=>$request['icon'],
            'menu_fid'=>$request['menu_fid'],
            'menu_seq'=>$request['menu_seq']?$request['menu_seq']:1,
            'menu_level'=>$menu_level,
            'create_by'=>session('adminId')
        ];

        if($request['action']=='add'){
            $result=M('menu','sys_')->add($addData);
            if(!$result) return ['error'=>1,'msg'=>'添加失败'];
            else return ['error'=>0,'msg'=>'添加成功'];
        }else if($request['action']=='edit'){
            $addData['menu_id']=$request['menu_id'];
            $result=M('menu','sys_')->save($addData);
            if($result===false) return ['error'=>1,'msg'=>'保存失败'];
            else return ['error'=>0,'msg'=>'保存成功'];
        }
    }
















}