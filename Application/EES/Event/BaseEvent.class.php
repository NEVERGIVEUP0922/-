<?php
// +----------------------------------------------------------------------
// | FileName:   BaseEvent.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017-12-29 11:26
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace EES\Event;

use EES\Sdk\Client;
use EES\System\Redis;
use Think\Log;

abstract class BaseEvent
{
    protected $action = '';
    protected $cate = '';

    abstract public function updateStore( $data );

    abstract public function insertStore( $data );

    abstract public function deleteStore( $data );

    /*
     * 发送通知事件处理结果
     * @param  $act_no  事件编码
     * @para
     */
    public function sendStoreResult( $act_no )
    {
		$redis=Redis::getInstance();
		$redis->sRem('shoperpsyncEvent',serialize($act_no));
		//$redis->sRem('shoperpsyncEvent1',serialize($act_no));
		return true;
        $res = M( 'ees_action' )->where( [ 'act_no' => $act_no ] )->save( [ 'is_store' => 1 ] );
        if( $res !== false ){
            return true;
        }
        return false;
    }
}