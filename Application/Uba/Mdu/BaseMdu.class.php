<?php
// +----------------------------------------------------------------------
// | FileName:   BaseMdu.class.php
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date:  2018-03-19 11:49
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Mdu;


use Uba\Inter\ModuleFilterInterFace;
use Uba\Model\UbaVisitLogModel;

class BaseMdu
{
	protected static $modules = [];
	
	protected static $moduleData = [];
	
	
	/**
	 * 注册统计模型
	 *
	 * @param string $name 模型名称 注: 注册的模型名称需要与日志log纪录中的model_name对应
	 * @param object $objClass 日志log纪录中的model_name对应的统计模型的实例化对象
	 * @exception
	 * @return bool true|false
	 **/
	public static function register($name,$objClass)
	{
		if( !( $objClass instanceof ModuleFilterInterFace ) ){
			E($objClass.'请注册正确的对象');
		}else{
			$obj = new $objClass;
			self::$modules[$name] = $obj;
			return true;
		}
		return false;
	}
	
	/**
	 * 卸载注销统计模型
	 *
	 * @param string $name 统计模型的注册名称
	 * @exception
	 * @return void
	 **/
	public static function distory( $name )
	{
		unset( self::$modules[$name] );
	}
	
	/**
	 * 检查模型是否已注册
	 * @param string $name 统计模型的注册名称
	 * @return bool
	 */
	public static function hasModule($name)
	{
		return isset(self::$modules[$name])?true:false;
	}
	
	/**
	 * 运行指定的统计模型进行统计分析处理
	 *
	 * @param string $name 注册模型名称
	 * @param int $page 分页页码
	 * @param int $limit 分页页大小
	 * @param date|timestamp  $startTime 查询开始时间
	 * @param date|timestamp  $endTime 查询结束时间
	 * @exception
	 * @return array
	 **/
	public static function runOne( $name,$page=1,$limit=10,$startTime='', $endTime='' )
	{
		if( empty( $startTime ) ){
			//默认为本月1号
			$startTime = strtotime(date('Y-m-01 H:i:s'));
		}
		if( empty( $endTime ) ){
			//默认为当前时间
			$endTime = time();
		}
		if( self::hasModule( $name ) ){
			self::resetModuleData( $name );
		}else{
			E($name.'对应分析模型未注册');
		}
		$startTime = coverToStimestamp( $startTime );
		$endTime = coverToStimestamp( $endTime );
		self::$moduleData[$name] = self::$modules[$name]->runStore((int)$page,(int)$limit,$startTime,$endTime);
		return self::$moduleData[$name];
	}
	
	public static function resetModuleData( $name='' )
	{
		if( $name ){
			self::$moduleData[$name] = [];
		}else{
			self::$moduleData = [];
		}
	
	}
	
	/**
	 * 获取已注册的某个模型
	 * @param string $name 统计模型名称
	 * @return object|false
	 */
	public function getModule( $name )
	{
		return isset(self::$modules[$name])?self::$modules[$name]:false;
	}
	
	/**
	 * 获取所有已注册的统计模型
	 * @return array
	 */
	public static function getModules()
	{
		return self::$modules;
	}
	
	/**
	 * 获取某个模型已分析完成的数据
	 * @param string $name 统计模型名称
	 * @return object|false
	 */
	public static function getModeleData( $name )
	{
		return isset( self::$moduleData[$name] ) && !empty( self::$moduleData[$name] )? self::$moduleData[$name]:[];
	}
	
	public function __autoload($class_name)
	{
		require_once $class_name. '.class.php';
	}
}