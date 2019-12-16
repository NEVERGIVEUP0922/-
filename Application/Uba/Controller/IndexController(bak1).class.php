<?php
// +----------------------------------------------------------------------
// | FileName:   IndexController.class.php
// +----------------------------------------------------------------------
// | Dscription:   用户行为分析系统
// +----------------------------------------------------------------------
// | Date:  2018-02-26 11:26
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Controller;

use EES\Controller\NoticeController;
use EES\System\Redis;
use Think\Controller;
use Uba\Inter\ModuleFilter;
use Uba\Mdu\ProductMdu;
use Uba\Model\UbaVisitLogModel;

class IndexController extends Controller
{

	protected $date;

	protected $hour;

	protected $data;//数据

	public $logModel;

	// 数据库连接参数配置
	protected $config = [
		'type'           => '',     // 数据库类型
		'hostname'       => '127.0.0.1', // 服务器地址
		'database'       => '',          // 数据库名
		'username'       => '',      // 用户名
		'password'       => '',          // 密码
		'hostport'       => '',        // 端口
		'dsn'            => '', //
		'params'         => [], // 数据库连接参数
		'charset'        => 'utf8',      // 数据库编码默认采用utf8
		'prefix'         => '',    // 数据库表前缀
		'debug'          => false, // 数据库调试模式
		'deploy'         => 0, // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
		'rw_separate'    => false,       // 数据库读写是否分离 主从式有效
		'master_num'     => 1, // 读写分离后 主服务器数量
		'slave_no'       => '', // 指定从服务器序号
		'db_like_fields' => '',
	];

	public function _initialize()
	{
		$this->date     = date( 'Y-m-d' );
		$this->hour     = date( 'H' );
		$this->logModel = new UbaVisitLogModel();
	}

	public function index()
	{
		$where['link_params'] = ['neq',''];
		$where['model_name'] = 'shop_product';
//		$where['uba_id'] = '2130706433a26b0105cf58780';
//		$where['vid'] = '520504904588c820';
		$res = $this->logModel->where( $where )->select();
		de( $res );
		de($this->logModel->where(['link_params.sign'=>'NS4165 ESOP8','uba_id'=>'2130706433a26b0105cf58780','vid'=>'b562cb208f861b69'])->order('visit_ts desc')->select());
		ModuleFilter::register('product', new ProductMdu());
		ModuleFilter::distory('product');
		die();
//        $config = array_merge( $this->config, C( 'DB_MONGO' ) );
//        $host = 'mongodb://' . ( $config[ 'username' ] ? "{$config['username']}" : '' ) . ( $config[ 'password' ] ? ":{$config['password']}@" : '' ) . $config[ 'hostname' ] . ( $config[ 'hostport' ] ? ":{$config['hostport']}" : '' ) . '/' . ( $config[ 'database' ] ? "{$config['database']}" : '' );
//        try {
//            $db = new \mongoClient( $host, [] );
//        } catch ( \MongoConnectionException $e ) {
//            E( $e->getmessage() );
//        }
//        $c = $db->selectDB( "uba" )->selectCollection( "dx_visit_log" );
		
		
		$e = $this->logModel
			->aJoin( 'dx_visit_links', 'link_id', '_id', 'link_data' )
			->aJoin( 'dx_visit_reffer', 'reffer_id', '_id', 'reffer_data' )
			->aJoin( 'dx_visit_client', 'client_id', '_id', 'client_data' )
			->aSelect( 1 );
		dd( $e );
		de( $this->logModel->_sql() );
	}
	
	public function getLogList()
	{
		$req = I();
		$sort = $req['sort']?$req['sort']:'';
		$page = $req['page']?intval($req['page']):1;
		$limit = $req['limit']?intval($req['limit']):20;
		$where = [];
		$sortF = explode('-',$sort);
		if( count($sortF) === 2 ){
			$sort = [ $sortF[0]=>$sortF[1] ];
		}

		$res = $this->logModel->getLogListData( $where, $sort, $page, $limit );
		return $res;
	}

	public function task()
	{
		$c      = TaskController::getInstance();
		$config = [
			'url'      => 'http://www.longicmall.com'  . U( 'Home/Default/index' ),
			'hreaders' => [],
			'randIp'   => true,
			'randUa'   => true,
		];
		$c->run( $config, 10 );
	}

	protected function dateFormatArray( $date, $format = '' )
	{
		if ( is_timestamp( $date ) ) {
			$dateTimeStamp = $date;
		} else {
			$dateTimeStamp = strtotime( $date );
		}
		if ( $format ) {
			return date( $format, $dateTimeStamp );
		} else {
			return [
				'y' => date( 'Y', $dateTimeStamp ),
				'm' => date( 'm', $dateTimeStamp ),
				'd' => date( 'd', $dateTimeStamp ),
				'h' => date( 'H', $dateTimeStamp ),
				'i' => date( 'i', $dateTimeStamp ),
				's' => date( 's', $dateTimeStamp ),
			];
		}
	}
}