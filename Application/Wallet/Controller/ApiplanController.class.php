<?php
namespace Wallet\Controller;

use Think\Controller;

class ApiplanController extends Controller{

    /**
     * @desc 月度积分，年度积分
     *
     */
    public function integralCrontab(){
        $request=I('get.');
        $type=$request['type'];
        if(!in_array($type,[21,41])) die(json_encode(['error'=>1,'msg'=>'参数错误']));
        $result=D('Wallet/Integral')->integralCrontab($type);
        die(json_encode($result));
    }




}