<?php

// +----------------------------------------------------------------------
// | FileName:   OauthModel.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/10 15:51
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Small\Logic;

use Small\Logic\BaseLogic;
use Common\Controller\KdApiController as KdApi;

class KDLogic extends BaseLogic
{
    public $limit='0,10';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 获取快递信息
     * @param  $kdSn_arr 快递单号信息
     *
     */
    public function KDList($kdSn_arr){
        $time=3600;//缓存时间
        $return_data=[];
        //缓存中获取
        $key=$this->getKDKeyCache(implode(',',$kdSn_arr));
        if($return_data=json_decode(S($key),true)){
            return ['error'=>0,'data'=>$return_data];
        }
        //数据库中获取，存入缓存

        //接口中获取，存入缓存，存入数据库
        $kdApd=\Common\Controller\KdApiController::getInit();
        foreach($kdSn_arr as $k=>$v){
            $kd = KdApi::getInit();
            $kdArr= $kd::getKdCodeByKdNum( $v, false );

            $return_data[$v]=$kdApd->getOrderTracesByJson( $kdArr['kdCode'],$v,  '' );
            $return_data[$v]=json_decode($return_data[$v],true);
        }

        S($key,json_encode($return_data),$time);
        return ['error'=>0,'data'=>$return_data];
    }

    /**
     * @desc 获取快递信息
     * @param  kdSn_arr 快递单号信息
     *
     */
    public function getKDKeyCache($kdSn_arr,$key='daxin_kd'){
        return md5($kdSn_arr.$key);
    }


}