<?php
// +----------------------------------------------------------------------
// | FileName:   TraceController.php
// +----------------------------------------------------------------------
// | Dscription:   接收记录数据
// +----------------------------------------------------------------------
// | Date:  2018-02-26 11:28
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Controller;

use Common\Controller\BaseController;
use Uba\Model\UbaVisitClientModel;
use Uba\Model\UbaVisitCountModel;
use Uba\Model\UbaVisitLinksModel;
use Uba\Model\UbaVisitLogModel;
use Uba\Model\UbaVisitRefferModel;
use Uba\Model\UbaVisitTimeTraceModel;
use Uba\Model\UbaVisitUbaIdModel;

class TraceController extends BaseController
{

	protected $logModel; //日志数据模型

	protected $countModel;//统计数据模型

	protected $linksModel;//访问链接数据模型

	protected $refferModel;//访问来源数据模型

	protected $ubaIdModel; //访客唯一标识模型

	protected $clientModel;//访客客户端信息数据模型

	protected $timeTraceModel;//访问页面耗时事件监控数据模型

	public function _initialize()
	{
        header( 'Accept:image/webp,image/apng,image/*,*/*;q=0.8' );
        header( 'Content-Type:image/png' );
		parent::_initialize();
		$this->logModel       = new UbaVisitLogModel();
		$this->countModel     = new UbaVisitCountModel();
		$this->linksModel     = new UbaVisitLinksModel();
		$this->refferModel    = new UbaVisitRefferModel();
		$this->ubaIdModel     = new UbaVisitUbaIdModel();
		$this->clientModel    = new UbaVisitClientModel();
		$this->timeTraceModel = new UbaVisitTimeTraceModel();
	}

	//浏览主体信息
	public function index()
	{
		$param = I();
		$mdu = [];
		if ( empty( $param[ 'uuid' ] ) && empty( $param[ 'cid' ] ) ) {
			return;
		}
		if ( !isset( $param[ 'cid' ] ) || empty( $param[ 'cid' ] ) || $param[ 'cid' ] === 'undefined' ) {
			$cid = session( $param[ 'uuid' ] . '_cid' );
			if ( $cid ) {
				$param[ 'cid' ] = $cid;
			}
		}
		//数据过滤检查
		$except = [
			'uuid'           => 'vid',//访客访次标识
			'firstVisitTs'   => 'first_ts',//本访次首次访问时间
			'currentVisitTs' => 'visit_ts',//当前本次访问时间
			'lastVisitTs'    => 'last_ts',//上次访问时间
			'visitCount'     => 'visit_count',//访问序列标识
			'url'            => 'link_url',//访问页面链接
			'title'          => 'link_name',//访问页面标题
			'referrer'       => 'reffer_url',//访问来源页面链接
			'ip'             => 'ip',//客户访问ip
			'cid'            => 'client_id',//客户客户端信息标识id
		];
		foreach ( $param as $key => $v ) {
			if ( array_key_exists( $key, $except ) ) {
				$param[ $except[ $key ] ] = $v;
			}
			unset( $param[ $key ] );
		}
		$param[ 'user_id' ] = is_login();
		$param[ 'ip' ]      = get_client_ip();
		
		//访问新老客户唯一标识码
		$param[ 'uba_id' ] = $this->createUbaUniqueId( $param[ 'client_id' ], $param[ 'ip' ] );
		//访问页面链接
		$param['link_url'] = $this->linkStore($param['link_url']);
		//访问链接参数及访问模块控制器操作的路径信息
		$param['link_info'] = uba_urlClean( $param['link_url'] );
		$param[ 'link_id' ] = $this->linksModel->getLinkId( $param[ 'link_info' ]->cle_url, $param[ 'link_name' ] );
		
		//来源页面链接
		if( !empty($param['reffer_url']) ){
			$param[ 'reffer_url' ] = $this->linkStore($param['reffer_url']);
			$param[ 'reffer_id' ] = $this->refferModel->getRefferLinkId( $param[ 'reffer_url' ] );
			$param['reffer_info'] = uba_urlClean( $param['reffer_url'] );
		}
		
		//写入访问数据
		$return = $this->logModel->saveVisitLog( $param );
		if ( $return ) {
			//统计计数
			$this->countUpdate( $param[ 'ip' ], $param[ 'vid' ] );
			
			//模型分析统计处理
			//返回的访问记录id
		}
		return $return;

	}
	
	//链接处理
	protected function linkStore( $links )
	{
		$urlCase = C('URL_CASE_INSENSITIVE');
		if( $urlCase ){
			$links = strtolower( $links );
		}
		return $links;
	}
	
	//数据统计更新
	protected function countUpdate( $ip, $vid )
	{
		//PV统计 每次访问都加一
		$this->countModel->updateCount( 'pv' );
		//IP统计 24小时内同一ip算一次
		if ( !$this->countModel->checkUvOrIpLogsExistsForToday( 'ip', $ip ) ) {
			$this->countModel->addUvOrIpLogs( 'ip', $ip );
			$this->countModel->updateCount( 'ip' );
		}
		//Uv统计 24小时内同一个访问标识码算一次
		if ( !$this->countModel->checkUvOrIpLogsExistsForToday( 'uv', $vid ) ) {
			$this->countModel->addUvOrIpLogs( 'uv', $vid );
			$this->countModel->updateCount( 'uv' );
		}
	}

	//生成访客用户唯一标识码
	protected function createUbaUniqueId( $client_id, $ip )
	{
		//生成用户唯一标识码( 通过用户的UA与IP标识生成 );
		$uba_id = bindec( decbin( ip2long( $ip ) ) ) . substr( base_convert( md5( $client_id ), 16, 16 ), 0, 15 );

		return $uba_id;
	}

	//检查是否是刷新页面(//)
	protected function checkIsRefresh( $data )
	{
		//先检查是否已存在
		$isEx = $this->logModel->checkIsVisitExists( $data );

		if ( $isEx ) {
			return $isEx;
		}
		$prevVisitData = $this->logModel->getPrevVisitLog( $data );
		if ( $prevVisitData[ 'link_id' ] === $data[ 'link_id' ] && $prevVisitData[ 'reffer_url' ] === $data[ 'reffer_url' ] ) {
			return $prevVisitData;
		}

		return false;
	}

	//访客刷新或关闭了浏览器
	public function beforeUnload()
	{
		$param = I();
		if ( empty( $param[ 'uuid' ] ) && empty( $param[ 'cid' ] ) ) {
			return;
		}
		if ( !isset( $param[ 'cid' ] ) || empty( $param[ 'cid' ] ) || $param[ 'cid' ] === 'undefined' ) {
			$cid = session( $param[ 'uuid' ] . '_cid' );
			if ( !empty( $cid ) ) {
				$param[ 'cid' ] = $cid;
			}
		}
		$except = [
			'uuid'         => 'vid',
			'cid'          => 'client_id',
			'vc'           => 'visit_count',
			'stayTs'       => 'stay_ts',
			'firstVisitTs' => 'first_ts',
			'tev'          => 'trace_event',
		];
		foreach ( $param as $key => $v ) {
			if ( array_key_exists( $key, $except ) ) {
				$param[ $except[ $key ] ] = $v;
			}
			unset( $param[ $key ] );
		}
		if ( empty( $param[ 'vid' ] ) && empty( $param[ 'visit_count' ] ) ) {
			return;
		}
		$user_id            = is_login();
		$param[ 'user_id' ] = $user_id;
		$param[ 'ip' ]      = get_client_ip();
		//访问新老客户唯一标识码
		$param[ 'uba_id' ] = $this->createUbaUniqueId( $param[ 'client_id' ], $param[ 'ip' ] );
		$this->logModel->saveUnload( $param );

		return true;
	}

	//客户端系统设备信息
	public function clientTracker()
	{
		$param  = I();
		$vid    = $param[ 'uuid' ];
		$except = [
			'sh'     => 'screen_height',
			'sw'     => 'screen_width',
			'cd'     => 'color_depth',
			'la'     => 'language',
			'ua'     => 'browser_ua',
			'bn'     => 'browser_name',
			'bnv'    => 'browser_ver',
			'engn'   => 'engine_name',
			'engv'   => 'engine_ver',
			'osn'    => 'os_name',
			'osv'    => 'os_ver',
			'cookie' => 'cookie_enabled',
		];
		foreach ( $param as $key => $value ) {
			if ( array_key_exists( $key, $except ) ) {
				$param[ $except[ $key ] ] = $param[ $key ];
			}
			unset( $param[ $key ] );
		}
		$res = $this->clientModel->saveOrSelectClient( $param );
		cookie( '_uba_ci', $res );
		session( $vid . '_cid', $res );
	}

	//弃用(文本存储)
	public function writeLog( $data )
	{
		//生成的文件名  按用户访问标识uuid
		if ( isset( $data[ 'uid' ] ) ) {
			$uid = $data[ 'uid' ];
		} else {
			$uid = cookie( '_uba_utmi' );
		}
		if ( !$data[ 'client_id' ] ) {
			$cid                 = session( $uid . '_cid' );
			$data[ 'client_id' ] = (int)$cid;
		}
		//生成用户唯一标识码( 通过用户的UA与IP标识生成 );
		$data[ 'unique' ] = bindec( decbin( ip2long( get_client_ip() ) ) ) . sprintf( '%09s', $data[ 'client_id' ] );
		//检查日志目录是否存在 不存在则创建 按时间
		$dir = $this->logPathDir . date( 'Y' ) . DS . date( 'm' ) . DS . date( 'd' ) . DS . date( 'H' ) . DS . $data[ 'unique' ];
		if ( !file_exists( $dir ) ) {
			mkDirectory( $dir );
		}
		$file = $dir . DS . $uid . '.' . $this->logExt;
		//检测日志文件大小，超过配置大小则日志文件重新生成
		if ( is_file( $file ) && floor( C( 'LOG_FILE_SIZE' ) ) <= filesize( $file ) ) {
			rename( $file, dirname( $file ) . '/' . pathinfo( __FILE__ )[ 'filename' ] . '-' . time() . $this->logExt );
		}

		file_put_contents( $file, json_encode( $data ) . $this->delimiter, FILE_APPEND );
	}

	//页面加载耗时性能监测
	public function timeTracker()
	{
		$param = I();
		if ( empty( $param[ 'uuid' ] ) && empty( $param[ 'cid' ] ) || empty( $param[ 'url' ] ) ) {
			return;
		}
		if ( !isset( $param[ 'cid' ] ) || empty( $param[ 'cid' ] ) || $param[ 'cid' ] === 'undefined' ) {
			$cid = session( $param[ 'uuid' ] . '_cid' );
			if ( $cid ) {
				$param[ 'cid' ] = (int)$cid;
			}
		}
		//数据过滤检查
		$except = [
			'uuid'             => 'vid',
			'cid'              => 'client_id',
			'url'              => 'visit_url',
			'vc'               => 'visit_count',
			'ts'               => 'visit_ts',
			'loadTime'         => 'load_ts',      		  //从开始至load总耗时
			'readyStart'       => 'ready_start_ts',       //准备新页面时间耗时
			'redirectTime'     => 'redirect_ts', 		  //redirect 重定向耗时
			'appcacheTime'     => 'apache_ts',           //Appcache 耗时
			'unloadEventTime'  => 'unload_event_ts',     // unload 前文档耗时
			'lookupDomainTime' => 'lookup_domain_ts',    //DNS 查询耗时
			'connectTime'      => 'connent_ts',          //TCP连接耗时
			'requestTime'      => 'request_ts',          //request请求耗时
			'initDomTreeTime'  => 'init_dom_tree_ts',    //请求完毕至DOM加载
			'domReadyTime'     => 'dom_ready_ts',        //解析dom树耗时
			'loadEventTime'    => 'load_event_ts',       //load事件耗时
		];

		foreach ( $param as $key => $v ) {
			if ( array_key_exists( $key, $except ) ) {
				$param[ $except[ $key ] ] = $v;
			}
			unset( $param[ $key ] );
		}

		$param[ 'ip' ] = get_client_ip();
		$this->timeTraceModel->saveLog( $param );
		return true;
	}
}