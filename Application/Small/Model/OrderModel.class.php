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


class OrderModel extends LinkModel
{

    protected $_link = array(
        'order_goods'  =>[
            'mapping_type'  => self::HAS_MANY,
            'foreign_key'=>'order_sn',
            'mapping_key'  => 'order_sn',
            'mapping_fields'=>'p_name,p_num',
        ],
    );
    /**
     *
     * @desc 是否全款支付
     * @param $pay_type:定单支付方式
     * @param $total_deposits:定经总额
     *
     */
    public function orderIsAllPay($pay_type,$total_deposits,$order_type=0){
        if($total_deposits){
            if( in_array($pay_type,[2,3])){
                $order_type=1;
            }
        }
        return $order_type;
    }
    /**
     * @desc 定单列表
     *
     */
    public function orderList(){
        $this->_link=[
            'order_goods'  =>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                //'mapping_fields'=>'p_name,p_num,p_price_true,p_id,knot_num,erp_num,retreat_num,pay_subtotal',
                'mapping_fields'=>'*',
            ],
            'order_sync_hy'=>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'order_no',
                'mapping_key'  => 'order_sn',
                'mapping_fields'=>'order_no,erp_th_no,is_kd,hy_shipvia,hy_num,hy_name,kd_code,detail,partid,is_recive',
            ],
            'order_pay_history'=>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'condition'=>'type=1',
                'mapping_fields'=>'IFNULL((sum(pay_amount)),0) as hymoney',
            ],
            'order_knot'=>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'mapping_fields'=>'check_status',
            ],
            'order_retreat'=>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'mapping_fields'=>'order_sn,re_sn,retreat_money,handle_status,retreat_type,retreat_img,retreat_desc,re_delivery_status',
            ],
        ];
        return $this;
    }

    /**
     * @desc 定单详情
     *
     */
    public function orderDetail(){
        $this->_link=[
            'order_goods'  =>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'condition'=>'pay_subtotal>0',
                'mapping_fields'=>'*',
            ],
            'order_pay_history'=>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'condition'=>'type=1',
                'mapping_fields'=>'IFNULL((sum(pay_amount)),0) as hymoney',
            ],
            'order_sync_hy'=>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'order_no',
                'mapping_key'  => 'order_sn',
                'mapping_fields'=>'order_no,erp_th_no,is_kd,hy_shipvia,hy_num,hy_name,kd_code,detail,partid,is_recive',
            ],
            'order_knot'=>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'as_fields'=>'check_status',
            ],
            'order_detail'=>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'mapping_fields'=>'order_sn,area_code,address,mobile,note,consignee',
            ]
        ];
        return $this;
    }

    /**
     * @desc 退贷退款详情
     *
     */
    public function retreatDetail($relation_where=''){
        $this->_link=[
            'order_goods'  =>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'mapping_fields'=>'*',
            ],
            'order_pay_history'=>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'condition'=>'type=1',
                'mapping_fields'=>'IFNULL((sum(pay_amount)),0) as hymoney',
            ],
            'order_sync_hy'=>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'order_no',
                'mapping_key'  => 'order_sn',
                'mapping_fields'=>'order_no,erp_th_no,is_kd,hy_shipvia,hy_num,hy_name,kd_code,detail,partid,is_recive',
            ],
            'order_retreat'=>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'condition'=>$relation_where['retreatDetail']?:'',
                'mapping_order' => 'update_time desc',
                'mapping_fields'=>'order_sn,re_sn,retreat_money,handle_status,retreat_type,retreat_img,retreat_desc,re_delivery_status',
            ],
            'order_retreat_goods'=>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'mapping_fields'=>'order_sn,re_sn,p_id,p_num,p_price',
            ],
            'order_detail'=>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'mapping_fields'=>'order_sn,area_code,address,mobile,note,consignee',
            ]
        ];
        return $this;
    }

    /**
     * @desc 定单结算
     *
     */
    public function orderPay($relation_where=''){
        $this->_link=[
            'order_goods'  =>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'mapping_fields'=>'p_name,p_num,p_price_true,p_id,pay_subtotal',
            ],
            'user_pay_account'  =>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'`index`',
                'mapping_key'  => 'order_sn',
                'condition'=>$relation_where['user_pay_account']?:'',
                'mapping_fields'=>'user_id,index,amount',
            ],
        ];
        return $this;
    }

    /**
     * @desc 定单评论添加
     *
     */
    public function orderCommentAction($request,$field=''){
        M()->startTrans();
        if(!$field) $field='order_sn,p_id,user_id,type,content,images,star';
        $result=M('user_comment')->field($field)->addAll($request);
        if(!$result){
            M()->rollback();
            return ['error'=>-1,'msg'=>'field'];
        }
        $order_res=M('order')->where(['order_sn'=>$request[0]['order_sn']])->save(['is_comment'=>2]);
        if(!$order_res){
            M()->rollback();
            return ['error'=>-1,'msg'=>'field'];
        }
        M()->commit();
        return ['error'=>0,'msg'=>'success'];
    }


}