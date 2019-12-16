<?php

// +----------------------------------------------------------------------
// | FileName:   config.php
// +----------------------------------------------------------------------
// | Dscription:    后台配置文件
// +----------------------------------------------------------------------
// | Date:  2017/7/31 14:29
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------


define('__STATIC_MODULE__', 'Admin');

return [
    'URL_MODEL' => 1,
	'HOME_LOGIN_FALSE_CHECK'    => 1, //是否开启登录错误次数限制,默认1为开启,0为关闭
	'HOME_LOGIN_FALSE_MAX'  => 5, //登录最大错误次数 默认为5次
	'HOME_LOGIN_FALSE_TIME' => 900, //登录超过错误次数限制时,账号冻结时间,单位为秒,默认900秒=15分钟
	
	'TMPL_ACTION_ERROR'     =>  SITE_PATH.'Application/Admin/View/dispatch_jump.html', // 默认错误跳转对应的模板文件
	'TMPL_ACTION_SUCCESS'   =>   SITE_PATH.'Application/Admin/View/dispatch_jump.html', // 默认成功跳转对应的模板文件

    'PAGE_PAGESIZE'=>10,//分页条目

    'PAY_TYPE'=>[ //支付方式
        1=>'在线支付',
        2=>'账期支付',
        3=>'快递代收',
        4=>'面对面付款',
        5=>'银行转账',
        6=>'线下支付',
        100=>'erp确认支付',
    ],

    'ADMIN_TOKEN'=>123456,//入口token

    'TABLE_NAME_ABBREVIATION'=>[//表名简称,对前端的
        'd_p_fi'=>'dx_product_fitemno'
    ]


];