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

class CustomeraccountModel extends BaseModel
{

    /*
     * 客户账期列表
     *
     */
    public function customerAccountList($where='',$page='',$pageSize='',$order=''){
        $must_where=[];
        $method_name=MODULE_NAME;
        $key_method=strtolower($method_name);
        $session=session();
        if($key_method=='admin'){
            $userM=new UserModel();
            $productPowers=$userM->departmentDataPower('order',$session['adminInfo']['department_id'],$where,'sys_uid');
            if($productPowers['error']!=0) return $productPowers;
            if(isset($productPowers['data']['must_where'])){
                $customers=$userM->adminSaleCustomerPower($productPowers['data']['must_where'],'id');
                if($customers['error']!=0) return $customers;
                $must_where['user_id']=['in',$customers['data']];
            }
        }
        if($where){
            if($must_where){
                $where=[$where,$must_where];
            }
        }else if($must_where){
            $where=$must_where;
        }

        $bargain=M('user_account');
        $list=$this->baseList($bargain,$where,$page,$pageSize,$order);
        $userId_arr=[];
        if($list['error']==0){
            foreach($list['data']['list'] as $k=>$v){
                $userId_arr[]=$v['user_id'];
            }
            $userList=$this->baseListType(M('user'),$userId_arr,'id');
            if($userList['error']==0){
                foreach($list['data']['list'] as $k=>$v){
                    $list['data']['list'][$k]['user_name']=$userList['data']['list'][$v['user_id']]['user_name'];
                    $list['data']['list'][$k]['nick_name']=$userList['data']['list'][$v['user_id']]['nick_name'];
                }
            }
        }
        return $list;
    }

    /*
     * 客户账期使用列表
     *
     */
    public function customerAccountUseList($where='',$page='',$pageSize='',$order=''){
        $bargain=M('user_order_account');
        $list=$this->baseList($bargain,$where,$page,$pageSize,$order);
        return $list;
    }

    /*
     * 客户账期审核
     *
     */
    public function customerAccountCheck($request){
        if($request['status']==1){
            if(!$request['sys_user_signature_img']) return ['error'=>'1','msg'=>'没有上传签字图片'];
            if(!$request['quota']) return ['error'=>'1','msg'=>'额度不对'];
        }
        $one=M('user_account')->where(['id'=>$request['id']])->find();
        if(!$one) return ['error'=>1,'msg'=>'账期信息错误'];
        $user=M('user')->where(['id'=>$request['user_id']])->find();
        $hasType=M('account_type')->where(['id'=>$request['day_type'],'user_type'=>$user['user_type']])->find();
        if(!$hasType) return ['error'=>1,'msg'=>'用户不可申请此类型帐期'];
        $data=[
            'id'=>$request['id'],
            'status'=>1,
            'day_type'=>$request['day_type'],
            'sys_user_signature_img'=>$request['sys_user_signature_img'],
            'sys_uid'=>session('adminId'),
            'quota'=>$request['quota'],
        ];
        $m=M('user');
        $m->startTrans();
        $result=M('user_account')->save($data);
        $result2=$m->where(['id'=>$one['user_id']])->save(['isedit_user_name'=>0]);
        if($result===false || $result2===false){
            $m->commit();
            return ['error'=>1,'msg'=>'faild'];
        } else{
            $m->rollback();
            return ['error'=>0,'msg'=>'success'];
        }
    }

    /*
     * 账期类型列表
     *
     */
    public function accountTypeList($where='',$page='',$pageSize='',$order=''){
        $bargain=M('account_type');
        $list=$this->baseList($bargain,$where,$page,$pageSize,$order);
        return $list;
    }









}