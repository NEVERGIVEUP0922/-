<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-12-27
 * Time: 11:28
 */
namespace Admin\Model;

use Admin\Model\BaseModel;
use Think\Model\RelationModel;

class RetreatModel extends RelationModel
{
    protected $tableName='order_retreat';

    protected $_link = [
        'user'=>[
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'User',
            'foreign_key' => 'user_id',
            'mapping_name' => 'userInfo',
        ],
        'retreat_goods'=>[
            'mapping_type' => self::HAS_MANY,
            'class_name' => 'OrderRetreatGoods',
            'foreign_key' => 're_sn',
            'mapping_name' => 'goods',
        ],
        'retreat_vm'=>[
            'mapping_type' => self::HAS_MANY,
            'class_name' => 'OrderRetreatVm',
            'foreign_key' => 're_sn',
            'mapping_name' => 'vmInfo',
        ],
        'retreat_log'=>[
            'mapping_type' => self::HAS_MANY,
            'class_name' => 'OrderRetreatLog',
            'foreign_key' => 're_sn',
            'mapping_name' => 'logs',
        ],

    ];

    public function getRetreatList( $where , $page=null, $pageSize=null, $order = '')
    {
        if( !empty($where) ){
            $this->where( $where );
            //$count  = $this->count();
        }
        if( empty( $order ) ){
            $order = 'create_time desc';
        }
        !empty( $page ) && !empty( $pageSize ) && $this->limit(($page-1)*$pageSize,$pageSize);
        $list = $this->relation(true)->order( $order )->select();
        $count  = $this->where($where)->count();
        return ['list'=>$list, 'count'=>$count];
    }

    public function getDetail( $re_sn )
    {
       $res = $this->where(['re_sn'=>$re_sn])->relation(true)->find();
        $res['logs'] = array_reverse($res['logs']);
       $res['order']= M('order')->where(['order_sn'=>$res['order_sn']])->find();
       foreach( $res['goods'] as $key=>$value ){
           $pro = M('product')->where(['id'=>$value['p_id']])->find();
           $res['goods'][$key] = array_merge(  $pro,$value );
           foreach( $res['vmInfo'] as $k=>$v ){
                if( $v['p_id'] == $value['p_id'] ){
                    $res['goods'][$key]['vm'] = $v;
                }
           }
       }
       return $res;
    }


    public function getUserSalesUid( $re_sn )
    {
        $user = $this->field('user_id')->where(['re_sn'=>$re_sn])->find();
        $sysUser = M('user')->where(['id'=>$user['user_id']])->find();
        return $sysUser['sys_uid'];
    }
}