<?php
// +----------------------------------------------------------------------
// | FileName:   ProductEvent.class.php
// +----------------------------------------------------------------------
// | Dscription:   ERP产品库存及价格变更监控事件处理
// +----------------------------------------------------------------------
// | Date:  2018-01-10 14:32
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace EES\Event;

use EES\Model\ProductModel;
use Think\Log;

class ProductEvent extends BaseEvent
{
    /*
     * 操作分类为Product的数据库更新事件处理  与产品相关 更新价格与库存
     *
     */
    public function updateStore( $data )
    {
        $change =$data['change'];
        $itemNo =(string)$change[ 'itemNo' ];
        if( !empty( $itemNo ) ){
            //更新价格
            $model = new ProductModel();
          	$ft=trim($itemNo);
			$ft = str_replace(array("\r\n", "\r", "\n"), "", $ft);
            $isExists = M('','erp_product')->where(['ftem'=>$ft])->find();
            $model->startTrans();
            if( $isExists ){
                $priceRes = $model->changePrice( $itemNo, sctonum($change['price']) );
                if( $priceRes === false ){
                    Log::write( '事件编号: '.$data['act_no'].'的产品编码'.$itemNo.'的价格'.$change['price'].'更新失败!' );
                    $model->rollback();
                    return false;
                }
				$saveRes = M('','erp_product')->where(['ftem'=>$ft])->save([
					'Fstcb'=>sctonum($change['price']),
					'store'=>(int)$change['qty'],
					'vm'=>$change['vm_no'],
					'vm_name'=>$change['vm_name'],
					'ftem'=>$ft,
				]);
				if($saveRes ===false){
					Log::write( '事件编号: '.$data['act_no'].'的操作纪录处理save失败!' );
					$model->rollback();
					return false;
				}
                //更新库存
                $qtyRes = $model->changeQty( $itemNo, (int)$change['qty'] );
                if( $qtyRes === false ){
                    Log::write( '事件编号: '.$data['act_no'].'的产品编码'.$itemNo.'的库存更新失败!' );
                    $model->rollback();
                    return false;
                }
                //操作处理记录表 更新已处理
					$modelR = M('','sys_user');
					$vno=$change['vm_no']?$change['vm_no']:'';
					$resR = $modelR->field('uid')->where(['FEmplNo'=>$vno])->find();
					if($resR){
						$res1 = M('product_fitemno')->where(['fitemno'=>$ft])->save(['person_liable'=>$resR['uid']]);
                      //  echo M()->getLastSql();
						if ($res1===false){
							Log::write( '事件编号: '.$data['act_no'].'的操作纪录处理更新失败!' );
							$model->rollback();
							return false;
						}
				
					//Log::write( '事件编号: '.$data['act_no'].'的产品编码为'.$itemNo.'的产品更新成功!');
					//return true;
                	
                  //  $model->commit();
                    //通知完成 那么就告知EES 处理完成
                    //return true;
                }
                     $model->commit();
                    //通知完成 那么就告知EES 处理完成
                    return true;
               
            }else{
                $insertRes =  M('','erp_product')->add([
                    'FItemNo'=>$itemNo,
                    'Fstcb'=>sctonum($change['price']),
                    'store'=>(int)$change['qty'],
					'vm'=>$change['vm_no'],
					'vm_name'=>$change['vm_name'],
					'ftem'=>"$ft",
                ]);
                if( $insertRes !== false ){
                    $model->commit();
                    //通知完成 那么就告知EES 处理完成
                    return true;
                }else{
					Log::write( '事件编号: '.$data['act_no'].'的操作纪录处理insert失败!' );
					$model->rollback();
					return false;
				}
            }
        }else{
            return false;
        }

    }


    public function insertStore( $data )
    {
        return  $this->updateStore($data);
    }

    /*
     * 操作分类为Product的数据库删除事件处理  与产品相关 更新价格与库存
     *
     */
    public function deleteStore( $data )
    {
        $change =$data['change'];
        $itemNo = $change[ 'itemNo' ];
        if( !empty( $itemNo ) ){
            //更新价格
            $model = new ProductModel();
            $model->startTrans();
            $priceRes = $model->changePrice( $itemNo, sctonum($change['price']) );
            if( $priceRes === false ){
                Log::write( '事件编号: '.$data['act_no'].'的产品编码'.$itemNo.'的价格更新失败!' );
                $model->rollback();
                return false;
            }
            //更新库存
            $qtyRes = $model->changeQty( $itemNo, $change['qty'] );
            if( $qtyRes === false ){
                Log::write( '事件编号: '.$data['act_no'].'的产品编码'.$itemNo.'的库存更新失败!' );
                $model->rollback();
                return false;
            }
            //操作处理记录表 更新已处理
            $actionRes = M('ees_action')->where( [ 'act_no' => $data[ 'act_no' ] ] )->save( [ 'is_store' => 1 ] );
            if ( $actionRes !== false ) {
                $model->commit();
                //通知完成 那么就告知EES 处理完成
                return true;
            }else{
                Log::write( '事件编号: '.$data['act_no'].'的操作纪录处理更新失败!' );
                $model->rollback();
                return false;
            }
        }

        return false;

    }
}