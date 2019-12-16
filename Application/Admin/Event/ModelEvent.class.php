<?php

// +----------------------------------------------------------------------
// | FileName:   ProductController.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2018/5/09 10:00
// +----------------------------------------------------------------------
// | Author: kelly <466395102@qq.com>
// +----------------------------------------------------------------------
namespace  Admin\Event;


interface ModelEvent{
    /**
     * 取出数据从this->list中
     * @param
     * @return $this
     */
    function toList(); //取出数据

    /**
     * 数据格式化
     * @ param array $name[0=>类型，1=>索引key] 格式化类型
     *     @example [0=>index_arr,1=>id]
     * @ return $this
     */
    function toType(); //数据格式化

    function toOrderInfo(); //订单数据

    function toOrderType(); //订单数据格式化

    function toUpdate(); //更新定单数据
}
