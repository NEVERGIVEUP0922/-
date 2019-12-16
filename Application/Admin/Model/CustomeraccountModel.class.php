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
use EES\System\Redis;
class CustomeraccountModel extends BaseModel
{

    /*
     * 客户账期列表
     *
     */
    public function customerAccountList($where='',$page='',$pageSize='',$order='',$field=''){
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
        $list=$this->baseList($bargain,$where,$page,$pageSize,$order,$field);

        $userId_arr=[];
        if($list['error']==0){
            foreach($list['data']['list'] as $k=>$v){
                $userId_arr[]=$v['user_id'];
            }

            $bargain_temp=M('user_account_temp');
            $bargain_temp=$this->baseListType($bargain_temp,$userId_arr,'user_id');

            $customerList=$this->baseList(M('user'),['id'=>['in',$userId_arr]]);
            $adminList_arr=[];
            foreach($customerList['data']['list'] as $k=>$v){
                $adminList_arr[]=$v['sys_uid'];
            }
            $adminList=$this->baseListType(M('user','sys_'),$adminList_arr,'uid');//业务员
            $erpAccount=$this->baseListType(M('accounts','erp_'),$userId_arr,'user_id');

            $userList=$this->baseListType(M('user'),$userId_arr,'id');
            if($userList['error']==0){
                foreach($list['data']['list'] as $k=>$v){
                    $list['data']['list'][$k]['user_name']=$userList['data']['list'][$v['user_id']]['fcustjc'];
                    $one_sale_id=$userList['data']['list'][$v['user_id']]['sys_uid'];
                    $list['data']['list'][$k]['sale_name']=$adminList['data']['list'][$one_sale_id]['femplname'];
                    $list['data']['list'][$k]['nick_name']=$userList['data']['list'][$v['user_id']]['nick_name'];
                    $list['data']['list'][$k]['account_type']=$erpAccount['data']['list'][$v['user_id']]['account_type'];
                    $list['data']['list'][$k]['since_name']=$erpAccount['data']['list'][$v['user_id']]['since_name'];
//                    if($bargain_temp['data']['list'][$v['user_id']]&&$bargain_temp['data']['list'][$v['user_id']]['status']!=1){
                        $list['data']['list'][$k]['account_temp']=$bargain_temp['data']['list'][$v['user_id']];
//                    }
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
        }
//        $one=M('user_account_temp')->where(['id'=>$request['id']])->find();
        $one=M('user_account_temp')->where(['user_id'=>$request['user_id']])->find();
        if(!$one) return ['error'=>1,'msg'=>'账期信息错误'];
        $user=M('user')->where(['id'=>$request['user_id']])->find();
        $hasType=M('account_type')->where(['id'=>$request['day_type'],'user_type'=>$user['user_type']])->find();
        if(!$hasType) return ['error'=>1,'msg'=>'用户不可申请此类型帐期'];
        $data=[
//            'id'=>$request['id'],
            'status'=>1,
            'day_type'=>$request['day_type'],
            'sys_user_signature_img'=>$request['sys_user_signature_img'],
            'sys_uid'=>session('adminId'),
//            'quota'=>$request['quota']?:0,
        ];

        $one_2=M('user_account')->where(['id'=>$request['id']])->find();
        $temp=array_merge($one,$data);

        $m=M('user');
        $m->startTrans();
        if(!$one_2){
            $result=M('user_account')->add($temp);
        }else{
            $result=M('user_account')->where(['user_id'=>$request['user_id']])->save($temp);
        }

        $result3=M('user_account_temp')->save($temp);
        $result2=$m->where(['id'=>$one['user_id']])->save(['isedit_user_name'=>0]);

        if($result===false || $result2===false || $result3===false){
            $m->rollback();
            return ['error'=>1,'msg'=>'failed2'];
        }
	
		$erpC=M("","erp_accounts")->where(["user_id"=>$request['user_id']])->find();
        if($erpC){
			$r=M('account_type')->where(["id"=>$request['day_type']])->find();
			$u=M("user")->where(["id"=>$request['user_id']])->find();
			$erpR=M("","erp_accounts")->where(["user_id"=>$request['user_id']])->save(["since_name"=>$r['name'],'fcustno'=>$u['fcustno']]);
			if($erpR===false){
				$m->rollback();
				return ['error'=>1,'msg'=>'failed2'];
			}else{
				$m->commit();
				if( !empty( $u['fcustno'] ) ){
					$key = 'syncCustCreditList';
					$redis = Redis::getInstance();
					$redis->sAdd( $key, $u['fcustno'] );
				}
				return ['error'=>0,'msg'=>'success'];
				
			}
		}else{
			$r=M('account_type')->where(["id"=>$request['day_type']])->find();
        	$u=M("user")->where(["id"=>$request['user_id']])->find();
        	if($u){
				$erpR=M("","erp_accounts")->add(["since_name"=>$r['name'],"user_id"=>$request['user_id'],'fcustno'=>$u['fcustno']]);
				if($erpR===false){
					$m->rollback();
					return ['error'=>1,'msg'=>'failed3'];
				}else{
					$m->commit();
					if( !empty( $u['fcustno'] ) ){
						$key = 'syncCustCreditList';
						$redis = Redis::getInstance();
						$redis->sAdd( $key, $u['fcustno'] );
					}
					return ['error'=>0,'msg'=>'success'];
			
				}
			}else{
				$m->rollback();
				return ['error'=>1,'msg'=>'不存在该客户'];
			}
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