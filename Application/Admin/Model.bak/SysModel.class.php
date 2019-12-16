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

class SysModel extends BaseModel
{

    /*
     * $desc 系统角色列表
     *
     */
    public function roleList($where='',$page='',$pageSize='',$order=''){
        $bargain=M('role','sys_');
        $list=$this->baseList($bargain,$where,$page,$pageSize,$order);
        return $list;
    }

    /**
     * @desc 系统角色的添加编辑和删除
     *
     */
    public function sysRoleAction($request){
        if($request['action']=='delete'){
            $deleteResult=M('role','sys_')->where(['role_id'=>$request['role_id']])->delete();
            if($deleteResult===false) return ['error'=>1,'msg'=>'删除失败'];
            else return ['error'=>0,'msg'=>'删除成功'];
        }

        $rules=[
            ['action',['add','edit','delete'],'添加编辑和删除',1,'in'],
            ['role_name','require','角色名称错误',1],
            ['role_desc','require','角色描述错误',1],
            ['role_seq','require','列表排序错误',2],
        ];

        $roelM=M('role','sys_');
        if(!$roelM->validate($rules)->create($request)) return ['error'=>1,'msg'=>$roelM->getError()];

        $request['create_by']=session('adminId');
        $field='role_name,role_desc,role_seq,create_by';

        if($request['action']=='add'){
            $result=$roelM->field($field)->add($request);
            if(!$result) return ['error'=>1,'msg'=>'添加失败'];
            else return ['error'=>0,'msg'=>'添加成功'];
        }else if($request['action']=='edit'){
            $field.=',role_id';
            $result=$roelM->field($field)->save($request);
            if($result===false) return ['error'=>1,'msg'=>'编辑失败'];
            else return ['error'=>0,'msg'=>'编辑成功'];
        }
    }

    /**
     * @desc 给系统角色添加编辑或删除系统功能
     *
        $request=[
            'role_id'=>1,
            'functionId_arr'=>[
                1,12,13,14
            ],
            'spread'=>[//2有全部，1有部分，0没有
                1,1,2,2
            ]
     *      ,action='delete'//删除
        ];
     *
     */
    public function roleFunctionAction($request){
        $rules=[
            ['role_id','require','角色信息错误',1],
        ];

        $m=M('role_function','sys_');
        if(!$m->validate($rules)->create($request)){
            $m->getError();
            return ['error'=>1,'msg'=>$m->getError()];
        }

        $m->startTrans();
        if($request['action']=='delete'||!isset($request['functionId_arr'])){
            $error=0;
            if(!isset($request['functionId_arr'])){
                $deleteResult=M('role_function','sys_')->where(['role_id'=>$request['role_id']])->delete();
                if($deleteResult===false) $error=1;
            }else{
                foreach($request['functionId_arr'] as $k=>$v){
                    $oneResult=M('role_function','sys_')->where(['role_id'=>$request['role_id'],'function_id'=>$v])->delete();
                    if($oneResult!=1){
                        $error=1;break;
                    }
                }
            }
            if($error){
                $m->rollback();
                return ['error'=>1,'msg'=>'删除失败'];
            } else {
                $m->commit();
                return ['error'=>0,'msg'=>'删除成功'];
            }
        }

        $roleList=$this->baseList(M('role','sys_'),['role_id'=>$request['role_id']]);
        if($roleList['error']!=0) return ['error'=>1,'msg'=>'角色信息不对'];

        $where=['function_id'=>['in',$request['functionId_arr']]];
        $list=$this->baseList(M('function','sys_'),$where);
        if($list['error']!=0||(count($request['functionId_arr'])!=$list['data']['count'])) return ['error'=>1,'msg'=>'功能信息错误2'];
        $level2_functionId=[];
        foreach($list['data']['list'] as $k=>$v){
            $level2_functionId[]=$v['function_fid'];
        }
        $request['functionId_arr']=array_merge($request['functionId_arr'],array_unique($level2_functionId));

        foreach($request['functionId_arr'] as $k=>$v){
            $data[]=[
                'role_id'=>$request['role_id'],
                'function_id'=>$v,
                'spread'=>1,
            ];
        }
        $data[]=[
            'role_id'=>$request['role_id'],
            'function_id'=>1,
            'spread'=>1,
        ];

        $deleteResult=$m->where(['role_id'=>$request['role_id']])->delete();
        $saveResult=$m->addAll($data);
        if(!$saveResult){
            $m->rollback();
            return ['error'=>1,'msg'=>'功能添加失败'];
        }else{
            $m->commit();
            return ['error'=>0,'msg'=>'功能添加成功'];
        }
    }

    /**
     * @desc 功能树
     *
     */
    public function functionShowTree($roleId){
        $list=$this->baseList(M('function_show','sys_'));
        if($list['error']!=0) return ['error'=>1,'msg'=>'数据错误'];

        $function_list=$this->baseList(M('function','sys_'),['tree_show_id'=>['gt',0]]);
        if($function_list['error']!=0) return ['error'=>1,'msg'=>'数据错误2'];

        $functionIds=M('role_function','sys_')->where(['role_id'=>$roleId])->select();
        $functionId_arr=[];
        if($functionIds){
            foreach($functionIds as $k=>$v){
                $functionId_arr[$v['function_id']]=$v['spread'];
            }
        }

        $level2_son=[];
        foreach($function_list['data']['list'] as $k=>$v){
            $v['spread']=$functionId_arr[$v['function_id']];
            $level2_son[$v['tree_show_id']][]=$v;
        }

        $tree=$one=$level2=[];

        foreach($list['data']['list'] as $k=>$v){
            if($v['fid']!=0){
                $v['children']=$level2_son[$v['id']];
            }
            $tree[$v['fid']][]=$v;
        }
        foreach($tree[0] as $k=>$v){
            $tree[0][$k]['children']=$tree[$v['id']];
        }

        return ['error'=>0,'data'=>['list'=>$tree[0]]];
    }

    /**
     * @desc $desc 系统功能列表
     *
     */
    public function sysFunction($where='',$page='',$pageSize='',$order='model_name asc,action_name asc,method_name asc'){
        $bargain=M('function','sys_');
        $list=$this->baseList($bargain,$where,$page,$pageSize,$order);
        return $list;
    }

    /**
     * @desc 后台系统功能初始化数据库
     *
     */
    public function sysFunctinInit(){
        $adminId=session('adminId');
        $medel_name='admin';
        $result=$this->adminControllerFunction();
        if($result['error']!=0) return $result;
        $list=$result['data']['list'];
        $addData=[];

        $init1=$init2=$init3=1;
        $function=M('function','sys_');
        $function->startTrans();

        $clear=$function->execute('truncate sys_function');
        if($clear!==false){
            $level1=[
                'model_name'=>$medel_name,
                'function_level'=>1,
                'function_name'=>'后台管理',
                'function_fid'=>0,
                'create_by'=>$adminId
            ];
            $level1_id=$function->add($level1);
            foreach($list as $k=>$v){
                if(!$level1_id){
                    $init2=0;
                    break;
                }
                $level2=[
                    'model_name'=>$medel_name,
                    'action_name'=>$k,
                    'function_level'=>2,
                    'function_fid'=>$level1_id,
                    'create_by'=>$adminId
                ];
                $level2_id=$function->add($level2);
                if(!$level2_id){
                    $init3=0;
                    break;
                }
                $level3=[];
                foreach($v as $k2=>$v2){
                    $level3[]=[
                        'model_name'=>$medel_name,
                        'action_name'=>$k,
                        'method_name'=>$k2,
                        'function_level'=>3,
                        'function_name'=>$v2,
                        'function_desc'=>$v2,
                        'function_fid'=>$level2_id,
                        'create_by'=>$adminId
                    ];
                }
                $init3=$function->addAll($level3);
            }
        }

        if($clear!==false&&$init1&&$init2&&$init3){
            $function->commit();
            return ['error'=>0,'msg'=>'初始化成功'];
        }else{
            $function->rollback();
            return ['error'=>1,'msg'=>'初始化失败'];
        }
    }

    /**
     * @desc 后台控制器的方法
     *
     */
    public function adminControllerFunction(){
        $controllerDir=APP_PATH.'/Admin/Controller';
        $controllerFile=scandir($controllerDir);
        $function=[];
        foreach($controllerFile as $k=>$v){
            if($k<2) continue;
            list($oneController)=explode('.',$v);
            $model=substr($oneController,0,-10);
            $one=$this->paseDesc("\\Admin\Controller\\".$oneController);
            if($one['error']!=0) return $one;
            $function[$model]=$one['data']['list'];
        }
        return ['error'=>0,'data'=>['list'=>$function]];
    }

    /**
     * @desc 解析desc
     *
     */
    public function paseDesc($class_name){
        $return=[];

        $reflection = new \ReflectionClass ( $class_name );
        //通过反射获取类的注释
        $doc = $reflection->getDocComment ();
        //解析类的注释头
        $parase_result =  \DocParserFactory::getInstance()->parse ( $doc );
        $class_metadata = $parase_result;

        //获取类中的方法，设置获取public,protected类型方法
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC + \ReflectionMethod::IS_PROTECTED + \ReflectionMethod::IS_PRIVATE);
        //遍历所有的方法
        foreach ($methods as $method) {
            //获取方法的注释
            $doc = $method->getDocComment();
            $name='';
            preg_match('/\@desc\s(.+)/',$doc,$name);
            if($name[1]) $return[$method->name] = $name[1];
        }
        if(!$return) ['error'=>1,'msg'=>'获取desc失败'];
        return ['error'=>0,'data'=>['list'=>$return]];
    }




    public function getUserUpDepartment( $user_id )
    {
        $dpModel = M('','sys_user_department');
        $userDp = M('','sys_user')->field('department_id')->where( ['uid'=>$user_id] )->find();
        if( !$userDp ){
            return ['error'=>1, '用户不存在'];
        }
        $dpInfo = $dpModel->where(['id'=>$userDp['department_id']])->find();
        $where['lft'] = ['lt', $dpInfo['lft']];
        $where['rht'] = ['gt', $dpInfo['rht']];
        $dpList = $dpModel->field('id')->where( $where )->select();
        if( empty( $dpList ) ){
            return ['error'=>0, 'data'=>[]];
        }
        $idList = array_column($dpList, 'id');
        return ['error'=>0, 'data'=>$idList ];
    }









}