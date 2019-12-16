<?php
// +----------------------------------------------------------------------
// | FileName:   UbaVisitLogModel.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2018-02-28 18:08
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Model;


class UbaVisitLogModel extends UbaModel
{
	protected $tableName = 'visit_log';
	
	protected $dbName = 'uba';
	
	//访问数据模型预定义
	protected $logModule = [
		'uba_id'        => '',//新老访客用户唯一标识码
		'vid'           => '',//本次访问会话标识码
		'user_id'       => '',//本次会话登录用户id
		'link_url'		=> '',//访问页面链接URl
		'link_id'       => '',//本次访问页面的URL纪录id标识
		'link_info'		=> '',//访问页面链接详细数据
		'reffer_url'	=> '',//来源页面链接URl
		'reffer_id'     => '',//本次访问页面来源页面URL纪录id标识
		'reffer_info'	=> '',//来源页面链接详细数据
		'first_ts'      => 0,//本次会话访问开始时间
		'last_ts'       => 0,//本次会话上次访问时间
		'visit_ts'      => 0,//本次会话本次访问时间
		'stay_ts'       => 0,//本次访问页面停留时间
		'visit_count'   => 1,//本次会话本次访问次数标识
		'refresh_count' => 0,//本次会话本次访问页面刷新次数
		'refresh_list'  => '',//本次会话本次访问页面刷新时的访问次数标识列表
		'client_id'     => 0,//本次访问客户客户端标识ID
		'ip'            => '',//本次访问客户客户端IP地址
		'trace_event'   => '',//本次访问页面触发事件操作纪录
		'create_time'   => 0,//本次访问纪录创建时间
	];

	//数据模型中需被转换时间的字段
	protected $needCoverTimeFields = [ 'first_ts', 'last_ts', 'visit_ts', 'create_time' ];
	
	protected $needCoverToObjectIdFields = [ 'link_id', 'reffer_id', 'client_id' ];
	
	//数据模型初始化
	public function logModuleInit( $data )
	{
		if ( $data ) {
			$data = array_store_str(array_merge( $this->logModule, $data ));
			return $this->AutoIdFieldsCoverToObject( $this->AutoTimeFieldsFormat( $data ) );
		}
	}
	
	
	//数据中时间字段统一格式化 统一使用时间戳
	public function AutoTimeFieldsFormat( $data, $needCoverTimeFields = [] )
	{
		if ( empty( $needCoverTimeFields ) ) {
			$needCoverTimeFields = $this->needCoverTimeFields;
		}
		foreach ( $data as $key => $value ) {
			if ( in_array( $key, $needCoverTimeFields ) ) {
				$data[ $key ] = is_timestamp( $value ) ? $value : strtotime( $value );
			}
		}
		
		return $data;
	}
	

	public function AutoIdFieldsCoverToObject( $data, $needCoverToObjectIdFields = [] )
	{
		if ( empty( $needCoverTimeFields ) ) {
			$needCoverToObjectIdFields = $this->needCoverToObjectIdFields;
		}
		if ( !empty( $data ) && $this->_idType == self::TYPE_OBJECT ) {
			foreach ( $data as $key => $value ) {
				if ( in_array( $key, $needCoverToObjectIdFields ) && !empty( $value ) ) {
					$data[ $key ] = new \MongoId( $value );
				}
			}
		}
		
		return $data;
	}

	//==========轨迹统计(kk)=====
	//获取日志列表数据
	public function traceData( $where ,$single=false)
	{
		$list = [];
		$i =3;
		while($i>=1&&$i<=3){
			$this->matchData=[];
			if($where && $single == true){
				if($where['user_id']){
					$match['$match']['user_id']= $where['user_id'];
				}elseif($where['uba_id']){
					$match['$match']['uba_id']= $where['uba_id'];
				}
			}
			if($i==3){
				$match['$match']['create_time'] = ['$gte'=>strtotime('-15 day'),'$lt'=>strtotime("now")];
				$this->aMatch2( $match );
				$group['$group'] =[
					'_id'=>['link_name'=>'$link_name'],
					'num_time'=>['$sum'=>'$stay_ts']
				];
				$this->aMatch2($group);
				$rez = $this->aSelect( 0 );
				$list[15] = $rez;
			}elseif($i==2){
				$match['$match']['create_time'] = ['$gte'=>strtotime('-30 day'),'$lt'=>strtotime("now")];
				$this->aMatch2( $match );
				$group['$group'] =[
					'_id'=>['link_name'=>'$link_name'],
					'num_time'=>['$sum'=>'$stay_ts']
				];
				$this->aMatch2($group);
				$rez = $this->aSelect( 0 );
				$list[30] = $rez;
			}elseif($i==1){
				$match['$match']['create_time'] = ['$gte'=>strtotime('-30 day'),'$lt'=>strtotime("now")];
				$this->aMatch2( $match );
				$group['$group'] =[
					'_id'=>['link_name'=>'$link_name'],
					'num_time'=>['$sum'=>'$stay_ts']
				];
				$this->aMatch2($group);
				$rez = $this->aSelect( 0 );
				$list[90] = $rez;
			}
			$i--;
		}
		return $list;
	}

	//==========统计=============
	
	//获取日志列表数据
	public function getLogListData( $where = [], $sort, $page, $limit )
	{
		$this->aJoin( 'dx_visit_links', 'link_id', '_id', 'link_data' )->aJoin( 'dx_visit_reffer', 'reffer_id', '_id', 'reffer_data' )->aJoin( 'dx_visit_client', 'client_id', '_id', 'client_data' );

		if ( $where ) {
			$this->aMatch( $where );
		}
		empty( $sort ) ? [] : $this->aSort( $sort );
		$this->aLimit( $page, $limit );
		$list = $this->aSelect( 1 );
		$countData = $this->getAggrSumCount( $where );
		$list = array_merge( $list, $countData[ 0 ] );

		return $list;
	}
	
	//查询搜索条件的总记录数
	protected function getAggrSumCount( $where )
	{
		$m = new self();
		$m->aJoin( 'dx_visit_links', 'link_id', '_id', 'link_data' )->aJoin( 'dx_visit_reffer', 'reffer_id', '_id', 'reffer_data' )->aJoin( 'dx_visit_client', 'client_id', '_id', 'client_data' );
		
		if ( $where ) {
			$m->aMatch( $where );
		}
		
		return $m->aCount( true )->aSelect();
	}
	
	public function getPvCountByDate( $startTime, $endTime )
	{
		$where = $this->getBaseWhere( $startTime, $endTime );
		
		return $this->getCount( $where, '_id' );
	}
	
	public function getUvCountByDate( $startTime, $endTime )
	{
		$where = $this->getBaseWhere( $startTime, $endTime );
		$count = $this->getCount( $where, 'vid', true );
		
		return $count;
	}
	
	public function getIpCountByDate( $startTime, $endTime )
	{
		$where = $this->getBaseWhere( $startTime, $endTime );
		
		return $this->getCount( $where, 'ip', true );
	}
	
	
	//=========纪录与更新================
	
	//写入页面刷新操作处理
	public function saveRefresh( $data )
	{
		$data = $this->AutoIdFieldsCoverToObject( $this->AutoTimeFieldsFormat( $data ) );
		
		return $this->where( [
			'uba_id'      => $data[ 'uba_id' ],
			'vid'         => $data[ 'vid' ],
			'user_id'     => $data[ 'user_id' ],
			'client_id'   => $data[ 'client_id' ],
			'link_id'     => $data[ 'link_id' ],
			'visit_count' => $data[ 'visit_count' ],
			'first_ts'    => $data[ 'first_ts' ],
			'reffer_id'   => $data[ 'reffer_id' ],
		] )->save( [
			'refresh_count' => $data[ 'refresh_count' ],
			'refresh_list'  => $data[ 'refresh_list' ],
			'visit_ts'      => $data[ 'visit_ts' ],
		] ) !== false ? true : false;
	}
	
	//写入页面访问纪录日志数据
	public function saveVisitLog( $data )
	{
		$data = $this->logModuleInit( $data );
		$data[ 'create_time' ] = (int)time();
		
		//如果有访问纪录则修改所有纪录-----已弃用
		$add = $this->add( $data );
		return $add ? $add->__toString() : $add;
	}
	
	//获取上一个页面的访问数据纪录
	public function getPrevVisitLog( $data )
	{
		$data = $this->AutoIdFieldsCoverToObject( $this->AutoTimeFieldsFormat( $data ) );
		
		return $lastVisitData = $this->where( [
			'uba_id'    => $data[ 'uba_id' ],
			'vid'       => $data[ 'vid' ],
			'user_id'   => $data[ 'user_id' ],
			'client_id' => $data[ 'client_id' ],
			'first_ts'  => $data[ 'first_ts' ],
			'visit_ts'  => $data[ 'last_ts' ],
		] )->find();
	}
	
	//写入页面关闭或刷新操作处理
	public function saveUnload( $data )
	{
		//格式化数据中的时间 及 ID数据
		$data = $this->AutoIdFieldsCoverToObject( $this->AutoTimeFieldsFormat( $data ) );
		$where = [
			'uba_id'      => $data[ 'uba_id' ],
			'vid'         => $data[ 'vid' ],
			'user_id'     => $data[ 'user_id' ],
			'visit_count' => $data[ 'visit_count' ],
			'client_id'   => $data[ 'client_id' ],
			'first_ts'    => $data[ 'first_ts' ],
		];
		$trace_event = json_decode( format_ErrorJson( $data[ 'trace_event' ] ), true );
		$linkRes = $this->where( $where )->find();
		if ( !$linkRes ) {
			//不存在纪录  但存在已被的刷新列表
			$refreshedData = $this->checkIsRefreshed( $data );
			if ( $refreshedData ) {
				$stay_ts = (int)$refreshedData[ 'stay_ts' ] + (int)$data[ 'stay_ts' ];
				if ( !empty( $linkRes[ 'trace_event' ] ) ) {
					$log_trace_event = object2array( $refreshedData[ 'trace_event' ] );
				} else {
					$log_trace_event = '';
				}
				if ( !empty( $trace_event ) ) {
					foreach ( $trace_event as $value ) {
						array_push( $log_trace_event, $value );
					}
					$log_trace_event = array2object( $log_trace_event );
				}
				
				return $this->where( [
					'uba_id'      => $data[ 'uba_id' ],
					'vid'         => $refreshedData[ 'vid' ],
					'user_id'     => $refreshedData[ 'user_id' ],
					'client_id'   => $refreshedData[ 'client_id' ],
					'first_ts'    => $refreshedData[ 'first_ts' ],
					'visit_count' => $refreshedData[ 'visit_count' ],
				] )->save( [ 'stay_ts' => $stay_ts, 'trace_event' => $log_trace_event ] );
			}
			
			return false;
		} else {
			$stay_ts = (int)$linkRes[ 'stay_ts' ] + (int)$data[ 'stay_ts' ];
			if ( !empty( $linkRes[ 'trace_event' ] ) ) {
				$log_trace_event = object2array( $linkRes[ 'trace_event' ] );
			} else {
				$log_trace_event = [];
			}
			if ( !empty( $trace_event ) ) {
				foreach ( $trace_event as $value ) {
					array_push( $log_trace_event, $value );
				}
				$log_trace_event = array2object( $log_trace_event );
			}
			$where[ 'link_id' ] = $linkRes[ 'link_id' ];
			
			return $this->where( $where )->save( [ 'stay_ts'     => $stay_ts,
												   'trace_event' => $log_trace_event,
			] ) !== false ? true : false;
		}
		
	}
	
	//检查页面是否是刷新操作
	protected function checkIsRefreshed( $data )
	{
		$data = $this->AutoIdFieldsCoverToObject( $this->AutoTimeFieldsFormat( $data ) );
		$where = [
			'uba_id'       => $data[ 'uba_id' ],
			'vid'          => $data[ 'vid' ],
			'user_id'      => $data[ 'user_id' ],
			'client_id'    => $data[ 'client_id' ],
			'first_ts'     => $data[ 'first_ts' ],
			'refresh_list' => [ 'like', ',' . $data[ 'visit_count' . ',' ] ],
		];
		$isEx = $this->where( $where )->find();
		
		return $isEx ? $isEx : false;
	}
	
	//检查页面访问纪录数据是否存在
	public function checkIsVisitExists( $data )
	{
		$data = $this->AutoIdFieldsCoverToObject( $this->AutoTimeFieldsFormat( $data ) );
		$where = [
			'uba_id'      => $data[ 'uba_id' ],
			'vid'         => $data[ 'vid' ],
			'user_id'     => $data[ 'user_id' ],
			'client_id'   => $data[ 'client_id' ],
			'first_ts'    => $data[ 'first_ts' ],
			'visit_count' => $data[ 'visit_count' ],
		];
		$isEx = $this->where( $where )->find();
		
		return $isEx ? $isEx : false;
	}
}