<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-12-28
 * Time: 17:35
 */
namespace EES\Controller;

use Common\Controller\BaseController;
use EES\Sdk\Client;
use Think\Exception;
use Think\Log;
class EESController extends BaseController
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    public function checkRequire( $requireData, $data )
    {
        if( !is_array($requireData) || empty( $requireData ) ){
            return false;
        }
        foreach( $requireData as $k=>$v ){
            //不存在或为空
            if( !isset( $data[ $k ] ) || is_null($data[ $k ]) ){;
                return ['code'=>63,'msg'=>$v.' 参数不存在或参数为空'];
            }
        }
        return true;
    }

    public static function requestEes( Array $data , $service)
    {
        $client = Client::create();
        $rs = $client->withHost(C('EES_HOST'))
            ->withService($service)
            ->setData( $data )
            ->send();
        if( $rs === false ){
            Log::write('EES错误: EES连接失败!请检查!');
            return [ 'error'=>true, 'msg'=> ' EES连接失败!请检查!', 'code'=>401];
        }
        if( $rs['ret'] != 100 && !$rs['status']  ){
            Log::write('EES错误:'.$rs['msg']);
            return [ 'error'=>true, 'msg'=>$rs['msg'] ];
        }else{
            return $rs;
        }
    }

    public function ajaxReturn( $data = [],$status=true, $msg='', $code=0 )
    {
        $return = [
            'error'=>$status?0:($code?$code:10),
            'data'=>$data,
            'msg'=>$msg,
        ];
        parent::ajaxReturn( $return, 'json' );
    }



    public function test()
    {
        $order_sn = '1801101020961';
        $result= (new \Home\Model\ErpModel())->orderAccountUpdate( $order_sn );
    }


}