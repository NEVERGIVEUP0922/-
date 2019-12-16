<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 应用入口文件
//
// 检测PHP环境

if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',true);
define('SITE_PATH', dirname(__FILE__).DIRECTORY_SEPARATOR);
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

defined('API_TOKEN_KELLY') OR define('API_TOKEN_KELLY',654321);

//版本号
define("DXPHP_VERSION", '1.0.0');
//define('BIND_MODULE','Wallet');
//phpinfo();
// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';

// 亲^_^ 后面不需要任何代码了 就是如此简单\
