<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-01-06
 * Time: 15:04
 */
namespace EES\Controller;

use Admin\Model\RetreatModel;
use Think\Log;

class RetreatController extends EESController
{

    public function getRetreatOrderInfo( $re_sn )
    {
        $retreat = new RetreatModel();
        $res = $retreat->getDetail( $re_sn );
        if( !$res ){
            $res = false;
        }
        $this->ajaxReturn($res);
    }
}