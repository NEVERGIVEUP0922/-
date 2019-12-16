<?php
// +----------------------------------------------------------------------
// | FileName:   ProductModel.class.php
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date:  2018-03-16 9:31
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Model\Mysql;

use Think\Model;

class ProductModel extends Model
{
	
	public function getInfoByFitemNo( $sign )
	{
		$res = $this->field('id,fitemno')->where(['p_sign'=>$sign])->find();
		if( $res ){
			//查询产品VM信息
			$vms = M('','erp_product')->field('vm,vm_name')->find();
			if( $vms ){
				$res = array_merge( $res , $vms );
			}else{
				$res['vm'] = $res['vm_name'] = '';
			}
		}
		return $res;
	}
}