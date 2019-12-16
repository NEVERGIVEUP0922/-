<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-01-06
 * Time: 15:32
 */
namespace EES\System;

use Think\Log;

class Redis
{
    public static function getInstance( $ip = '', $port = '6379' , $pass='')
    {
        if( $ip && $port ){
            $config = [
                'ip'=>$ip,
                'port'=>$port,
                'pass'=>$pass
            ];
        }else{
            $config = C('SHOP_REDIS');
        }
        $redis = new \Redis();
        $rs = $redis->connect($config['ip'],$config['port']);
        //密码连接
        if( !empty( $config['pass'] ) ){;
            $redis->auth( $config['pass'] );
        }
        if( !$rs ){
            Log::write('REDIS: '.$config['ip'].':'.$config['port'].'连接失败');
            E( 'REDIS: '.$config['ip'].':'.$config['port'].'连接失败' );
        }
        return $redis;
    }
	
	
	/**
	 *
	 * @throws
	 **/
	public static function getPCInstance($ip = '', $port = '6379' , $pass='')
	{
		if( $ip && $port ){
			$config = [
				'ip'=>$ip,
				'port'=>$port,
				'pass'=>$pass
			];
		}else{
			$config = C('SHOP_REDIS');
		}
		$redis = new \Redis();
		$rs = $redis->pconnect($config['ip'],$config['port']);
		//密码连接
		if( !empty( $config['pass'] ) ){;
			$redis->auth( $config['pass'] );
		}
		if( !$rs ){
			Log::write('REDIS: '.$config['ip'].':'.$config['port'].'连接失败');
			E( 'REDIS: '.$config['ip'].':'.$config['port'].'连接失败' );
		}
		return $redis;
	}
}