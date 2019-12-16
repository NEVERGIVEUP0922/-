<?php
// +----------------------------------------------------------------------
// | FileName:   UserInvoiceModel.class.php
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date:  2018-01-25 10:28
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace  Admin\Model;

use Think\Model\RelationModel;

class UserInvoiceModel extends RelationModel
{
    protected $tableName='user_invoice';

    protected $_link = [
        'user'=>[
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'User',
            'foreign_key' => 'user_id',
            'mapping_name' => 'userInfo',
        ],
    ];

    public function getInvoiceList( $where , $page=null, $pageSize=null, $order = '' )
    {
        if( !empty($where) ){
            $this->where( $where );
        }
        !empty( $page ) && !empty( $pageSize ) && $this->limit(($page-1)*$pageSize,$pageSize);
        $list = $this->relation(true)->select();
        $count  = $this->count();
        return ['list'=>$list, 'count'=>$count];
    }
}