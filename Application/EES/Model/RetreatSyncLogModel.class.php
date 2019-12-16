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
        return  M('retreat_sync_log')->where( ['re_sn'=>$re_sn] )->select();
    }

    public function getMaxLindId( $re_sn )
    {
        $id = M('retreat_sync_log')->where(['re_sn'=>$re_sn])->count();
        return $id+1;
    }

    public function getAllSyncFailList()
    {
       return M('retreat_sync_log')->field('re_sn')->where(['sync_status'=>2])->select();
    }
}