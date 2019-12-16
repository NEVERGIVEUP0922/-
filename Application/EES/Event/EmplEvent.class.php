<?php
// +----------------------------------------------------------------------
// | FileName:   EmplEvent.class.php
// +----------------------------------------------------------------------
// | Dscription:   ERP系统用户资料监控事件处理
// +----------------------------------------------------------------------
// | Date:  2018-01-16 14:26
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace EES\Event;

use EES\Model\ErpUserModel;
use EES\Model\OrderModel;
use Think\Log;

class EmplEvent extends BaseEvent
{
    /*
     *  更新通知事件处理
     *
     */
    public function updateStore( $data )
    {
        $change = $data['change'];
        $emplNo = $change['emplNo'];
        $emplName = $change['emplName'];
        $erpAdmin = M('','erp_admin');
        $erpAdmin->startTrans();
        //更新erp_admin表信息
        $adminRes = $erpAdmin->where(['femplno'=>$emplNo])->save([ 'femplname'=>$emplName ]);
        if( $adminRes === false ){
            Log::write('ERP系统用户更改事件:更新erp_admin表中femplno='.$emplNo.'的数据语句执行失败!');
            $erpAdmin->rollback();
            return false;
        }
        //更新sys_user表信息
        $sysRes = M('','sys_user')->where(['FEmplNo'=>$emplNo])->save([ 'FEmplName'=>$emplName ]);
        if( $sysRes === false ){
            Log::write('ERP系统用户更改事件:更新sys_user表中FEmplNo='.$emplNo.'的数据语句执行失败!');
            $erpAdmin->rollback();
            return false;
        }
        //完成
        $erpAdmin->commit();
        return true;
    }

    public function insertStore( $data )
    {
        $change = $data['change'];
        $emplNo = $change['emplNo'];
        $emplName = $change['emplName'];
        $insert = M('','erp_admin')->add(['femplno'=>$emplNo,'femplname'=>$emplName]);
        if( $insert === false ){
            Log::write('ERP系统用户新增事件:在erp_admin表中新增femplno='.$emplNo.'femplname='.$emplName.'的数据语句执行失败!');
            return false;
        }
        if( $insert > 0 ){
            return true;
        }
        return false;
    }

    public function deleteStore( $data )
    {
        $change = $data['change'];

        if( $change['isDel'] ){
            $emplNo = $change['emplNo'];
            $erpAdmin = M('','erp_admin');
            $erpAdmin->startTrans();
            //删除erp_admin表中的数据
            $delRes = $erpAdmin->where(['femplno'=>$emplNo])->delete();
            if( $delRes === false ){
                Log::write('ERP系统用户删除事件:在erp_admin表中删除femplno='.$emplNo.'的数据语句执行失败!');
                return false;
            }
            //更新sys_user表的数据
            $upRes = M('','sys_user')->where(['FEmplNo'=>$emplNo])->save([ 'FEmplNo'=>'','FEmplName'=>'' ]);
            if( $upRes === false ){
                Log::write('ERP系统用户删除事件:在sys_user表中更新femplno='.$emplNo.'的数据语句执行失败!');
                return false;
            }
        }
    }
}