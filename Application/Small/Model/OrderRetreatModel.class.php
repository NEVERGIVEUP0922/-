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


class OrderRetreatModel extends LinkModel
{

    protected $_link = array(
        'order_goods'  =>[
            'mapping_type'  => self::HAS_MANY,
            'foreign_key'=>'order_sn',
            'mapping_key'  => 'order_sn',
            'mapping_fields'=>'p_name,p_num',
        ],
    );

    public  function  retreatDetail($relation_where){
        $this->_link=[
            'order_retreat_goods'=>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'re_sn',
                'mapping_key'  => 're_sn',
                'mapping_fields'=>'*',
            ],
            'order_retreat_log'=>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'re_sn',
                'mapping_key'  => 're_sn',
                'mapping_fields'=>'*',
                'mapping_order'=>'update_time desc'
            ],
            'order'=>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'mapping_fields'=>'*',
            ],
            'order_goods'=>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'order_sn',
                'mapping_key'  => 'order_sn',
                'mapping_fields'=>'*',
            ],
            'kd_delivery'=>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'id',
                'mapping_key'  => 're_delivery_id',
                'as_fields'=>'kd_name',
            ],
        ];
        return $this;
    }


}