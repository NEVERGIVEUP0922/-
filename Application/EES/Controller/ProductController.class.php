<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-12-28
 * Time: 17:36
 */
namespace EES\Controller;

use EES\Model\ProductModel;

class ProductController extends  EESController
{
    protected function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    /**
     *
     * 获取指定ERP产品编码的标准价格 并 更新数据
     * @param string $itemNo ERP 产品编码
     * @return json 产品价格
    **/
    public function getItemPrice( $itemNo )
    {
        $rs = $this->requestEes( ['itemNo'=>$itemNo ], 'App.Product.GetItemStcostPrice' );
        if( $rs['error'] ){
           $this->ajaxReturn( [],false,$rs['msg'], $rs['code'] );
        }else{
            //更新
            $model = new ProductModel();
            $model->changePrice($itemNo, $rs['data']['price'] );
            $this->ajaxReturn( $rs['data']);
        }
    }

    /**
     *
     * 获取指定ERP产品编码的可用库存 并 更新数据
     * @param string $itemNo ERP 产品编码
     * @return mixed
     **/
    public function getItemStock( $itemNo )
    {
        $rs = $this->requestEes( ['itemNo'=>$itemNo ], 'App.Product.GetItemRealStockNum' );
        if( $rs['error'] ){
            $this->ajaxReturn( [],false,$rs['msg'], $rs['code'] );
        }else{
            //更新
            $model = new ProductModel();
            $model->changeQty($itemNo, $rs['data']['qty'] );
            $this->ajaxReturn( $rs['data']);
        }
    }


}