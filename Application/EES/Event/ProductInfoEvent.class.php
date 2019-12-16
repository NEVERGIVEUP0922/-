<?php
// +----------------------------------------------------------------------
// | FileName:   ProductInfoEvent.class.php
// +----------------------------------------------------------------------
// | Dscription:   ERP产品基础资料监控事件处理
// +----------------------------------------------------------------------
// | Date:  2018-01-12 17:32
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace EES\Event;

use EES\Model\ProductModel;
use Think\Log;


class ProductInfoEvent extends BaseEvent
{
    /*
     *
     * ERP 产品资料修改Update事件处理
     * 因为ERP产品编码是不能更改的所以无需处理此事件
     */
    public function updateStore( $data )
    {
        return true;
    }

    /*
     *
     * ERP 产品新增Insert事件处理
     */
	
	public function insertStore( $data )
	{
		$model = M('','erp_product');
		$change = $data['change'];
		$itemNo = $change['itemNo'];
		$ft=trim($itemNo);
		$ft = str_replace(array("\r\n", "\r", "\n"), "", $ft);
		$res = $model->field('id')->where(['ftem'=>$ft])->find();
		if( !$res ){
			$save = [
				'FItemNo'=>$itemNo,
				'Fstcb'=> 0,
				'store'=> 0,
				'vm'=>'',
				'vm_name'=>'',
				'ftem'=>'',
			];
			if( isset($change['qty']) && isset( $change['price'] ) ){
				$save = [
					'FItemNo'=>$itemNo,
					'Fstcb'=> sctonum($change['price']),
					'store'=> (int)$change['qty'],
					'vm'=>$change['vm_no']?$change['vm_no']:'',
					'vm_name'=>$change['vm_name']?$change['vm_name']:'',
					'ftem'=>$ft,
				];
			}
			$insert = $model->add( $save );
			if( $insert === false ) {
				Log::write( '事件编号: '.$data['act_no'].'的产品编码为'.$itemNo.'的新产品添加失败!' );
				return false;
			}else{
				Log::write( '事件编号: '.$data['act_no'].'的产品编码为'.$itemNo.'的新产品添加成功!插入id为:'.$insert );
				return true;
			}
		}else{
         
			$save = [
				'Fstcb'=> 0,
				'store'=> 0,
				'vm'=>'',
				'vm_name'=>'',
			];
			if( isset($change['qty']) && isset( $change['price'] ) ){
				$save = [
					'Fstcb'=> sctonum($change['price']),
					'store'=> (int)$change['qty'],
					'vm'=>$change['vm_no']?$change['vm_no']:'',
					'vm_name'=>$change['vm_name']?$change['vm_name']:'',
				];
			}
			$update = $model->where(['ftem'=>$ft])->save($save);
           // echo M()->getLastSql();
			if( $update === false ) {
				//file_put_contents('77.txt','bbb');
				Log::write( '事件编号: '.$data['act_no'].'的产品编1111码为'.$itemNo.'的产品更新失败!' );
				return false;
			}else{
				
				$modelR = M('','sys_user');
				$vno=$change['vm_no']?$change['vm_no']:'';
				$resR = $modelR->field('uid')->where(['FEmplNo'=>$vno])->find();
				if($resR){
					$res = M('product_fitemno')->where(['fitemno'=>$ft])->save(['person_liable'=>$resR['uid']]);
                    //file_put_contents('727.txt',M()->getLastSql());
				}
				Log::write( '事件编号: '.$data['act_no'].'的产品编码为'.$itemNo.'的产品更新成功!');
				return true;
			}
			//$insert = $model->add( $save )

		}
		return true;
	}
	
//	    public function insertStore( $data )
//    {
//        $model = M('','erp_product');
//        $change = json_decode( $data['change'], true );
//        $itemNo = $change['itemNo'];
//
//        $res = $model->field('id')->where(['FitemNo'=>$itemNo])->find();
//        if( !$res ){
//			$save = [
//				'FItemNo'=>$itemNo,
//				'Fstcb'=> 0,
//				'store'=> 0,
//				'vm'=>'',
//				'vm_name'=>'',
//			];
//			if( isset($change['qty']) && isset( $change['price'] ) ){
//				$save = [
//					'FItemNo'=>$itemNo,
//					'Fstcb'=> sctonum($change['price']),
//					'store'=> (int)$change['qty'],
//					'vm'=>$change['vm_no']?$change['vm_no']:'',
//					'vm_name'=>$change['vm_name']?$change['vm_name']:'',
//				];
//			}
//			$insert = $model->add( $save );
//			if( $insert === false ) {
//				Log::write( '事件编号: '.$data['act_no'].'的产品编码为'.$itemNo.'的新产品添加失败!' );
//				return false;
//			}else{
//				Log::write( '事件编号: '.$data['act_no'].'的产品编码为'.$itemNo.'的新产品添加成功!插入id为:'.$insert );
//				return true;
//			}
//		}
//		return true;
//    }


    /*
     *
     * ERP 产品资料删除事件处理
     */
    public function deleteStore( $data )
    {
        $change = $data['change'];

        if( $change['isDel'] ){
            $model = M('','erp_product');
            $itemNo = $change['itemNo'];
            $res = $model->where(['FItemNo'=>$itemNo])->delete();
            if( $res === 0 || $res ){
                return true;
            }else{
                Log::write( '事件编号: '.$data['act_no'].'的产品编码为'.$itemNo.'的产品删除失败!' );
                return false;
            }
        }
        return false;
    }
}