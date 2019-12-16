<?php
namespace Api\Controller;
use Think\Controller;
/**
 * 支付宝
 */
class BaseController extends Controller{

    /**
     * 初始化方法
     */
    protected function _initialize(){
        if(C('API_TOKEN')!=I('get.token')){
            $destination = C('LOG_PATH').'pay_'.date('y_m_d').'.log';
            \Think\Log::write('非法支付调用token错误','ALERT','',$destination);
          	echo 'fail';
            die();
        }else{
            $destination = C('LOG_PATH').'pay_'.date('y_m_d').'.log';
            \Think\Log::write('post____:'.json_encode($_POST),'ALERT','',$destination);
            \Think\Log::write('get____:'.json_encode($_GET),'ALERT','',$destination);
        }
    }


}