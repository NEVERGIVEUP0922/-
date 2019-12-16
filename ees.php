<?php

// +----------------------------------------------------------------------
// | FileName:   admin.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/6 14:33
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

// 后台入口文件

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',True);


define('SITE_PATH', dirname(__FILE__)."/");
// 定义应用绝对目录
define( 'APP_PATH', SITE_PATH . 'Application/' );

//定义缓存存放路径
define( "RUNTIME_PATH", SITE_PATH . "Data/Runtime/" );

//定义自定义日志存储目录
define( 'MYLOG_PATH', SITE_PATH . 'Data/Log/' );

//定时任务存储目录
define( 'CRON_PATH', SITE_PATH . 'Data/Crontab/' );

//静态缓存目录
define( "HTML_PATH", SITE_PATH . "Data/Html/" );

//版本号
define("DXPHP_VERSION", '1.0.0');

//绑定后台模块
define('BIND_MODULE','EES');

// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';

// 亲^_^ 后面不需要任何代码了 就是如此简单