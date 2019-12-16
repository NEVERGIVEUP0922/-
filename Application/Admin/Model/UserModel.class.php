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

class UserModel extends BaseModel
{

    /**
     * @desc 用户功能 (用户->部门->角色->功能)
     *
     */
    public function userFunctions($userId,$return_type=''){
        $userList=$this->baseList(M('user','sys_'),['uid'=>$userId]);
        if($userList['error']!=0) return ['error'=>1,'msg'=>'用户信息错误'];
        $user=$userList['data']['list'][0];

        $departments=$this->userDepartments($user['department_id']);
        if($departments['error']!=0) return ['error'=>1,'msg'=>'部门信息错误'];

        $departmentsId_arr=[$departments['data']['self']['id']];//所有部门信息
        if($departments['data']['son']){
            foreach($departments['data']['son'] as $k=>$v){
                $departmentsId_arr[]=$v['id'];
            }
        }

        $roleId=$this->baseList(M('department_role','sys_'),['department_id'=>['in',$departmentsId_arr]]);
        if($roleId['error']!=0) return ['error'=>1,'msg'=>'用户没有角色'];

        $roleId_arr=[];
        foreach($roleId['data']['list'] as $k=>$v){
            $roleId_arr[]=$v['role_id'];
        }
        $roleId_str=implode(',',$roleId_arr);

        $functions=M('function','sys_')->alias('f')
            ->join('left join sys_role_function rf on rf.function_id = f.function_id')
            ->where(['rf.role_id'=>['in',$roleId_arr],"rf.spread in(select max(spread) from sys_role_function where role_id in ($roleId_str) group by function_id)"])
            ->group('f.function_id')
            ->select();
        if(!$functions) return ['error'=>1,'msg'=>'没有功能'];
        if($return_type=='index_type'){
            return ['error'=>0,'data'=>['list'=>$functions]];
        }

        $userFunction=[];
        foreach($functions as $k=>$v){
            if($v['method_name']){
                $userFunction[$v['model_name']][$v['action_name']][$v['method_name']]['spread']=$v['spread'];
                $userFunction[$v['model_name']][$v['action_name']]['spread']=1;
                $userFunction[$v['model_name']]['spread']=1;
            }else if($v['action_name']){
                $userFunction[$v['model_name']][$v['action_name']]['spread']=$v['spread'];
                $userFunction[$v['model_name']]['spread']=1;
            }else if($v['model_name']){
                $userFunction[$v['model_name']]['spread']=$v['spread'];
            }
        }

        return ['error'=>0,'data'=>['function'=>$userFunction,'adminInfo'=>$user]];
    }

    /**
     * @desc 用户的部门信息和下属部门
     *
     */
    public function userDepartments($departmentId){
        $m=M('user_department','sys_');
        $list=$this->baseList($m,['id'=>$departmentId]);
        if($list['error']!=0) return $list;
        $data=$list['data']['list'][0];

        $all=$this->baseList($m,['lft'=>['gt',$data['lft']],'rht'=>['lt',$data['rht']]]);//下属部门
        $return=['error'=>0,
            'data'=>[
                'self'=>$data,
                'son'=>$all['data']['list']
            ]
            ];
        return $return;
    }

    /**
     * @desc 用户的下属信息
     *
     */
    public function userSlaveList($userId,$type=''){

        $userList=$this->baseList(M('user','sys_'),['uid'=>$userId]);
        if($userList['error']!=0) return ['error'=>1,'msg'=>'用户信息错误'];
        $user=$userList['data']['list'][0];
        $departments=$this->userDepartments($user['department_id']);
        if($departments['error']!=0) return ['error'=>1,'msg'=>'部门信息错误'];

        $departmentsId_arr=[];
        if($departments['data']['son']){
            foreach($departments['data']['son'] as $k=>$v){
                $departmentsId_arr[]=$v['id'];
            }
            $slaveList=$this->baseList(M('user','sys_'),['department_id'=>['in',$departmentsId_arr]]);
            if($slaveList['error']!=0) return ['error'=>1,'msg'=>'部门信息错误2'];
        }

        if( $type=='slaveId_arr' ){
            $slaveId_arr=[$userId];
            foreach($slaveList['data']['list'] as $k=>$v){
                $slaveId_arr[]=$v['uid'];
            }
            return ['error'=>0,'data'=>['list'=>$slaveId_arr]];
        }

        return $slaveList;
    }

    /**
     * @desc 用户的管理的下属的客户
     *
     */
    public function userSlaveCustomerList($userId){
        $slaveId_arr=$this->userSlaveList($userId,'slaveId_arr');
        if($slaveId_arr['error']!=0) return ['error'=>1,'msg'=>'个人信息错误'];
        $list=$slaveId_arr['data']['list'];

        $customer=$this->baseList(M('user'),['sys_uid'=>['in',$list]]);
        if($customer['error']!=0) return ['error'=>1,'msg'=>'没有顾客'];

        $customerId_arr=[];
        foreach($customer['data']['list'] as $k=>$v){
            $customerId_arr[]=$v['id'];
        }

        return ['error'=>0,'data'=>$customerId_arr];
    }

    /**
     * @desc 部门的角色信息
     *
     */
    public function departmentRole($request){
        $list=$this->baseList(M('department_role','sys_'));
        if($list['error']!=0) return ['error'=>1,'msg'=>'没有角色'];

        $role=$this->baseListType(M('role','sys_'),'*','role_id');

        $data=[];
        foreach($list['data']['list'] as $k=>$v){
            $v['role_name']=$role['data']['list'][$v['role_id']];
            $data[$v['department_id']][]=$v;
        }
        return ['error'=>0,'data'=>$data];
    }

    /**
     * @desc 系统用户列表
     *
     */
    public function adminList($where='',$page='',$pageSize='',$order='',$skip_power=false){
        $session=session();
        $method_name=MODULE_NAME;
        $key_method=strtolower($method_name);
        if($key_method=='admin'&&!$skip_power){
            $productPowers=(new UserModel())->departmentDataPower('admin',$session['adminInfo']['department_id'],$where,'uid');
            if($productPowers['error']!=0) return $productPowers;
            $where=$productPowers['data']['where'];
        }

        $bargain=M('user','sys_');
        $field=['password'];
        $list=$this->baseList($bargain,$where,$page,$pageSize,$order,$field,true);
        $data=$list['data']['list'];
        $departmentId_arr=[];
        foreach($data as $k=>$v){
            $departmentId_arr[]=$v['department_id'];
        }
        $departmentList=$this->baseListType(M('user_department','sys_'),$departmentId_arr,'id');//部门信息
        if($departmentList['error']==0){
            foreach($list['data']['list'] as $k=>$v){
                $list['data']['list'][$k]['departmentInfo']=$departmentList['data']['list'][$v['department_id']];
                if($v['product_category']){//业务被咨询的分类
                    foreach(explode(',',$v['product_category']) as $k2=>$v2){
                        if($k2){
                            $one_category_name=M('category')->field('cate_name')->where(['id'=>$v2])->find();
                            if($v2)$list['data']['list'][$k]['service_category'][]=$one_category_name['cate_name'];
                            else break;
                        }
                    }
                }
            }
        }
        return $list;
    }

    /*
     * 系统用户添加或编辑或删除
     *
     */
    public function adminAction($request){
        if($request['action']=='delete'){//删除后台管理员
            $delete_result=$this->adminDelete(['uid'=>$request['uid']]);
            if($delete_result['error']===0){
                $delete_request=[
                    'active'=>'delete',
                    'userId_arr'=>[$request['uid']]
                ];
                $delete_result=D('Admin/Customer','Controller')->updateAdminFirstString($delete_request);//删除拼音
            }
            return $delete_result;
        }

        foreach($request as $k=>$v){
            if(!is_array($v))$request[$k]=trim($v);
        }

        $hasNickName=M('user','sys_')->where(['nickname'=>$request['nickname'],'user_name'=>['neq',$request['user_name']]])->find();
        if($hasNickName) return ['error'=>1,'msg'=>'昵称已被使用'];

        $pattern='/^\w{5,}$/';
        if(!preg_match($pattern,$request['user_name'])) return ['error'=>1,'msg'=>'用户名大于等于5的字符'];
        if(isset($request['password'])&&!preg_match($pattern,$request['password'])) return ['error'=>1,'msg'=>'密码大于等于5的字符'];

        $department=M('user_department','sys_')->where(['id'=>$request['department_id']])->find();
        if(!$department) return ['error'=>1,'msg'=>'部门信息错误'];
        $femployee=M('admin','erp_')->where(['femplno'=>$request['femplno']])->find();
        if(!$femployee) return ['error'=>1,'msg'=>'erp管理员信息错误'];

        $request['FEmplName']=$femployee['femplname'];
        $request['FEmplNo']=$request['femplno'];

        if(is_array($request['product_category'])){//客服被咨询的产品分类
            $product_category_str=','.implode(',',$request['product_category']).',,';
            $category=$this->baseListType(M('category'),$request['product_category'],'id');
            foreach($request['product_category'] as $k=>$v){
                $lft=$category['data']['list'][$v]['lft'];
                $rht=$category['data']['list'][$v]['rht'];
                $oneSlave=M('category')->field('id')->where(['lft'=>['gt',$lft],'rht'=>['lt',$rht]])->select();

                if($oneSlave){
                    foreach($oneSlave as $k2=>$v2){
                        $product_category_str.=$v2['id'].',';
                    }
                }
            }
            $request['product_category']=$product_category_str;
        }

        $request['create_by']=session('adminId');
        if(isset($request['password'])){
            $request['password']=hash_string($request['password']);
        }

        $result=0;
        M('user','sys_')->startTrans();
        if($request['action']=='add'){
            if(isset($request['uid'])) unset($request['uid']);
            if($request['uid']=M('user','sys_')->add($request)) $result=1;
        }else if($request['action']=='edit'){
            if(isset($request['user_name'])) unset($request['user_name']);
            if(M('user','sys_')->field('user_name',true)->where(['uid'=>$request['uid']])->save($request)!==false) $result=1;
        }
        if($result){//更新拼音
            $update_request=[
                'userId_arr'=>[$request['uid']]
            ];
            $delete_result=D('Admin/Customer','Controller')->updateAdminFirstString($update_request);
            if($delete_result['error']===0) M('user','sys_')->commit();
            else M('user','sys_')->rollback();

            return $delete_result;
        }else{
            M('user','sys_')->rollback();
            return ['error'=>1,'msg'=>'failed'];
        }
    }

    /*
     * 系统用户删除
     *
     */
    public function adminDelete($request){
        $result=M('user','sys_')->where(['uid'=>$request['uid']])->delete();
        if($result) return ['error'=>0,'msg'=>'success'];
        else return ['error'=>1,'msg'=>'failed'];
    }

    /*
     * 系统用户部门价格等级
     *
     */
    public function adminRolePriceLevel($adminId_arr){
        $list=M('user','sys_')->alias('u')
            ->join('left join sys_user_department as d on u.department_id=d.id')
            ->where(['u.uid'=>['in',$adminId_arr]])->select();
        if(!$list) return ['error'=>1,'msg'=>'部门信息错误'];

        $returnData=[];
        foreach($list as $k=>$v){
            $returnData[$v['uid']]=$v;
        }

        return ['error'=>0,'data'=>['list'=>$returnData]];
    }


    /*
     * 系统用户部门树
     *
     */
    public function sysUserDepartmentTree(){
        $category=new CategoryModel();
        $result=$category->productCategoryInfinite(M('user_department','sys_'));
        return $result;
    }

    /*
     * 系统部门添加子部门
     *
     */
    public function sysUserDepartmentAdd($request,$type='son'){
        $id=$request['id'];
        $cate_name=$request['department_name'];
        if(!in_array($request['department_level'],[1,2,3])) return ['error'=>1,'msg'=>'部门等级信息错误'];
        $category=new CategoryModel();
        $result=$category->addCategory($id,$cate_name,$type,$table='user_department',$profix='sys_','department_name',$request['department_level']);
        return $result;
    }

    /*
     * 部门删除
     *
     */
    public function sysUserDepartmentDelete($request){
        $id=$request['id'];
        $category=new CategoryModel();
        $result=$category->deleteCategory($id,'user_department','sys_','user','sys_','department_id');
        return $result;
    }

    /*
     * 部门编辑
     *
     */
    public function sysUserDepartmentEdit($request){
        if(!in_array($request['department_level'],[1,2,3])) return ['error'=>1,'msg'=>'部门等级信息错误'];
        $data=[
           'id'=>$request['id'],
            'department_name'=>$request['department_name'],
            'department_level'=>$request['department_level'],
            'sort'=>$request['sort'],
        ];
        $result=M('user_department','sys_')->save($data);
        if($result===false) return ['error'=>1,'msg'=>'编辑失败'];
        else return ['error'=>0,'msg'=>'编辑成功'];
    }

//    /*
//     * 给用户添加下属部门
//     *
//     */
//    public function userAddDepartment($request){
//        $one=M('user','sys_')->where(['uid'=>$request['sys_uid']])->find();
//        if(!$one)return ['error'=>1,'msg'=>'系统用户信息错误'];
//        $count=M('user_department','sys_')->where(['id'=>['in',$request['departmentId_arr']]])->count();
//        if($count!=count($request['departmentId_arr'])) return ['error'=>1,'msg'=>'部门信息错误'];
//        $data=[];
//        foreach($request['departmentId_arr'] as $k=>$v){
//            $data[]=[
//                    'sys_uid'=>$request['sys_uid'],
//                    'sys_department_id'=>$v,
//                ];
//        }
//        $result=M('user_son_department','sys_')->addAll($data);
//        if($result===false) return ['error'=>1,'msg'=>'操作失败'];
//        else return ['error'=>0,'msg'=>'操作成功'];
//    }

//    /*
//     * 取出用户的所有下属部门
//     *
//     */
//    public function userSonDepartmentList($uid){
//        $list=$this->baseList(M('user_son_department','sys_'),['sys_uid'=>$uid]);
//        return $list;
//    }
//
//    /*
//     * 取出用户的所有下属
//     *
//     */
//    public function sysUserSonList($uid){
//        $list=M('user_son_department','sys_')->where(['sys_uid'=>$uid])->select();
//        if(!$list) return ['error'=>1,'msg'=>'用户没有下属'];
//
//        $departmentId_arr=[];
//        foreach($list as $k=>$v){ $departmentId_arr[]=$v['sys_department_id']; }
//        $departmentInfo=$this->baseListType(M('user_department','sys_'),$departmentId_arr,'id');
//        $where='';
//        foreach($departmentInfo['data']['list'] as $k=>$v){ $where.="(lft >=$v[lft] and rht <= $v[rht]) or "; }
//        $where=substr($where,0,-3);
//        $all_department=M('user_department','sys_')->where($where)->group('id')->select();//所用子部门
//
//        $departmentId_list=[];
//        foreach($all_department as $k=>$v){ $departmentId_list[]=$v['id']; }
//
//        $adminList=$this->adminList(['department_id'=>['in',$departmentId_list]]);
//        return $adminList;
//    }

    /**
     * @desc $desc 管理员菜单
     *
     */
    public function adminMenu(){
        //取用户的权限spread
        $session=session();
        $where=[];
        $where['is_disabled']=0;

        $userFunctions=$this->userFunctions(session('adminId'),'index_type');
        if($userFunctions['error']!=0) return ['error'=>1,'msg'=>'用户功能错误'];
        $functionId_arr=[];
        foreach($userFunctions['data']['list'] as $k=>$v){
            $functionId_arr[]=$v['function_id'];
        }
        $where['function_id']=['in',$functionId_arr];
        $where=[$where,'menu_level'=>1,'_logic'=>'or'];

        $field='menu_id,menu_fid,menu_level,menu_name as title,function_id,icon,menu_seq';
        $list=$this->baseList(M('menu','sys_'),$where,'','','',$field);
        if($list['error']!=0) return $list;
        $data=$list['data']['list'];

        $functionList=$this->baseListType(M('function','sys_'),'*','function_id');
        if($functionList['error']!=0) return $functionList;
        $functionList_type=$functionList['data']['list'];

        $level=[];
        foreach($data as $k=>$v){
            $v['action_name']=$functionList_type[$v['function_id']]['action_name'];
            $v['method_name']=$functionList_type[$v['function_id']]['method_name'];
            if($v['menu_level']==1) $v['spread']=true;
            if($v['menu_level']==2){
                if(!$v['action_name']||!$v['method_name']) $v['href']=U("index/error");//错误页面
                else $v['href']=U("$v[action_name]/$v[method_name]");
            }
            $level[$v['menu_level']][$v['menu_fid']][]=$v;
        }

        foreach($level[1][0] as $k=>$v){
            if($level[2][$v['menu_id']]){
                $level[1][0][$k]['children']=array_multisort__(array_values($level[2][$v['menu_id']]),'menu_seq');
            }else{
                unset($level[1][0][$k]);
            }
        }
        $menu=array_multisort__(array_values($level[1][0]),'menu_seq');

        if($level[1][0]) return ['error'=>0,'data'=>$menu];
        else return ['error'=>1,'msg'=>'菜单信息错误'];
    }

    /**
     * @desc add管理员消息
     *
        $oneData=[
            'type'=>'orderDelete',
            'sign'=>1,//订单编号
            'msg'=>'订单删除'
        ];
     *
     */
    public function adminNoteAdd($oneData,$sys_uid){
        if($oneData){
            $key=md5('adminNote');
            $time=time().mt_rand(10000,99999);
            $value=S($key);
            $value[$key][$sys_uid][]=$oneData;
            S($key,$value);
        }
    }

    /**
     * @desc 管理员消息
     *
    */
    public function adminNoteList($sys_uId_arr){
        $key=md5('adminNote');
        $list=S($key);
        $return=[];
        foreach($list[$key] as $k=>$v){
//            if(in_array($k,$sys_uId_arr)){
                $return[$k]=$v;
//            }
        }
        return $return;
    }

    /**
     * @desc 管理员消息删除
     *
     */
    public function adminNoteDelete($sys_uid,$index_arr){
        $key=md5('adminNote');
        $list=S($key);
        foreach($list[$key][$sys_uid] as $k=>$v){
            if(in_array($k,$index_arr)){
                unset($list[$key][$sys_uid][$k]);
            }
        }
        S($key,$list);
    }

    /**
     * @desc 部门添加,删除角色
     *
     */
    public function departmentRoleAction($request){
        $rules=[
            ['action',['add','edit','delete','all'],'部门id没有',2,'in'],
            ['department_id','require','部门id没有'],
        ];

        $message='';
        $error=0;
        $m=M('department_role','sys_');
        if(!$m->validate($rules)->create($request)){
            $message=$m->getError();
            $error=1;
        }
        if($error) return ['error'=>$error,'msg'=>$message];

        $department=M('user_department','sys_')->where(['id'=>$request['department_id']])->find();
        if(!$department) return ['error'=>1,'msg'=>'部门信息错误'];
        $role=M('role','sys_')->field('count(role_id) as count')->where(['role_id'=>['in',$request['roleId_arr']]])->find();
        if($role['count']==0||$role['count']!=count($request['roleId_arr'])) return ['error'=>1,'msg'=>'角色信息错误'];

        if($request['action']=='delete'){
            $deleteAction=$m->where(['department_id'=>$request['department_id'],'role_id'=>['in',$request['roleId_arr']]])->delete();
            if(!$deleteAction){
                return ['error'=>1,'msg'=>'删除失败'];
            }else{
                return ['error'=>0,'msg'=>'删除成功'];
            }
        }

        $data=[];
        foreach($request['roleId_arr'] as $k=>$v){
            $data[]=[
                'department_id'=>$request['department_id'],
                'role_id'=>$v,
            ];
        }

        if($request['action']=='all'){
            $m->startTrans();
            $deleteAction=$m->where(['department_id'=>$request['department_id']])->delete();
            $result=$m->addAll($data);
            if($deleteAction===false||!$result){
                $m->rollback();
                return ['error'=>1,'msg'=>'保存失败'];
            }else{
                $m->commit();
                return ['error'=>0,'msg'=>'保存成功'];
            }
        }

        $m->startTrans();
        $delete=$m->where(['department_id'=>$request['department_id'],'role_id'=>['in',$request['roleId_arr']]])->delete();
        $result=$m->addAll($data);
        if($delete===false||!$result){
            $m->rollback();
            return ['error'=>1,'msg'=>'添加失败'];
        }else{
            $m->commit();
            return ['error'=>0,'msg'=>'添加成功'];
        }
    }

    /**
     * @desc 前台qq列表
     *
     */
    public function qqList($user_id,$departmentId){
        $return=[];
        if(!$user_id){//未登录
            $list=$this->userDepartments($departmentId);
            if($list['error']!=0) return ['error'=>1,'msg'=>'部门信息错误'];

            $departmentId_arr=[];
            foreach($list['data']['son'] as $k=>$v){
                $departmentId_arr[]=$v['id'];
            }
            $user=$this->baseList(M('user','sys_'),['department_id'=>['in',$departmentId_arr],'is_customer_service'=>1],'','','','uid,nickname,qq,wechat,mobile');
            $return['list']=$user['data']['list'];
        }else{//登录
            $session=session();
            if($session['userType']==20){//子帐号
                $userInfo=M('user_son')->field('p_id')->where(['user_id'=>$user_id])->find();
                $user_id=$userInfo['p_id'];
            }
            $one=M('user')->alias('u')->field('su.uid,su.nickname,su.qq,su.wechat,su.mobile')->join('left join sys_user su on su.uid=u.sys_uid')->where(['u.id'=>$user_id])->find();
            if(!$one) return ['error'=>1,'msg'=>'个人信息错误'];
            $return['list']=[$one];
        }
        return ['error'=>0,'data'=>$return];
    }

    /**
     * @desc 系统用户执行数据权限规则
     *
     */
    public function departmentDataPower($power_type,$adminDepartmentId,$where,$key){
        $powerDepartment=$this->powerDepartmentId($power_type);
        if($powerDepartment['error']!=0) return ['error'=>1,'msg'=>'权限规则配置错误'];
        $departmentId=$powerDepartment['data'];

        $departmentPowers=(new UserModel())->userDepartments($departmentId);
        if($departmentPowers['error']!=0||!$departmentPowers['data']['son']) return $departmentPowers;
        $power=0;
        foreach($departmentPowers['data']['son'] as $k=>$v){
            if($adminDepartmentId==$v['id']){
                $power=1;
                break;
            }
        }

        if($power){
            $must_where=[];
            $slaveList=$this->userSlaveList(session('adminId'),'slaveId_arr');
            if($slaveList['error']!=0) return ['error'=>1,'msg'=>'产品负责人信息错误'];
            if($key=='person_liable'){
                $person_liable_str=implode("','",$slaveList['data']['list']);
                $person_liable_str="'".$person_liable_str."'";
                $must_where[]="p_sign in (select p_sign from dx_product_fitemno where person_liable in ($person_liable_str))";
                $fitemno_where="fitemno in (select fitemno from dx_product_fitemno where person_liable in ($person_liable_str))";
                $where=$where?[$where,$must_where]:$must_where;
            }else{
                $must_where=[$key=>['in',$slaveList['data']['list']]];

                $where=$where?[$where,$must_where]:$must_where;
            }
            return ['error'=>0,'data'=>['where'=>$where,'must_where'=>$must_where,'fitemno_where'=>$fitemno_where]];
        }else{
            return ['error'=>0,'data'=>['where'=>$where]];
        }
    }

    /**
     * @desc 客户执行数据权限规则
     *
     */
    public function adminSaleCustomerPower($where,$key){
        $userIds=$this->baseList(M('user'),$where,'','','',$key);
        if($userIds['error']!=0) return ['error'=>1,'msg'=>'权限错误'];
        $userId_arr=[];
        foreach($userIds['data']['list'] as $k=>$v){
            $userId_arr[]=$v['id'];
        }
        $son=M('user_son')->field('user_id')->where(['p_id'=>['in',$userId_arr]])->select();
        if($son){//子用户
            foreach($son as $k=>$v){ $userId_arr[]=$v['user_id']; }
        }
        return ['error'=>0,'data'=>$userId_arr];
    }

    /**
     * @desc 用户执行数据权限规则的部门id
     *
     */
    public function powerDepartmentId($power_type){
        $dataPower=C('DATA_POWER');
        $departmentId=$dataPower[$power_type];
        return ['error'=>0,'data'=>$departmentId];
    }

    /**
     * @desc 管理员名字搜素条件拼接
     *
     */
    public function sysNameToWhere($name){
        $where=[
            'FEmplName'=>['like',"%$name%"],
            'fullname'=>['like',"%$name%"],
            '_logic'=>'or'
        ];
        $list=M('user','sys_')->field('uid')->where($where)->select();
        $data=[];
        if($list){
            foreach($list as $k=>$v){
                $data[]=$v['uid'];
            }
        }
        return ['error'=>0,'data'=>$data];
    }

    /**
     * @desc 客户名字搜素条件拼接
     *
     */
    public function customerNameToWhere($name){
        $where=[
            'u.user_name'=>['like',"%$name%"],
            'u.nick_name'=>['like',"%$name%"],
            'u.fcustjc'=>['like',"%$name%"],
            'c.company_name'=>['like',"%$name%"],
            '_logic'=>'or'
        ];
        $list=M('user')->alias('u')->join('left join dx_user_company c on u.id=c.user_id')->field('u.id')->where($where)->select();
        $data=[];
        if($list){
            foreach($list as $k=>$v){
                $data[]=$v['id'];
            }
        }
        return ['error'=>0,'data'=>$data];
    }


    /*
     *
     * 检查用户是否存在
     */
    public function checkUserExists( $userId )
    {
        return M('','sys_user')->where(['uid'=>$userId])->count() > 0 ? true:false;
    }

    //Event //取出数据
    public function toOrderInfo($where='',$field='',$limit=[0,10]){
        $page=(int)(($limit[0]-1)*$limit[1]);
        $pageSize=(int)$limit[1];
        $list=M('user','sys_')
            ->field($field)
            ->where($where)
            ->limit($page,$pageSize)
            ->select();
        $this->list=$list;
        return $list;
    }





}