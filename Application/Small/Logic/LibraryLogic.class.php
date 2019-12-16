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

class LibraryLogic extends BaseLogic
{
    public $limit='0,10';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 快递公司
     *
     */
    public function kdCompany($where,$limit=''){
        $limit=$limit?:$this->limit;
        $field='id,kd_name,kd_code';
        $result=M('kd_delivery')->field($field)->where($where)->limit($limit)->select();
        return ['error'=>0,'data'=>['list'=>$result]];
    }

    public function qrcode($url,$filePath,$level=3,$size=4)
    {
        Vendor('phpqrcode.phpqrcode');
        $errorCorrectionLevel =intval($level) ;//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        //生成二维码图片
        $object = new \QRcode();
        $qrcode=$object->png($url,$filePath, $errorCorrectionLevel, $matrixPointSize, 2);
    }


}