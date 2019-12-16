<?php

// +----------------------------------------------------------------------
// | FileName:   ProductModel.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/7 12:47
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace  Wallet\Model;

use \Admin\Model\BaseModel;


class EntryModel extends \Admin\Model\BaseModel {

    protected $tablePrefix='wa_';
    protected $listCount=0;//查询数据总条目

    protected function _initialize(){
        parent::_initialize();
    }

    /**
     * @desc Home调用
     *
    */
    public function toHomeModule($action,$arguments){
        return $this->$action($arguments);
    }

    /**
     * @desc 定期理财时间
     *
     */
    public function regularEndTime($wallet_rule_type){
        $end_time='';
        switch ($wallet_rule_type){
            case 33://定期三个月
                $end_time=date('Y-m-d 00:00:00',strtotime('+3 month +1 day'));
                break;
            case 36://定期六个月
                $end_time=date('Y-m-d 00:00:00',strtotime('+6 month +1 day'));
                break;
            case 42://定期一年
                $end_time=date('Y-m-d 00:00:00',strtotime('12 month +1 day'));
                break;
        }
        return $end_time;
    }

    /**
     * @desc 定期理财利息
     *
     */
    public function regularInterest($wallet_rule_type,$amount,$interest){
        $interest_amount=0;
        switch ($wallet_rule_type){
            case 33://定期三个月
                $interest_amount=$interest*$amount*3/12;
                break;
            case 36://定期六个月
                $interest_amount=$interest*$amount*6/12;
                break;
            case 42://定期一年
                $interest_amount=$interest*$amount;
                break;
        }
        return (int)($interest_amount*100);
    }



}