<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-12-29
 * Time: 17:37
 */
namespace EES\Model;

use Think\Model\RelationModel;

class UserModel extends  RelationModel
{
    protected $_link =[
        'normal'=>[
            'mapping_type' => self::HAS_ONE,
            'class_name' => 'UserNormal',
            'foreign_key' => 'user_id',
            'mapping_name' => 'normal',
        ],
        'company'=>[
            'mapping_type' => self::HAS_ONE,
            'class_name' => 'UserCompany',
            'foreign_key' => 'user_id',
            'mapping_name' => 'company',
        ],
        'address'=>[
            'mapping_type' => self::HAS_MANY,
            'class_name' => 'UserOrderAddress',
            'foreign_key' => 'user_id',
            'mapping_name' => 'address',
        ],
    ];

    public function getAllInfo( $userId )
    {
        $user = $this->field('user_type')->find( $userId );
        $userInfo = [];
        if( $user['user_type'] == 1 ){
            $userInfo = $this->relation(['normal','address'])->find($userId);
        }else{
            $userInfo = $this->relation(['company', 'address'])->find( $userId );
        }
        return $userInfo;
    }

    public function getParentId( $uid )
    {
        return M('user_son')->field('p_id')->where(['user_id'=> $uid ])->find();
    }
}