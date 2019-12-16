<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-01-10
 * Time: 14:32
 */
namespace EES\System;

class Extension
{
    public static function check()
    {
        $mustExtension = ['mysql','redis'];
        foreach( $mustExtension as $e ){
            if(!extension_loaded($e))
            {
                E( ucfirst($e).'扩展未安装!请检查是否已正确安装!' );
            }
        }
        return true;
    }
}