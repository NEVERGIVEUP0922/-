<?php

// +----------------------------------------------------------------------
// | FileName:   ProductController.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2018/5/09 10:00
// +----------------------------------------------------------------------
// | Author: kelly <466395102@qq.com>
// +----------------------------------------------------------------------
namespace  Admin\Event;



class PowerorderknotEvent implements PowerEvent
{
    public $where;

    /**
     * @返差额权限 获取权限条件
     * @param int departmentId
     * @return array  where  sql  的搜索条件
     *
     */
    public function getPowerWhere($where=''){
        $must_where=[];
        $method_name=MODULE_NAME;
        $key_method=strtolower($method_name);
        $session=session();
        if($key_method=='admin'){
            $userM=new \Admin\Model\UserModel();
            //业务部门
            $productPowers=$userM->departmentDataPower('order',$session['adminInfo']['department_id'],$where,'sys_uid');
            if($productPowers['error']!=0) return $productPowers;
            if(isset($productPowers['data']['must_where'])){
                $customers=$userM->adminSaleCustomerPower($productPowers['data']['must_where'],'id');
                if($customers['error']!=0) return $customers;
                $must_where['user_id']=['in',$customers['data']];
            }
            //财务部门
            $productPowers=$userM->departmentDataPower('money',$session['adminInfo']['department_id'],$where,'sys_uid');
            if($productPowers['error']!=0) return $productPowers;
            if(isset($productPowers['data']['must_where'])){
                $customers=$userM->adminSaleCustomerPower($productPowers['data']['must_where'],'id');
                if($customers['error']!=0) return $customers;
                $must_where['user_id']=['in',$customers['data']];
            }
        }else{
            if(!(isset($where['order_status'])&&(int)$where['order_status']==101)) $must_where['order_status']=['lt',100];
        }
        if($where){
            if($must_where){
                $where=[$where,$must_where];
            }
        }else if($must_where){
            $where=$must_where;
        }

        $this->where=$where;
        return $this;
    }


}