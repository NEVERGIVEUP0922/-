<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-01-09
 * Time: 16:31
 */
namespace EES\Model;

use Think\Model;

class RetreatSyncLogModel extends Model
{

    public function getAllLog( $re_sn )
    {
        return $this->where( ['re_sn'=>$re_sn] )->select();
    }

    public function getMaxLindId( $re_sn )
    {
        $id = $this->where(['re_sn'=>$re_sn])->count();
        return $id+1;
    }
}