<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-01-03
 * Time: 15:35
 */
namespace EES\Model;

use Think\Model;
use Think\Log;
class ProductModel extends Model
{
    protected $trueTableName='erp_product';

    /**
     * 更新产品价格
     * @param string $itemNo ERP产品编码
     * @param float $stcb  更新的产品标准价格
     * @return bool
    **/
    public function changePrice( $itemNo, $stcb )
    {
        if( empty( $itemNo ) ){
            return false;
        }
        $model = M('','erp_product');
      	$ft=trim($itemNo);
		$ft = str_replace(array("\r\n", "\r", "\n"), "", $ft);
        $oldData = $model->field('fstcb')->where( [ 'ftem' => $ft ] )->find();
        //echo M()->getLastSql();
        if( !$oldData ){
            return true;
        }
        $model->startTrans();
        $res = $model->where( [ 'ftem' => $ft ] )->data([ 'Fstcb'=>$stcb ])->save();
        if( $res === false ){
            //失败回滚
            $model->rollback();
            return false;
        }
        //更新价格 大于 原价格
       // if( $stcb > $oldData['fstcb'] ){
            //更新区间价格
            //查询 对应的所有商城产品id
            $pids = M('product')->field('id')->where("fitemno='{$ft}'")->select();
        	 //echo M()->getLastSql();die();
     //file_put_contents('8888.txt',M()->getLastSql());
            if( $pids ){
                foreach( $pids as $k=>$value ){
                    //查询 对应商城产品的所有价格区间
                    $prices = M('product_price')->where(['p_id'=>$value['id']])->select();
                    foreach( $prices as $key=>$v ){
                        //计算区间的价格
                        $unit_price = (float)$stcb*(float)$v['price_ratio'];
                        //更新
                        $saveRes = D('product_price')->data([
							'id'=>$v['id'],
							'unit_price'=>$unit_price
						])->save();
                        if( $saveRes === false ){
                            //失败回滚
                            $model->rollback();
                            return false;
                        }
                    }
                }
            }
      //  }
        //提交事务
        $model->commit();
        return true;
    }

    /**
     * 更新产品库存
     * @param string $itemNo ERP产品编码
     * @param float $qty  更新的产品库存
     * @return bool
     **/
    public function changeQty( $itemNo, $qty )
    {
       	$ft=trim($itemNo);
		$ft = str_replace(array("\r\n", "\r", "\n"), "", $ft);
        return M('','erp_product')->where( [ 'ftem' => $ft ] )->save([ 'store'=> floatval($qty) ]);
    }
}