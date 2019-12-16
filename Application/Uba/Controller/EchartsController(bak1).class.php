<?php
// +----------------------------------------------------------------------
// | FileName:   echartsController.class.php
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date:  2018-03-08 17:29
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Controller;

use EES\System\Redis;
use Think\Controller;
use Uba\Mdu\BaseMdu;
use Uba\Mdu\ProductMdu;
use Uba\Model\UbaVisitLogModel;
use Uba\Model\UbaVisitRefferModel;

class EchartsController extends Controller
{
    protected $date;

    protected $hour;

    protected $data;//数据

    private  $logModel;

    public function _initialize()
    {
        $this->date = date('Y-m-d');
        $this->hour = date( 'H' );
        $this->logModel = new UbaVisitLogModel();
    }

    public function index()
    {
//    	$param = parse_url('http://www.shop.com/Home/Default/index.html');
//    	dd( $_SERVER );
//    	dd( $_SERVER['HTTP_HOST'] );
//    	de( $param['host'] );
        $this->display();
    }

    /**
     * AJAX 获取echarts可用的最近三十分钟实时数据
     * @param string $startTime 开始时间 时间字符串 例如: 12:30 || 2018-03-08 12:30
     * @param string $endTime 结束时间 时间字符串 例如: 12:30 || 2018-03-08 12:30
     * @param string $type  类型  可选值 all => 全部类型统计数据(PV,UV,IP)
     *                                  ip => IP统计
     *                                  pv => PV统计
     *                                  uv => uv统计
     */
    public function getLastThirtyMinData($startTime, $endTime, $type='all' )
    {
        if( !in_array( strtolower($type), ['all','ip','pv','uv'] ) ){
            ajaxReturn(['error'=>1,'msg'=>'type 参数不正确!请检查']);
        }
        $startTime = strtotime( date('Y-m-d H:i:00', strtotime($startTime)) );
        $endTime = strtotime(date('Y-m-d H:i:59', strtotime($endTime)));
        $list = $this->getCountByDate( $type , $startTime, $endTime,'+1 Minute' );
        ajaxReturn(['error'=>0, 'data'=>$list]);
    }

	/**
	 * AJAX 获取echarts可用的本月统计数据
	 * @param string $type  类型  可选值 all => 全部类型统计数据(PV,UV,IP)
	 *                                  ip => IP统计
	 *                                  pv => PV统计
	 *                                  uv => uv统计
	 */
    public function getCurrMonthData( $type='all' )
	{
		if( !in_array( strtolower($type), ['all','ip','pv','uv'] ) ){
			ajaxReturn(['error'=>1,'msg'=>'type 参数不正确!请检查']);
		}
		$startTime = strtotime( date('Y-m-01 00:00:00', time()) );
		//$endTime = strtotime(date('Y-m-d 23:59:59', time()));
		$endTime = strtotime('+1 day');
		$list = $this->getCountByDate( $type , $startTime, $endTime,'+1 day', true );

		foreach( $list['timeList'] as $key=>$value ){
			$list['timeList'][$key] = date( 'Y-m-d', $value );
		}
		ajaxReturn(['error'=>0, 'data'=>$list]);
	}

	/*
	 * 获取访问量最多的页面排行
	 *
	 */
	public function getTopCountPage()
	{

	}

	/*
	 * 获取产品访问量最多的产品排行
	 */
	public function getTopPvCountProduct()
	{

	}

	/*
	 * 获取来源访问统计
	 */
	public function getSumRefferData()
	{
		$m = new UbaVisitRefferModel();
		$data['xList'] = $m->distinct('link_sign');
		foreach( $data['xList'] as $k=>$v ){
			$data['data'][] = [
				'name'=>$v,
				'value'=>$m->getCount( ['link_sign'=>$v],'_id',true ),
			];
		}
		ajaxReturn(['error'=>0, 'data'=>$data]);
	}
	
	
    protected function getCountByDate( $type, $start,$end,$interval='+1 Minute', $isReturnDate=false )
    {
        $data = [];
        $type = strtolower($type);
        $startTime = is_timestamp($start)?$start:strtotime($start);
        $endTime = is_timestamp($end)?$end:strtotime($end);
        $timeList = $this->prDates( $startTime, $endTime,$interval);
        switch( $type){
            case 'all':
                $class = [
                    'pv'=>'getPvCountByDate',
                    'uv'=>'getUvCountByDate',
                    'ip'=>'getIpCountByDate'
                ];
            case 'pv':
                $class['pv'] = 'getPvCountByDate';
                break;
            case 'uv':
                $class['uv'] = 'getUvCountByDate';
                break;
            case 'ip':
                $class['ip'] = 'getIpCountByDate';
                break;
            default:
                return false;
        }
        foreach( $class as $k=>$cl ){
            foreach( $timeList as $key =>$time ){
                if( $key !== count($timeList)-1 ){
                    $data[$k][] = $this->logModel->$cl( $time, $timeList[$key+1] );
                }else{
                    $eTime = strtotime($interval, strtotime($time));
                    $data[$k][] = $this->logModel->$cl( $time, $eTime);
                }
            }
        }
        $this->data = array_merge( $this->data, $data );
        $isReturnDate && $data['timeList']= $timeList;
        return $data;
//        Redis::
    }

    protected function prDates($start,$end, $interval='+1 day'){
        $date = [];
        $dt_start = is_timestamp($start)?$start:strtotime($start);
        $dt_end = is_timestamp($end)?$end:strtotime($end);
        while ($dt_start<=$dt_end){
            $date[] = $dt_start;
            $dt_start = strtotime($interval,$dt_start);
        }
        return  $date;
    }
    
    //分析产品数据
    public function getProductParse()
	{
		BaseMdu::register('shop_product', new ProductMdu());
		$res = BaseMdu::runOne('shop_product');
		return de($res);
	}
}