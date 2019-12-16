<?php
// +----------------------------------------------------------------------
// | FileName:   UbaVisitTimeTraceModel.class.php
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date:  2018-03-07 11:36
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Model;

class UbaVisitTimeTraceModel extends UbaModel
{
    protected $tableName = 'visit_time_trace';

    protected $dbName = 'uba';

    public function saveLog( $data )
    {
        //数据过滤检查
        $d = [
            'vid'              => '',
            'client_id'        => '',
            'visit_url'        => '',
            'visit_count'      => 0,
            'visit_ts'         => 0,
            'load_ts'          => 0,            //从开始至load总耗时
            'ready_start_ts'   => 0,      //准备新页面时间耗时
            'redirect_ts'      => 0,        //redirect 重定向耗时
            'apache_ts'        => 0,          //Appcache 耗时
            'unload_event_ts'  => 0,    // unload 前文档耗时
            'lookup_domain_ts' => 0,   //DNS 查询耗时
            'connent_ts'       => 0,         //TCP连接耗时
            'request_ts'       => 0,         //request请求耗时
            'init_dom_tree_ts' => 0,   //请求完毕至DOM加载
            'dom_ready_ts'     => 0,       //解析dom树耗时
            'load_event_ts'    => 0,      //load事件耗时
        ];
        $data = array_merge( $d, $data );
        if( !is_timestamp($data['visit_ts']) ){

            $data['visit_ts'] = strtotime($data['visit_ts']);
        }
        $addId = $this->add( $data );

        return $addId ? $addId->__toString() : $addId;
    }
}