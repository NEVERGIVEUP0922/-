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

class SearchLogic extends BaseLogic
{
    public $limit='0,10';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 搜索
     *
     */
    public function searchHistory($request){

        $list=[];
        switch ($request['type']){
            case 'product':
                $list=D('Small/Product','Logic')->productSearchHistory($request);
                break;
        }

        return $list;
    }


}