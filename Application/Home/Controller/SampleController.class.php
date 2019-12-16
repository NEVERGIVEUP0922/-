<?php

// +----------------------------------------------------------------------
// | FileName:   ProductController.class.php
// +----------------------------------------------------------------------
// | Dscription:   产品控制器类
// +----------------------------------------------------------------------
// | Date:  2017/8/7 12:45
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace Home\Controller;

use Admin\Controller\MsgController;
use Home\Controller\HomeController;
use Org\ThinkSDK\sinaClient;
use Org\ThinkSDK\ThinkOauth;
use Home\Controller\DefaultController;


class SampleController extends HomeController
{
    //前台-数据
    public  function  storeSample(){
        $request=I('post.');
        $session=session();
        if(!($session&&$session['userId'])){
            die(json_encode(['error'=>1,'msg'=>"请重新登录"]));
        }
        $request['user_id'] = $session['userId'];
        $field="project_name,month_num,application_area,prototype_date,project_status,batch_date,item,user_id";
        $request['item']=json_encode($request['item'],true);
        $checkRes=$this->checkSample($field,$request);
        if($checkRes['error']==1){
            die(json_encode($checkRes));
        }
        $sampleAdd=M('user_sample')->field($field)->add($request);
        if($sampleAdd){
            die(json_encode(['error'=>0,'msg'=>'提交成功']));
        }else{
            die(json_encode(['error'=>1,' 提交失败']));
        }
    }

    public  function  checkSample($field,$request){
        $field_arr=explode(',',$field);
        foreach ($field_arr as $v){
            $checkRes=isset($request[$v]);
            if(!$checkRes){
                return ['error'=>1,'msg'=>'参数缺少'];
            }
        }
        return  ['error'=>0,'msg'=>'验证通过'];
    }

    public function  userSampleList(){
        $request=I('get.');
        //分页  每页20条
        $request['page']=$page=$request['page']?$request['page']:1;
        $request['pageSize']=$pageSize=$request['pageSize']?$request['pageSize']:20;
        $session=session();
        $where['user_id']=$session['userId'];
        $userSample=M('user_sample');
        $userSampleList=$userSample->where($where)->page($page,$pageSize)->order('update_at desc')->select();
        $count=$userSample->field("count(id) as count")->where($where)->find();
        $request['count']=$count['count'];
        foreach ($userSampleList as &$v){
            $sampleWhere['origin']=$v['id'];
            $homeList=M('user_product_example')->where($sampleWhere)->select();
            $v['samplelist']=D('Admin/userSample')->homeSampleList($homeList);
            $v['item']=json_decode($v['item'],true);
        }
        //用户信息
        $user_id = session( 'userId' );
        $userType = session('userType');
        if( $userType == 1 ){
            $key = 'ssid_'.$user_id;
            if(S($key)){
                $ssid = S($key);
                if( $this->ssid !== $ssid ){
                    session(null);
                    S($key,null);
                    redirect(U('Home/Account/login', ['isRelogin'=>1]));
                }
            }
        }

        $order_m=new \Home\Controller\OrderController();//企业子账号信息
        $companySons=$order_m->companySons();
        if($companySons['error']==0){
            $users=$companySons['data'];
            $user_id=['in',$users];
        }
        //查询用户待付款订单条数
        $noPay_list = D( 'order' )->where( [ 'user_id' => $user_id, 'pay_status' => 0, 'order_status'=>['in',[0,1]] ] )->select();
        $noPayNum = count($noPay_list);
        $this->assign('noPayNum', $noPayNum);

        //查询待发货条数
        $noShip_list = D( 'order' )->where( [ 'user_id' => $user_id, 'ship_status' => 0, 'order_status'=>['in', [0,1,2]] ] )->select();
        $noShipNum = count($noShip_list);
        $this->assign('noShipNum', $noShipNum);

        //查询待收货条数
        $noDelivery_list = D( 'order' )->where( [ 'user_id' => $user_id,'order_status'=>['in', [0,1,2]], 'ship_status' =>['in', [1,2,3]]  ] )->select();
        $noDeliveryNum = count($noDelivery_list);
        $this->assign('noDeliveryNum', $noDeliveryNum);

        //查询待评价条数
        $noRelease_list = D( 'order' )->where( [ 'user_id' => $user_id, 'order_status' => 3, 'is_comment'=>1 ] )->select();
        $noReleaseNum = count($noRelease_list);
        $this->assign('noReleaseNum', $noReleaseNum);
        $pages=$request['page']?$request['page']:1;
        $pageSize=$request['pageSize']?$request['pageSize']:C('PAGE_PAGESIZE');
        $request['page']=$pages;
        $request['pageSize']=$pageSize;
        $Page = new \Think\Page( $request['count'], $pageSize );// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig( 'prev', '<上一页' );
        $Page->setConfig( 'next', '下一页>' );
        $show = $Page->show();// 分页显示输出
        if ( $request['count'] > $pageSize ) $this->assign( 'page', $show );// 赋值分页输出
        $this->assign('request',$request);
        $this->assign('isManage',"1");
        $this->assign('userSampleList',$userSampleList);
        $this->display("/User/userSampleList");


    }


}
