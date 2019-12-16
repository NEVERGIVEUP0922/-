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

class AdminHandleHistoryModel extends BaseModel
{
    protected $tableName = 'sys_user_handle_history';
    protected $pk='id';

    /**
     * @desc 添加操作记录
    */
    public function add($data,$syncOrder){
        $data['sys_uid']=session('adminId');
        if($syncOrder){
			$to=$data['type'].'Save';
		}else{
			$to=$data['type'].'Add';
		}
        return $this->$to($data);
    }

    public function order_priceAdd($data){
        $result=M('user_handle_history','sys_')->field('sys_uid,type,index,data_old,request')->add($data);
        if(!$result) return ['error'=>1,'msg'=>'记录失败'];
        return ['error'=>0,'msg'=>'记录成功'];
    }
	
	public function order_priceSave($data){
		$data['sync_status']=0;
		$result=M('user_handle_history','sys_')->where(['index'=>$data['index']])->save($data);
		if(!$result) return ['error'=>1,'msg'=>'记录失败'];
		return ['error'=>0,'msg'=>'记录成功'];
	}


}