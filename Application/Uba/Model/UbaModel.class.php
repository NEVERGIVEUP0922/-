<?php
// +----------------------------------------------------------------------
// | FileName:   UbaModel.class.php
// +----------------------------------------------------------------------
// | Dscription:   Uba  扩展MongoDB操作类库
// +----------------------------------------------------------------------
// | Date:  2018-03-08 18:00
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Model;

use Think\Model\MongoModel;

class UbaModel extends MongoModel
{
	//数据库配置
	protected $connection = 'DB_MONGO';

	protected $lookUpData = [];

	protected $matchData = [];

	protected $sortData = [];

	protected $limitData = [];

	protected $asNameList = [];

	protected $aCount;


	/**
	 * 命令行式 获取唯一值
	 * @access public
	 * @return array | false
	 */
	public function distinct($field, $where=array() ){
		// 分析表达式
		$this->options =  $this->_parseOptions();
		$this->options['where'] = array_merge((array)$this->options['where'], $where);

//		$command = array(
//			"distinct" => $this->options['table'],
//			"key" => $field,
//			"query" => $this->options['where']
//		);

		$result = $this->db->distinct($field, $this->options['where']);

		return $result ? $result : false;
	}

	public function getBaseWhere( $startTime, $endTime )
	{
		$startTime           = is_timestamp( $startTime ) ? $startTime : strtotime( $startTime );
		$endTime             = is_timestamp( $endTime ) ? $endTime : strtotime( $endTime );
		$where[ 'visit_ts' ] = [ '$gte' => (string)$startTime, '$lte' => (string)$endTime ];

		return $where;
	}

	public function getCount( $where, $fields = '*', $isDistinct = false )
	{
		if ( $isDistinct && $fields ) {
			if ( $fields !== '*' ) {
				$res = $this->distinct( $fields, $where );
				if( !$res ){
					return 0;
				}else{
					return count($res);
				}

			} else {
				E( 'distinct查询时 fields 参数不能为 * ' );
			}
		}

		return $this->field( $fields )->where( $where )->count();
	}

	/**
	 * aggregate LookUp 类似Mysql JOIN 关联操作
	 *
	 * @access public
	 *
	 * @param string $joinTableName 加入的表名
	 * @param string $localField    与加入的表关联的字段
	 * @param string $foreginField  加入的表中被关联的字段
	 * @param string $asName        查询的关联数据集合的名称
	 *
	 * @return $this
	 */
	public function aJoin( $joinTableName, $localField, $foreginField, $asName )
	{
		$this->lookUpData[] = [ '$lookup' => [
			'from'         => $joinTableName,
			'localField'   => $localField,
			'foreignField' => $foreginField,
			'as'           => $asName,
		],
		];
		$this->asNameList[] = $asName;

		return $this;
	}

	/**
	 * aggregate match2 分组求和,仿照下面aMatch但不能同时使用),轨迹专用
	 */
	public function aMatch2($where)
	{
		$this->matchData[] = $where;
		return $this;
	}

	/**
	 * aggregate match 筛选聚合
	 *
	 * @access public
	 * @param string|array $field    被筛选字段
	 * @param string $exp      筛选条件(比较条件)
	 * @param void   $expValue 比较的数值/条件
	 * @return $this
	 */
	public function aMatch( $field, $exp = null, $expValue = null )
	{
		$match = [];
		if( is_array( $field ) ){
			if(empty( $match[ '$match' ] )){
				$match[ '$match' ] = $field;
			}else{
				$match[ '$match' ]  = array_merge( $match[ '$match' ] , $field );
			}
		}else{
			if( $field && empty( $exp ) && empty($expValue) ){
				E('field 参数不是数组时! 其他参数为必填');
				return false;
			}
			if ( $field === '_id' || ( strpos( $field, '_id' ) !== false ) ) {
				$expValue = new \MongoId( $expValue );
			}
			if ( $exp === '=' ) {
				$match[ '$match' ][ $field ] = $expValue;
			} else {
				$match[ '$match' ][ $field ] = [ $exp => $expValue ];
			}
		}
		$this->matchData[] = $match;


		return $this;
	}


	public function aCount($is = false)
	{
		$this->aCount = $is ;
		return  $this;
	}

	/**
	 * aggregate Sort 排序
	 *
	 * @access public
	 * @param string|array    $field 排序字段
	 * @param int|array $sort  排序规则 默认1正序排序  -1 倒序排序  元数据排序 [ '$meta'=>'' ]
	 * @return $this
	 */
	public function aSort( $field, $sort = 1 )
	{
		if(is_array( $field ) ){
			$this->sortData[]= ['$sort'=>$field];
		}else{
			$this->sortData[] =['$sort'=>[$field =>$sort]];
		}
		return $this;
	}

	/**
	 * aggregate Limit
	 *
	 * @access public
	 * @param int $skip 跳过数量
	 * @param int $size 显示数量
	 * @return $this
	 */
	public function aLimit( $skip = 0, $size = 0 )
	{
		$skip = intval($skip);
		$size = intval($size);
		if ( $skip && $size === 0 ) {
			$this->limitData[]= [ '$limit'=>$skip ];
		} elseif ( $skip === 0 && $size ) {
			$this->limitData[]= [ '$limit'=>$size ];
		} elseif ( $skip && $size ) {
			$skip = (intval($skip)-1)*intval($size);
			$this->limitData[]= ['$skip'=>$skip];
			$this->limitData[]= [ '$limit'=>$size ];
		}

		return $this;
	}


	/**
	 * aggregate Select 执行聚合查询
	 *
	 * @access public
	 * @param bool $isCoverId 是否自动转换数据结果中的 MongId(Object)字段为 字符串(String)格式
	 * @return array $data
	 */
	public function aSelect( $isCoverId = false )
	{
		$pipeline = [];
		//加入表
		if ( !empty( $this->lookUpData ) ) {
			$pipeline = $this->lookUpData;
		}
		//筛选
		if ( !empty( $this->matchData ) ) {
			$pipeline = array_merge( $pipeline, $this->matchData );
		}
		//排序
		if ( !empty( $this->sortData ) ) {
			$pipeline = array_merge( $pipeline, $this->sortData );
		}
		//数量Limit
		if ( !empty( $this->limitData ) ) {
			$pipeline = array_merge( $pipeline, $this->limitData );
		}

		if( $this->aCount ){
			$pipeline[] = ['$count'=>'agg_sum_count'];
		}
		$result = $this->aggregate( $pipeline );
		$data   = [];
		if ( (int)$result[ 'ok' ] !== 0 ) {
			unset( $result[ 'cursor' ][ 'id' ] );
			unset( $result[ 'cursor' ][ 'ns' ] );
			foreach ( $result[ 'cursor' ] as $key => $value ) {
				foreach ( $value as $k => $v ) {
					foreach ( $v as $item => $da ) {
						if ( in_array( $item, $this->asNameList ) && count( $da ) === 1 ) {
							$value[ $k ][ $item ] = $da[ 0 ];
						}
					}
					$data[] = $value[ $k ];
				}
			}
		}
		$data = $isCoverId ? $this->coverObject( $data ) : $data;

		return $data;
	}

	/**
	 * 聚合接口
	 *
	 * @access public
	 * @param array $pipeline aggregate MongoCode
	 * @param array $option   aggregate option
	 * @return array
	 */
	public function aggregate( $pipeline, $option = [] )
	{
		$option = $this->_parseOptions( $option );
		$option['cursor'] = (object)[];
		return $this->db->aggregate( $pipeline, $option );
	}

	/**
	 * 将数据中的id对象转为字符串
	 *
	 * @access protected
	 * @param array  $data 转换数据数组
	 * @param string $key  指定MongId(Object)类型字段的键名  默认为'_id'
	 *
	 * @return array $data
	 */
	protected function coverObject( &$data, $key = '_id' )
	{
		foreach ( $data as $k => $v ) {
			if ( is_object( $v ) && $v instanceof \MongoId ) {
				$data[ $k ] = $v->__toString();
			} elseif ( is_array( $v ) && !empty( $v ) ) {
				$data[ $k ] = $this->coverObject( $v );
			}
		}

		return $data;
	}
}