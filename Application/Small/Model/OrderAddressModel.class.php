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


class OrderAddressModel extends LinkModel
{

    /**
     * @desc 我的数据保存：收贷地址
     *
     */
    public function orderAddressSave($check,$request){
        $mAction=M('user_order_address');
        if($check['action']=='delete'){
            $result_delete=$mAction->where(['id'=>$request['id']])->delete();
            if($result_delete===false){
                return ['error'=>-1,'msg'=>'删除失败'];
            }else{
                return ['error'=>0,'msg'=>'删除成功'];
            }
        }

        $mAction_field='user_id,consignee,area_code,address,zipcode,mobile,status';
        $action_data=[];
        foreach($request as $k=>$v){
            if(strpos($mAction_field,$k)!==false){
                $action_data[$k]=$v;
            }
        }

        if($request['status']==1){
            $mAction->where([['user_id'=>$request['user_id']]])->save(['status'=>0]);
        }
        $result='';
        if($check['action']=='add'){
            $result=$mAction->field($mAction_field)->add($action_data);
        }else{
            $result=$mAction->where([['id'=>$request['id']]])->field($mAction_field)->save($action_data);
        }

        if($result===false){
            return ['error'=>-1,'msg'=>'failed'];
        }else{
            return ['error'=>0,'msg'=>'success'];
        }
    }

}