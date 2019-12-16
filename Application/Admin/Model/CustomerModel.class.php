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

use EES\System\Redis;
use Think\Model;

class CustomerModel extends BaseModel
{

    /*
     * 客户列表
     *
     */
    public function customerList($where='',$page='',$pageSize='',$order=''){
        $method_name=MODULE_NAME;
        $key_method=strtolower($method_name);
        if($key_method=='admin'){
            $session=session();
            $productPowers=(new UserModel())->departmentDataPower('order',$session['adminInfo']['department_id'],$where,'sys_uid');
            if($productPowers['error']!=0) return $productPowers;
            $where=$productPowers['data']['where'];
        }

        $bargain=M('user');
        $list=$this->baseList($bargain,$where,$page,$pageSize,$order,['user_pass'],true);
        if($list['error']!=0) return $list;
        $data=$list['data']['list'];
        $userId_arr=$sysUid_arr=$departmentId_arr=[];
        foreach($data as $k=>$v){
            $userId_arr[]=$v['id'];
            $sysUid_arr[]=$v['sys_uid'];
        }
        $listCompany=$this->baseListType(M('user_company'),$userId_arr,'user_id');
        $listPersonal=$this->baseListType(M('user_normal'),$userId_arr,'user_id');
        $sysUserList=$this->baseListType(M('user','sys_'),$sysUid_arr,'uid',['password'],true);
        $sonParent=$this->sonGetFather($userId_arr);//母帐号

        if($sysUserList['error']==0){
            foreach($sysUserList['data']['list'] as $k=>$v){
                $departmentId_arr[]=$v['department_id'];
            }
            $departmentId_arr=array_unique($departmentId_arr);
        }
//        if($listCompany['error']==0){
            if($departmentId_arr) $departmentList=$this->baseListType(M('user_department','sys_'),$departmentId_arr,'id');
            foreach($list['data']['list'] as $k=>$v){
                $list['data']['list'][$k]['company']=$listCompany['data']['list'][$v['id']];
                $list['data']['list'][$k]['normal']=$listPersonal['data']['list'][$v['id']];
                $list['data']['list'][$k]['sale_name']=$sysUserList['data']['list'][$v['sys_uid']]['fullname'];
                $oneDepartmentId=$sysUserList['data']['list'][$v['sys_uid']]['department_id'];
                $list['data']['list'][$k]['department_name']=$departmentList['data']['list'][$oneDepartmentId]['department_name'];

                if(isset($sonParent['data']['list'][$v['id']])){
                    $list['data']['list'][$k]['company']=[
                        'company_name'=>$sonParent['data']['list'][$v['id']]['company_name'],
                        'company_address'=>$sonParent['data']['list'][$v['id']]['company_address'],
                        'company_phone_num'=>$sonParent['data']['list'][$v['id']]['company_phone_num'],
                        'company_user_name'=>$sonParent['data']['list'][$v['id']]['company_user_name'],
                    ];
                    $list['data']['list'][$k]['sale_name']=$sonParent['data']['list'][$v['id']]['salename'];
                }
            }
//        }
        return $list;
    }

    /*
     * 客户列表编辑
     *
     */
    public function customerAction($request){
        $user=M('user')->where(['id'=>$request['id']])->find();
        if(!$user) return ['error'=>1,'msg'=>'客户信息错误'];
        $custUser = M('user')->where(['fcustno'=>$request['fcustno']])->find();
        if( $custUser ) return ['error'=>1,'msg'=>'该ERP客户已有绑定商城账号!请先进行解绑操作后再重试'];
        $erp_customer=M('customer','erp_')->where(['fcustno'=>$request['fcustno']])->find();
        if(!$erp_customer) return ['error'=>1,'msg'=>'erp客户信息错误'];
        $sysUser=M('user','sys_')->where(['FEmplNo'=>$erp_customer['fsale']])->find();
        if(!$sysUser) return ['error'=>1,'msg'=>'erp业务员信息没有同步到商城'];
        $data=[
            'id'=>$request['id'],
            'sys_uid'=>$sysUser['uid'],
            'fcustno'=>$request['fcustno'],
            'fcustjc'=>$erp_customer['fcustjc'],
        ];
        $result = M('user')->save($data);
        //更换账号绑定ERP客户  同步更新账号账期信息
        $this->flushUserAccount( $user['id'], $request['fcustno'] );
        if($result===false) return ['error'=>1,'msg'=>'更新失败'];
        else return ['error'=>0,'msg'=>'更新成功'];
    }

    //更新客户账期信息
    protected function flushUserAccount( $user_id, $fcustno )
    {
        //删除原来客户的账期
        M('user_account')->where(['id'=>$user_id])->delete();
        M('','erp_accounts')->where( ['user_id'=>$user_id] )->delete();
        //更新客户账期信息
        $key = 'syncCustCreditList';
        $redis = Redis::getInstance();
        $redis->sAdd( $key, $fcustno);
    }

    /**
     *
     *  @desc 母账号,子账号全部关联的账号
     */
    public function companyAccountAll($user_id){
        $user=$this->baseList(M('user'),['id'=>$user_id]);
        if($user['error']!=0) return ['error'=>1,'msg'=>'客户信息错误'];

        if($user['data']['list'][0]['user_type']==1){
            $company_users_id=[$user_id];
        }else{
            if($user['data']['list'][0]['user_type']==20){
                $user_sons=M('user_son')->where(['user_id'=>$user_id,'is_delete'=>0])->find();
                $parentId=$user_sons['p_id'];
            }else if($user['data']['list'][0]['user_type']==2){
                $parentId=$user_id;
            }

            $company_users_id=[$parentId];
            $user_sons=M('user_son')->where(['p_id'=>$parentId,'is_delete'=>0])->select();
            if($user_sons){
                foreach($user_sons as $k=>$v){
                    $company_users_id[]=$v['user_id'];
                }
            }
        }
        return ['error'=>0,'data'=>$company_users_id];
    }

    /**
     *
     *  @desc 客户企业信息更改
     */
    public function companyInfoAction($request){
        if(!isset($request['user_id'])||!$request['user_id']) return ['error'=>1,'msg'=>'参数错误'];
        $where=['user_id'=>$request['user_id']];
        M('user_company')->startTrans();
        $result=M('user_company')->field('company_name')->where($where)->save($request);

        $pinyin=[
            'customerId_arr'=>[$request['user_id']]
        ];
        $pinyin_result=$this->updateCompanyNameFirstString($pinyin);//更新拼音

        if($pinyin_result!==0){
            M('user_company')->commit();
            return $pinyin_result;
        }else{
            M('user_company')->rollback();
        }

        if($result===false) return ['error'=>1,'msg'=>'false'];
        else return ['error'=>0,'msg'=>'success'];
    }

    /**
     *
     *  @desc 客户需求列表
     */
    public function releaseList($where,$page,$pageSize,$order){
        $must_where=[];
        $method_name=MODULE_NAME;
        $key_method=strtolower($method_name);
        $session=session();
        if($key_method=='admin'){
            $userM=new UserModel();
            //业务部门
            $productPowers=$userM->departmentDataPower('order',$session['adminInfo']['department_id'],$where,'sys_uid');
            if($productPowers['error']!=0) return $productPowers;
            if(isset($productPowers['data']['must_where'])){
                $customers=$userM->adminSaleCustomerPower($productPowers['data']['must_where'],'id');
                if($customers['error']!=0) return $customers;
//                $must_where['user_id']=['in',$customers['data']];
                $must_where=[
                    'user_id'=>['in',$customers['data']],
                    'sale_id'=>$productPowers['data']['must_where']['sys_uid'],
                    '_logic'=>'or'
                   ];
            }
        }
        if($where){
            if($must_where){
                $where=[$where,$must_where];
            }
        }else if($must_where){
            $where=$must_where;
        }

        $list=$this->baseList(M('release'),$where,$page,$pageSize,$order);
        if($list['error']==0){
            $userId_arr=[];
            foreach($list['data']['list'] as $k=>$v){
                if($v['user_id']) $userId_arr[]=$v['user_id'];
            }
            $userId_arr=array_unique($userId_arr);
            if($userId_arr){
                $users=$this->baseListType(M('user'),$userId_arr,'id','id,user_name,fcustjc,nick_name,sys_uid');
                if($users['error']==0){
                    foreach($list['data']['list'] as $k=>$v){
                        $list['data']['list'][$k]['user_name']=($users['data']['list'][$v['user_id']]['fcustjc']?:$users['data']['list'][$v['user_id']]['user_name'])?:'路人';
                        if($list['data']['list'][$k]['user_id']){//业务员
                            $sys_uid=$users['data']['list'][$v['user_id']]['sys_uid'];
                        }else if($users['data']['list'][$v['sale_id']]){
                            $sys_uid=$users['data']['list'][$v['sale_id']];
                        }
                        if($sys_uid){
                            $one=M('user','sys_')->field('nickname')->where(['uid'=>$sys_uid])->find();
                            $list['data']['list'][$k]['sale_name']=$one['nickname'];
                        }
                    }
                }
            }
        }
        return $list;
    }

    /**
     *
     *  @desc 客户需求处理
     */
    public function releaseHandle($request){
        $result=M('release')->field('handle_status')->where(['id'=>$request['id']])->save(['handle_status'=>1]);
        if($result===false) return ['error'=>1,'msg'=>'failed'];
        else  return ['error'=>0,'msg'=>'success'];
    }

    /**
     *
     *  @desc 更新顾客拼音检索
     * @param       $request['customerId_arr']=[
                        1543,1542
                    ];
     *
     */
    public function updateCompanyNameFirstString($request){
        $active='';
        $where=[];

        if($active!='all' && isset($request['customerId_arr'])&&$request['customerId_arr']&&is_array($request['customerId_arr'])){
            $where['u.id']=['in',$request['customerId_arr']];
            $active='update';
        }

        if($request['active']=='delete'){
            $delete_result=M('user_pinyin')->where($where)->delete();

            if($delete_result===false) return ['error'=>1,'msg'=>'failed'];
            else return ['error'=>0,'msg'=>'success'];
        }

        $list=M('user')->alias('u')->where($where)->field('uc.company_name,u.user_name,u.fcustno,u.nick_name,u.fcustjc,u.id as user_id')->join('left join dx_user_company uc on uc.user_id=u.id')->select();
        if(!$list) return ['error'=>1,'msg'=>'客户信息错误'];

        $save_data=[];
        if($active=='all') M('user_pinyin')->query('truncate dx_user_pinyin');

        foreach($list as $k=>$v){
            $one_save=[];
            foreach($v as $k2=>$v2){
                if($k2=='id'){
                    $one_save[$k2]=$v2;
                    continue;
                }
                if($v2){
                    $v2=trim($v2);
                    $one_one=pinyin($v2);
                    if($one_one['error']===0){
                        $one_save[$k2]=$one_one['data'];
                    }
                }else{
                    $one_save[$k2]=$v2;
                }
            }
            if($v['company_name']) $one_save['return']=$v['company_name'];
            else if($v['nick_name'])$one_save['return']=$v['nick_name'];
            else $one_save['return']=$v['user_name'];
            $save_data[]=$one_save;

            if($active=='update'){
                if(M('user_pinyin')->where(['user_id'=>$one_save['user_id']])->find()){
                    $one_result=M('user_pinyin')->where(['user_id'=>$one_save['user_id']])->save($one_save);
                }else{
                    $one_result=M('user_pinyin')->add($one_save);
                }
                if($one_result===false) $result=false;
            }
        }

        if($active=='all') $result=M('user_pinyin')->addAll($save_data);

        if($result===false) return ['error'=>1,'msg'=>'failed'];
        else return ['error'=>0,'msg'=>'success'];
    }

    /**
     * @desc 子帐号获取母帐号信息
     * @param son_arr  array
     */
    public function sonGetFather($son_arr){
        if(!is_array($son_arr)||!$son_arr) return ['error'=>1,'msg'=>'参数错误'];
        $return_data=[];

        $where=[
            'u.id'=>['in',$son_arr],
            'u.user_type'=>20,
        ];

        $field='u.id,cn.company_name,cn.company_address,cn.company_phone_num,cn.company_user_name,us.p_id as parentId,(select fullname from sys_user where uid = (select sys_uid from dx_user where id = (select p_id from dx_user_son where user_id = u.id))) as saleName';
        $list=M('user')->alias('u')
            ->field($field)
            ->join('left join dx_user_son as us on us.user_id = u.id')
            ->join('left join dx_user_company as cn on cn.user_id = us.p_id')
            ->where($where)->select();
        if(!$list) return ['error'=>1,'msg'=>'没有数据'];

        $list_arr=[];
        foreach($list as $k=>$v){
            $list_arr[$v['id']]=$v;
        }
        $return_data['list']=$list_arr;

        return ['error'=>0,'data'=>$return_data];
    }


    /**
     * @desc erp客户列表
     */
    public function erpCustomerAll($where,$page,$pageSize)
    {
        $list=$this->baseList(M('customer','erp_'),$where,$page,$pageSize,'','fcustname,fcustno,fcustjc,fsalename');
        return $list;
    }




}