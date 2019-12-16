<?php
// +----------------------------------------------------------------------
// | FileName:   ProductMdu.class.php
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date:  2018-03-15 10:52
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Mdu;

use Uba\Inter\ModuleFilter;
use Uba\Model\Mysql\ProductModel;
use Uba\Model\UbaVisitLinksModel;
use Uba\Model\UbaVisitLogModel;
use Uba\Model\UbaVisitRefferModel;

class ProductMdu extends ModuleFilter
{
	protected $pageModuleName = 'shop_product';
	
	protected $pageMca = 'home@product@detail';
	
	protected $modelData = [
		'uba_id'=>'',//访客唯一标识
		'vid'=>'',//访问访次标识
		'product_id'=> 0,//产品id,
		'product_sign'=>'',//产品商城型号
		'product_fitemno'=>'',//产品ERP型号
		'product_vm_no'=>'',//产品vm,
		'product_vm_name'=>'',
		'product_visit_count'=>0,//本访次查看产品次数
		'order_no'=>'',//购买时的订单单号
		'about_visit'=>[ //关联本访次访问日志记录列表
			[
				'log_id'=>'',//关联日志记录id
				'reffer_model_name'=>'',//产品访问来源模块标识 //首页 搜索
				'visit_ts'=>'',//访问时间
				'stay_ts'=>'',//停留时间
				'is_add_cart'=>0,//是否加入购物车
			],
						 //多个页面记录
		],
		'aboout_user'=>[
			'user_id'=>0,//用户id
			'user_type'=>0,//用户类型
			'user_cust_no'=>'',//用户关联ERP客户编号
			'user_cust_name'=>'',//用户关联ERP客户名称
			'user_sale_no'=>'',//用户所属业务员编号
			'user_sale_name'=>'',//用户所属业务员名称
		],
		'about_order_list'=>[
			[
				'order_no'=>'',//订单编号
				'order_pay_success'=>'',//是否支付成功
			]
			//多个订单
		],//本访次中购买了此产品的订单号
	];
	
	protected $productVisitLogs = [];
	
	
	protected $linkModel;
	protected $logModel;
	protected $refferModel;
	
	public function  __construct()
	{
		$this->linkModel = new UbaVisitLinksModel();
		$this->logModel = new UbaVisitLogModel();
		$this->refferModel = new UbaVisitRefferModel();
	}
	
	public function fetchData($page,$limit,$startTime,$endTime)
	{
		$logModel = new UbaVisitLogModel();
		$begin = ((int)$page-1)*((int)$limit);
		$where['first_ts'] = ['gte', (string)$startTime];
		$where['last_ts'] = ['lte', (string)$endTime];
		$where['link_info.mca'] = $this->pageMca;
		$where['link_info.params.sign'] = ['neq', ''];
		$res = $logModel->where( $where )->limit($begin,$limit)->select();
		return $res;
	}
	
	//进行处理
	public function runStore($page,$limit,$startTime,$endTime)
	{
		$data = $this->fetchData($page,$limit,$startTime,$endTime);
		$res = [];
		foreach( $data as $k=>$value ){
			
			$da = $this->visitLogStore($value);
			if( !$da ){
				continue;
			}
			$res[] = $da;
		}
		return $res;
	}
	
	
	//进入产品页访问纪录处理
	public function visitLogStore( $data )
	{
		$logKey = $data['uba_id'].'.'.$data['vid'].'.'.$data['first_ts'].'.'.$data['link_info']['params']['sign'];
		if( in_array($logKey, $this->productVisitLogs) ){
			return false;
		}
		//访客标识信息
		$modelData['uba_id'] = $data['uba_id'];
		$modelData['vid'] = $data['vid'];
		//分析产品 产品信息
		$productInfo = $this->getProductInfo( $data['link_info']['params']['sign'] );
		$modelData['product_sign'] = $data['link_info']['params']['sign'];//产品商城型号
		if( $productInfo ){
			$modelData['product_id'] = $productInfo['id'];//产品id,
			$modelData['product_fitemno'] = $productInfo['fitemno'];//产品ERP型号
			$modelData['product_vm_no'] = $productInfo['vm'];//产品vm编号
			$modelData['product_vm_name'] = $productInfo['vm_name'];//产品vm名称
		}else{
			return false;
		}
		//分析同访次访客查看此产品数量
		$modelData['product_visit_count'] = $this->getVisiorVisitCount( $modelData['uba_id'],$modelData['vid'],$data['first_ts'],$modelData['product_sign'] );
		//分析产品访问日志
		$modelData['about_visit'] = $this->parseVisitLog(  $modelData['uba_id'],$modelData['vid'],$data['first_ts'],$modelData['product_sign']);
		//分析访问用户
		$modelData['about_user'] = $this->parseUserInfo( $modelData['uba_id'],$modelData['vid'],$data['first_ts'] );
		//分析产品是否下单
		$modelData['about_order_list'] = $this->parseOrderInfo( $modelData['uba_id'],$modelData['vid'],$data['first_ts'], $productInfo['id'] );
		$this->productVisitLogs[] = $logKey;
		return $modelData;
	}
	
	//获取产品信息
	protected function getProductInfo( $sign )
	{
		$proModel = new ProductModel();
		$res = $proModel->getInfoByFitemNo( $sign );
		if( !$res ){
			return false;
		}
		return $res;
	}
	
	//计算本访次访问本产品的次数
	protected function getVisiorVisitCount( $uba_id, $vid, $first_ts , $sign )
	{
		$res = $this->logModel->where(['link_info.params.sign'=>$sign,'uba_id'=>$uba_id,'vid'=>$vid, 'first_ts'=>$first_ts])->order('visit_ts desc')->count();
		return $res?$res:0;
	}
	
	//分析产品访问日志
	protected function parseVisitLog(  $uba_id, $vid, $first_ts , $sign )
	{
		$res = $this->logModel->where(['link_info.params.sign'=>$sign,'uba_id'=>$uba_id,'vid'=>$vid, 'first_ts'=>$first_ts])->order('visit_ts desc')->select();
		$logs = [];
		foreach( $res as $k=>$v ){
			$log['log_id'] = $k;
			$log['reffer_log'] = $this->parseReffer( $v );
			$log['visit_ts'] = date('Y-m-d H:i:s',$v['visit_ts']);
			$log['stay_ts'] = seconds2minAndSeconds($v['stay_ts']);
			$trace_event = $this->parseTraceEvent( $v['trace_event'] );
			$log['is_add_cart'] = $trace_event['is_add_cart'];
			$log['is_add_collect'] = $trace_event['is_add_collect'];
			$logs[] = $log;
		}
		return $logs;
	}
	
	//分析页面操作事件
	protected function parseTraceEvent( $trace )
	{
		$data = [
			'is_add_cart' =>0,
			'is_add_collect'=>0,
		];
		foreach( $trace as $k=>$value ){
			if( $value['name'] === 'product_add_cart' ){
				$data['is_add_cart'] = 1;
			}
			if( $value['name'] === 'product_add_collect' ){
				$data['is_add_collect'] = 1;
			}
		}
		return $data;
	}
	
	//分析用户信息
	protected function parseUserInfo( $uba_id, $vid, $first_ts )
	{
		$userData = [
			'user_id'=>0,//用户id
			'user_type'=>0,//用户类型
			'user_cust_no'=>'',//用户关联ERP客户编号
			'user_cust_name'=>'',//用户关联ERP客户名称
			'user_sale_no'=>'',//用户所属业务员编号
			'user_sale_name'=>'',//用户所属业务员名称
		];
		$where['uba_id']=$uba_id;
		$where['vid'] = $vid;
		$where['first_ts'] = $first_ts;
		$where['user_id'] = ['neq',0];
		$user = $this->logModel->field('user_id')->where($where)->find();
		$userInfo  = M('user')->field('id,user_type,fcustno,fcustjc,sys_uid')->where(['id'=>$user['user_id']])->find();
		if( $user  && $userInfo ){
			$userData['user_id'] = $user['user_id'];
			$userData['user_type'] = $userInfo['user_type'];
			//客户ERP编码
			$userData['user_cust_no'] = $userInfo['fcustno'];
			$userData['user_cust_name'] = $userInfo['fcustjc'];
			$sys_uid = $userInfo['sys_uid'];
			//客户所属业务员
			if( $sys_uid ){
				$sysUserINfo = M('','sys_user')->field('FEmplNo,FEmplName')->where(['uid'=>$sys_uid])->find();
				if( $sysUserINfo){
					$userData['user_sale_no'] = $sysUserINfo['femplno'];
					$userData['user_sale_name'] = $sysUserINfo['femplname'];
				}
			}
		}
		return $userData;
	}
	
	//分析产品是否下单
	protected function parseOrderInfo( $uba_id, $vid, $first_ts, $pid )
	{
		$where['link_params'] = ['neq',''];
		$where['trace_event'] = ['neq',''];
		$where['uba_id'] = $uba_id;
		$where['vid'] = $vid;
		$where['first_ts'] = $first_ts;
		$res = $this->logModel->where( $where )->order('visit_ts desc')->select();
		$orderList = [];
		foreach( $res as  $k=>$v ){
			if( (string)$v['link_params']['pid[]'] !== (string)$pid ){
				continue;
			}
			if( empty($v['trace_event'] ) ){
				continue;
			}
			foreach( $v['trace_event'] as $key=>$value ){
				if( $value['name'] === 'order_pay_success' && $value['data'] ){
					if( $value['data']['order_no'] && !empty($value['data']['success']) ){
						$orderInfo = M('order')->field('order_status')->where(['order_sn'=>$value['data']['order_no']])->find();
						if( $orderInfo ){
							$order['order_sn'] = $value['data']['order_no'];
							$order['pay_ts'] = date('Y-m-d H:i:s', $value['ts']);
							if( $orderInfo['order_status'] > 1 ){
								$order['order_pay_success'] = 1;
							}else{
								$order['order_pay_success'] = 0;
							}
							$orderList[] = $order;
						}
					}
				}
			}
		}
		return $orderList;
	}
	
	/*
	 * 解析来源链接
	 */
	protected function parseReffer( $log )
	{
		$reffer = [
			'mca' => '',
			'params'=>[]
		];
		$reffer_info = $log['reffer_info'];
		if( is_string($reffer_info)  ){
			if( $reffer_info !== '' ){
				$reffer['mca'] = 'home@default@index';
				$reffer['page_name'] = $this->mcaName[$reffer['mca']];
			}else{
				$reffer['mca'] = '';
				$reffer['page_name'] = '未定义页面';
			}
		}elseif( is_object($reffer_info) || is_array( $reffer_info ) ){
			$reffer['mca'] = $reffer_info['mca'];
			if( array_key_exists($reffer['mca'], $this->mcaName) ){
				$reffer['page_name'] = $this->mcaName[$reffer['mca']];
			}else{
				$reffer['page_name'] = '未定义页面';
			}
			//来源是搜索
			if( $reffer['mca'] === 'home@product@search' ){
				$reffer['params'] = $this->parseRefferForSearch( $reffer_info['params'] );
			}else{
				$reffer['params'] = $reffer_info['params'];
			}
		}
		
		return $reffer;
	}
	
	
	protected function parseRefferForSearch( $searchParams )
	{
		if( empty( $searchParams ) ){
			return [];
		}
		$searchArr = [
			'search_words'=>'',//搜索关键词
			'search_type'=>'', //默认是搜索型号 brand_name 品牌  function 是功能
			'search_inner'=>'',//在关键字结果中搜索的关键字
			'cate_id'=>0,//分类id
			'cate_name'=>'',
			'brand_id'=>0,//品牌id
			'brand_name'=>'',
		];
		foreach( $searchParams as $k=>$v ){
			if( array_key_exists($k, $searchArr) && !empty($v) ){
				$searchArr[$k] = $v;
			}
		}
		if( $searchArr['search_type'] === '' ) $searchArr['search_type'] = 'sign_name';
		if( $searchArr['cate_id'] ){
			$cateRes = M('category')->field('cate_name')->where(['id'=>$searchArr['cate_id']])->find();
			$searchArr['cate_name'] = $cateRes['cate_name'];
		}
		if( $searchArr['brand_id'] ){
			$brandRes = M('brand')->field('brand_name')->where(['id'=>$searchArr['brand_id']])->find();
			$searchArr['brand_name'] = $brandRes['brand_name'];
		}
		return $searchArr;
	}
	
	//访问链接处理 去除链接参数
	protected function linkStore( $links )
	{
		$urlCase = C('URL_CASE_INSENSITIVE');
		if( $urlCase ){
			$links = strtolower( $links );
		}
		return $links;
	}
	
	protected function linkParams( $link )
	{
		if( empty($link) ){
			return '';
		}
		$parse = parse_url( urldecode($link) );
		parse_str( htmlspecialchars_decode($parse['query']), $array );
		return  $array;
	}
	
}