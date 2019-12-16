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

class MenuModel extends BaseModel
{

    /*
     * 用户的权限菜单
     *
     */
    public function soleMenuList($userId){
        $menu=M('menu','sys_')->where(['is_disabled'=>0]) ->select();

        $userFunction=D('user')->oneUserRoleFunctions($userId);
        if($userFunction['error']!=0) return $userFunction;
        $soleFunction=$userFunction['data']['functions'];

        $menuReturn=[];
        foreach($menu as $k=>$v){
            if(in_array($v['function_id'],$soleFunction)) array_push($menuReturn,$v);
        }
        if(empty($menuReturn)) return ['error'=>1,'msg'=>'用户没有菜单权限'];

        return ['error'=>0,'data'=>$menuReturn];
    }













}