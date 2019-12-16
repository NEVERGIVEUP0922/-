<?php

// +----------------------------------------------------------------------
// | FileName:   ProductController.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2018/5/15 10:21
// +----------------------------------------------------------------------
// | Author: kelly <466395102@qq.com>
// +----------------------------------------------------------------------
namespace  Admin\Event;


class PoweractionEvent
{
    protected $power;
    public $where;

    /**
     * @param PowerEvent $ObPower
     * @return $this
     */
    public function setPower(PowerEvent $ObPower){
        $this->power=$ObPower;
        return $this;
    }

    /**
     * @param string $where
     * @return string
     */
    public function powerWhere($where=''){
        $this->power->getPowerWhere($where);
        $this->where=$this->power->where;
        return $this;
    }
}