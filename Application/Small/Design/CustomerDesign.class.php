<?php
namespace Small\Design;
use Think\Controller;
class CustomerController extends Controller {

    protected static $objects;

    function set($alias,$object){
        slef::$objects[$alias]=$object;
    }

    static public function get_($name){
        return self::$objects[$name];
    }

    public function unsert_($alias){
        unset(self::$objects[$alias]);
    }

}