<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-12-29
 * Time: 11:15
 */
namespace EES\Event;

class EventFactory
{
    public static function getEventService($action_category)
    {
        $category = strpos($action_category,'_' ) !== false?strstr($action_category, '_',true):$action_category;
        $eventServiceName = __NAMESPACE__.'\\'.ucfirst($category ) . 'Event';
        if (!file_exists(APP_PATH.'/EES/Event/'.ucfirst($category ).'Event.class.php')) {
            return false;
        }else{
            $obj = new $eventServiceName();
            return $obj;
        }
    }
}