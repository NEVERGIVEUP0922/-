<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-12-26
 * Time: 17:45
 */
namespace Admin\Controller;

use Admin\Model\RetreatModel;
use Admin\Model\UserModel;
use Home\Controller\KdController;
use EES\System\Redis;

class RetreatController extends AdminController
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     *  @desc 退款申请列表
     */
    public function index()
    {


        $must_where=[];
        $method_name=MODULE_NAME;
        $key_method=strtolower($method_name);
        $session=session();
        if($key_method=='admin'){
            $userM=new UserModel();
            //业务部门
            $productPowers=$userM->departmentDataPower('order',$session['adminInfo']['department_id'],$where,'sys_uid');
            if($productPowers['error']!=0) return $productPowers;
            if(isset($productPowers['data']['must_where'])){
                $customers=$userM->adminSaleCustomerPower($productPowers['data']['must_where'],'id');
                if($customers['error']!=0) return $customers;
                $must_where['user_id']=['in',$customers['data']];
            }
            //财务部门
            $productPowers=$userM->departmentDataPower('product',$session['adminInfo']['department_id'],$where,'sys_uid');
            if($productPowers['error']!=0) return $productPowers;
            if(isset($productPowers['data']['must_where']['sys_uid'][1])&&$productPowers['data']['must_where']['sys_uid'][1]){

                $vm_id=implode(',',$productPowers['data']['must_where']['sys_uid'][1]);

                $vmwhere='re_sn in (select re_sn from dx_order_retreat_vm where vm_id IN ('.$vm_id.'))';

            }
        }else{
            $must_where['order_status']=['neq',100];
        }
        if($where){
            if($must_where){
                $where=[$where,$must_where];
            }
        }else if($must_where){
            $where=$must_where;
        }

        $ruleWhere = $where;
        $query = I('get.');
        $page=I('get.page')?I('get.page'):1;
        $pageSize=I('get.pageSize')?I('get.pageSize'):C('PAGE_PAGESIZE');
        $where = '';
        $whereList = [];
        $ids = $reSnRes = [];
        foreach( $query as $k=>$v ){
            if( $v ){
                switch( $k ){
                    case 'order_sn':
                        $whereList[] = ' order_sn  = \''.$v.'\'';
                        break;
                    case 're_sn':
                        $reSnRes[] = $v;
                        break;
                    case 'fcustname':
                        $idRes = M('user')->field('id')->where(" user_name like '%{$v}%' OR fcustno = '{$v}' OR fcustjc like '%{$v}%'")->select();
                        $idRes && $ids = array_column( $idRes,'id' );
                        break;
                    case 'sys_user':
                        $condition = [
                            'FEmplNo'=>$v,
                            'FEmplName'=>$v,
                            'user_name'=>$v,
                            'fullname'=>$v,
                            'nickname'=>$v,
                            '_logic' => 'OR',
                        ];
                        $sysUidRes = M('','sys_user')->field('uid')->where( $condition )->find();
                        if( $sysUidRes ){
                            $idRes = M('user')->field('id')->where(['sys_uid'=>$sysUidRes['uid']])->select();
                            if( $idRes ){
                                $idss = array_column( $idRes, 'id' );
                                $ids?$ids = array_merge( $ids, $idss ):$ids = $idss;
                            }
                        }
                        break;
                    case 'person_liable':
                        $condition = [
                            'vm_id'=>$v,
                            'vm_name'=>$v,
                            '_logic' => 'OR',
                        ];
                        $res = M('order_retreat_vm')->field('re_sn')->where($condition)->select();
                        $re = array_column($res, 're_sn');
                        $reSnRes?$reSnRes = array_merge( $reSnRes, $re ):$reSnRes = $re;
                        break;
                }
            }
        }

        $ids && $whereList[] = 'user_id in ('.implode(',', $ids).')';
        $reSnRes && $whereList[] = 're_sn in('.implode(',', $reSnRes).')';
        if( $whereList ){
            $where = implode( ' AND ', $whereList );
        }
        
        if($ruleWhere){
			$ruleWhere= 'user_id in ('.implode(',', $ruleWhere['user_id'][1]).')';
		}
	
        if( $ruleWhere ){
        	if($where){
				$where .= ' and ('.$ruleWhere.')';
			}else{
				$where .= ' ('.$ruleWhere.')';
			}
        
        };

        if($vmwhere){
            if($where){
                $where .= ' and ('.$vmwhere.')';
            }else{
                $where .= ' ('.$vmwhere.')';
            }
        }
        $res = D('Retreat')->getRetreatList( $where, $page, $pageSize);
        $types = C('SHOP_ORDER_PAY_TYPE');
        foreach( $res['list']  as $key=>$value  ){
            //查询订单下单的支付方式
            $payInfo = M('order')->field('pay_type')->where(['order_sn'=>$value['order_sn']])->find();
            //查询订单支付历史的支付方式
            $pay_history =M('order_pay_history')->query("SELECT GROUP_CONCAT(a.pay_name) as pay FROM ( SELECT DISTINCT pay_name from `dx_order_pay_history` WHERE ( order_sn='{$value['order_sn']}' ) ORDER BY pay_time ) as a limit 1");
            $typeKey = $payInfo['pay_type'];
            $res['list'][$key]['pay_type'] = $types[$typeKey].'['.$pay_history[0]['pay'].']';
            $res['list'][$key]['retreat_img'] = json_decode( $value['retreat_img'], true );
            if ($value['order']['ship_status'] >= 1){
                $delivery = (new KdController())->info( $value['ship'][ 'code' ], $value['re_delivery_num'], $value['order_sn'] );
                $res['list'][$key] = array_merge( $res['list'][$key] ,$delivery );
                //按物流时间节点排序
                $arr1 = array_column( $res['list'][$key][ 'traces' ],'AcceptTime' );
                array_multisort($arr1,SORT_DESC,$res['list'][$key][ 'traces' ] );
                $res['list'][$key][ 'traces' ] = kdTracesFormat( $res['list'][$key][ 'traces' ] );
            }
            //业务员
            if( isset($value['userInfo']['sys_uid']) && $value['userInfo']['sys_uid'] ){
                $sysInfo = M('','sys_user')->field('FEmplName')->where(['uid'=>$value['userInfo']['sys_uid']])->find();
                $res['list'][$key]['sys_name'] = $sysInfo['femplname'];
				$res['list'][$key]['fcustname'] = $value['userInfo']['fcustjc'];
            }elseif($value['userInfo']['user_type']==20){
					$r=M()->query("select du.fcustno,du.fcustjc,du.sys_uid from dx_user du,dx_user_son dus where du.id=dus.p_id and dus.user_id={$value['userInfo']['id']}");
					if($r && $r[0]['sys_uid']){
						$sysInfo = M('','sys_user')->field('FEmplName')->where(['uid'=>$r[0]['sys_uid']])->find();
						$res['list'][$key]['sys_name'] = $sysInfo['femplname'];
					}
				   $res['list'][$key]['fcustname'] = $r['0']['fcustjc'];
			}
            //查询产品VM
            $vm = $vmIds = [];
            foreach ($value['vmInfo'] as $kv=>$vv){
            	if($vv['p_id']==0){
            		unset($value['vmInfo'][$kv]);
				}
			}
			//print_r($value['vmInfo']);
            if ( $value['vmInfo'] ){
                $vm  = array_column($value['vmInfo'], 'vm_name');
                $vmIds = array_column($value['vmInfo'], 'vm_id');
            }
            //print_r($vm);
            $vm = array_unique( $vm );
            $vmIds = array_unique( $vmIds );
            $res['list'][$key]['vm'] = $vm?implode(',',$vm):'';
            $res['list'][$key]['vmIds'] = $vmIds;
            $res['list'][$key]['logs'] = array_reverse( $value['logs'] );
            $res['list'][$key]['user_name'] = $value['userInfo']['user_name'];
           // $res['list'][$key]['fcustname'] = $value['userInfo']['fcustjc'];
        }
        $res['page'] = $page;
        $res['pageSize'] = $pageSize;
        // print_r($res);die();
        $this->assign( 'retreatList', $res );
        $this->assign('request',$query);
        $this->display();

    }


    /**
     *  @desc 退款申请详情
     *
     */
    public function getDetail( $re_sn )
    {
        $res = D('Retreat')->getDetail( $re_sn );
        //print_r($res);
        //VM进度
        $vmProgress = ['未审核','同意','驳回'];
        //查询业务员信息
		if($res['userInfo']['user_type']==20){
			$pid=M('user_son')->field('p_id')->where(['user_id'=> $res['user_id'] ])->find();
			$userInfo1 = M('user')->field('sys_uid')->where(['id'=>(int)$pid['p_id']])->find();
			$sysUser = M('','sys_user')->field('FEmplNo,FEmplName')->where(['uid'=>$userInfo1['sys_uid']])->find();
			$res['sysUser'] = $sysUser;
		}else{
			$sysUser = M('','sys_user')->field('FEmplNo,FEmplName')->where(['uid'=>$res['userInfo']['sys_uid']])->find();
			$res['sysUser'] = $sysUser;
		}
        $re_img = json_decode( $res['retreat_img'], true );
		$de_img = json_decode( $res['re_delivery_img'], true );
        if( $res['re_delivery_status'] >= 1 ){
            $orderKd = M('kd_delivery')->where( ['id'=>$res['re_delivery_id']] )->find();
            //print_r($orderKd);
            if( $orderKd ){
            	if(!$res['re_delivery_num']){
					$res[ 'traces' ]=[];
					$res['re_delivery_name']=$orderKd['kd_name'].'('.$orderKd['kd_code'].')';
				}else{
					$delivery = (new KdController())->info( $orderKd[ 'kd_code' ], $res['re_delivery_num'], $res['order_sn'] ,0,false);
					$res = array_merge( $res ,$delivery );
					$res['re_delivery_name'] = $orderKd['kd_name'].'('.$orderKd['kd_code'].')';
					//按物流时间节点排序
					$arr1 = array_column( $res[ 'traces' ],'AcceptTime' );
					array_multisort($arr1,SORT_DESC,$res[ 'traces' ] );
					$res[ 'traces' ] = kdTracesFormat( $res[ 'traces' ] );
				}
				
            }
        }

        $res['logs'] = array_reverse($res['logs']);

        $res['user_name'] = $res['userInfo']['user_name'];
        $this->assign( 're_img',$re_img );
		$this->assign( 'de_img',$de_img );
        $this->assign( 'detail',$res );
      // de( $res );
        $this->assign( 'vmProgress', $vmProgress );
        $html =$this->fetch('Retreat/detail');
        $this->ajaxReturn( ['html'=>$html] );
    }


    /**
     *  @desc VM审核退款申请
     *  @param int $re_sn 退款交易编号
     *
     */
    public function agreeRetreat()
    {
        $re_sn = I('re_sn')?I('re_sn'):0;
        $action_desc = I('action_desc');
        $re_sn == 0 && $this->ajaxReturnStatus(1001,'参数错误');
        //查询退款订单信息
        $retreat = new RetreatModel();
        $order = $retreat->where(['re_sn'=>$re_sn])->find();
        !$order && $this->ajaxReturnStatus(1002,'退款订单数据不存在!');

        $adminId  = session('adminInfo.uid');
        //查询产品Vm
        $vms = M('order_retreat_vm')->where(['re_sn'=>$re_sn,'vm_check'=>0])->select();
        $vmIds = array_unique( array_column( $vms,'vm_id' ));
        !in_array($adminId,$vmIds) && $this->ajaxReturnStatus(1003,'您不是产品VM,无法审核!');
        foreach( $vms as $k=>$v ){
            if( (int)$v['vm_id'] === (int)$adminId ){
                if ( $v['vm_check'] === 1 ){
                    $this->ajaxReturnStatus(1004,'您已审核通过!请不要重复审核');
                }
                unset( $vmIds[$k] );
            }
        }
        //审核列表为空 那么代表已全部审核通过
        if( empty( $vmIds ) ){
            //退货退款的订单 写入退款同步列表
            if( (int)$order['retreat_type'] === 1 ){
                $redis = Redis::getInstance();
                $redRes = $redis->sAdd( 'retreatOrderSyncList', $re_sn );
                //同意申请
                $this->retreatStatus(2,$re_sn,1,1, $action_desc);
            }elseif( (int)$order['retreat_type'] === 0  ){
                //同意申请
                $this->retreatStatus(5,$re_sn,1,1, $action_desc);
            }elseif( (int)$order['retreat_type'] === 2  ){
				//同意申请
				$this->retreatStatus(2,$re_sn,1,1, $action_desc);
			}

        }else{
            //vm审核
            $this->retreatStatus(8,$re_sn,1,8, $action_desc);
        }

    }

    /**
     *  @desc VM驳回退款申请
     *  @param int $re_sn 退款交易编号
     *
     */
    public function rejectRetreat()
    {
        $re_sn = I('re_sn')?I('re_sn'):0;
        $action_desc = I('action_desc');
        $re_sn == 0 && $this->ajaxReturnStatus(1001,'参数错误');
        empty( $action_desc ) && $this->ajaxReturnStatus(1001,'驳回原因不能为空');
        //查询退款订单信息
        $retreat = new RetreatModel();
        $order = $retreat->where(['re_sn'=>$re_sn])->find();
        !$order && $this->ajaxReturnStatus(1002,'退款订单数据不存在!');

        $adminId  = session('adminInfo.uid');
        //查询产品Vm
        $vms = M('order_retreat_vm')->where(['re_sn'=>$re_sn])->select();
        $vmIds = array_unique( array_column( $vms,'vm_id' ));
        !in_array($adminId,$vmIds) && $this->ajaxReturnStatus(1003,'您不是产品VM,无法操作!');
        $this->retreatStatus(3,$re_sn,1,2, $action_desc);
    }

    /**
     * @desc 确认买家退的货物  收货操作
     *  @param int $re_sn 退款交易编号
     */
    public function agreeRetreatDelivery()
    {

        $re_sn = I('re_sn')?I('re_sn'):0;
        $action_desc = I('action_desc');
        $re_sn == 0 && $this->ajaxReturnStatus(1001,'参数错误');
        $rr=M('order_retreat')->where(['re_sn'=>$re_sn])->find();
        if($rr['retreat_type']==1){
			$this->retreatStatus(6,$re_sn,1,6, $action_desc);
		}else{
			$this->retreatStatus(5,$re_sn,1,5, $action_desc);
		}
    
    }

    /**
     *
     * @desc 执行退款操作 交易完成
     *
     */
    public function commitRetreat()
    {
        $re_sn = I('re_sn')?I('re_sn'):0;
        $action_desc = I('action_desc');
        $re_sn == 0 && $this->ajaxReturnStatus(1001,'参数错误');
        //改变订单状态
        $res  = M('order_retreat')->field('order_sn')->where(['id'=>$re_sn])->find();
        $re = D('order')->where(['order_sn'=>$res['order_sn']])->setField('order_status',6);

       // $wallet=D('Wallet/Integral');//积分退还
      //  $list=$wallet->integralOrderThaw($res['order_sn'],40);

//        //查询客户Sales
//        $sale = D('Retreat')->getUserSalesUid( $re_sn );
        if( $re !== false){
            $this->retreatStatus(6,$re_sn,1,6, $action_desc);
        }else{
            $this->ajaxReturnStatus(1000, '处理失败');
        }
    }

    /*
     *  退款操作记录日志
     * @param int $re_sn  [退款交易编号]
     * @param int $user_type   [用户类型   0为买家用户 1为玖隆方]
     * @param int $action_type  [操作类型  0为创建退款交易 1为玖隆批准 2为玖隆驳回 3为买家修改退款 4为买家发货 5为玖隆已收货 6为关闭退款交易 7为用户撤销退款 ]
     * @oaram varchar $action_desc  [操作补充说明]
     *
     * @return
     */
    protected function retreatStatus($handle_status,$re_sn,$user_type,$action_type,$action_desc='')
    {
        if( $handle_status == 2 ){
            $save['apply_delivery_address'] = C('RETREAT_DELIEVRY_ADDRESS');
            $save['apply_delivery_user'] = C('RETREAT_DELIVERY_USER');
            $save['apply_delivery_phone'] = C('RETREAT_DELIEVEY_PHONE');
        }
        if( $handle_status == 6 ){
            $save['close_time'] = time();
        }

        $vmSave = [];
        if( $handle_status == 8 || $handle_status == 2 ){
            $vmSave = [
                'vm_check'=>1,
                'vm_check_time'=>date('Y-m-d H:i:s')
            ];

        }
		if( $handle_status == 3){
			$vmSave = [
				'vm_check'=>2,
				'vm_check_time'=>date('Y-m-d H:i:s')
			];
		
		}
        
     
        $save['handle_status'] = $handle_status;
        $retreat = M('order_retreat');
        $add = [
            're_sn'=>$re_sn,
            'user_type'=>$user_type,
            'user_id'=>0,
            'user_name'=>'',
            'action_type'=>$action_type,
            'action_desc'=>$action_desc,
            'handle_user'=>session('adminInfo.uid'),
            'handle_name'=>session('adminInfo.fullname').'-'.session('adminInfo.user_name'),
        ];
        $retreat->startTrans();
		if($handle_status == 3){
		    $map['re_sn']=$re_sn;
		    $map['handle_status']= array('in',[3,7]);
			$r=$retreat->where($map)->find();
			if(!$r){
				$goods1 = M( 'order_retreat_goods' )->where( [ 're_sn' => $re_sn ] )->select();
				foreach ( $goods1 as $k => $v ) {
					//查询旧数据
					$orderG = M( 'order_goods' )->field( 'retreat_num' )->where( [ 'order_sn' => $v[ 'order_sn' ], 'p_id' => $v[ 'p_id' ] ] )->find();
					$res = M( 'order_goods' )->where( [ 'order_sn' => $v[ 'order_sn' ], 'p_id' => $v[ 'p_id' ] ] )->save( [ 'retreat_num' => $orderG[ 'retreat_num' ] - $v[ 'p_num' ] ] );
					if ( $res === false ) {
						$retreat->rollback();
						$this->ajaxReturnStatus(1000, '处理失败');
					}
				}
			}
		}
	
		$re = $retreat->where(['re_sn'=>$re_sn])->save($save); //改变退款交易状态
        $log = M('order_retreat_log')->add($add);
        if( isset( $vmSave ) && !empty($vmSave) ){
            $vmRes = M('order_retreat_vm')->where(['re_sn'=>$re_sn,'vm_id'=>session('adminInfo.uid')])->save($vmSave);
            if( $vmRes === false ){
                $retreat->rollback();
                $this->ajaxReturnStatus(1000, '处理失败');
            }
        }
        if( $re !== false && $log){
            $retreat->commit();
            $this->ajaxReturnStatus(0000, '处理成功');
        }else{
            $retreat->rollback();
            $this->ajaxReturnStatus(1000, '处理失败');
        }
    }
}