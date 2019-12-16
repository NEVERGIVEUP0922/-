<?php
// +----------------------------------------------------------------------
// | FileName:   UbaClientTraceModel.class.php
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date:  2018-02-28 16:38
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Model;


class UbaVisitClientModel extends UbaModel
{
    protected $tableName = 'visit_client';

    protected $dbName = 'uba';

    //存在就查询, 否则就写入
    public function saveOrSelectClient( $data )
    {
        $d = [
            'screen_height' => '',
            'screen_width' => '',
            'color_depth' => '',
            'language' => '',
            'browser_ua' => '',
            'browser_name' => '',
            'browser_ver' => '',
            'engine_name' => '',
            'engine_ver' => '',
            'os_name' => '',
            'os_ver' => '',
            'cookie_enabled'=>''
        ];
        $data = array_merge( $d, $data );
        //检查是否存在
        $clientEx = $this->checkExists( $data );
        if( $clientEx ){
            return  $clientEx['_id'];
        }else{
            $addId = $this->add( $data );

            return $addId?$addId->__toString():$addId;
        }
    }

    public function checkExists( $data )
    {
        return $this->where( $data )->find();
    }

}