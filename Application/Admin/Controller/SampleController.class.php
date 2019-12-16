<?php


// +----------------------------------------------------------------------
// | FileName:   OrderController.class.bak.20170906.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/22 15:14
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace  Admin\Controller;
use Admin\Model\ProductbargainModel;
use Common\Controller\BaseController;
use Common\Controller\Category;
use EES\System\Redis;
use Think\Model;

class SampleController extends AdminController
{
    /**
     * @desc 样品列表
     * @param
     */
    public  function storeSampleList(){
        $request=I('get.');
        //分页  每页20条
        $request['page']=$page=$request['page']?$request['page']:1;
        $request['pageSize']=$pageSize=$request['pageSize']?$request['pageSize']:20;
        $relation=isset($request['relation'])?$request['relation']:'userSampleList';
        if(isset($request['user_id'])&&$request['user_id']) $where['user_id']=$request['user_id'];
        if(isset($request['s'])&&$request['user_id']) $where['user_id']=$request['user_id'];
        if(isset($request['saleId'])&&$request['saleId']){
            $request['saleId']=(int)$request['saleId'];
            $where[]="user_id in (select id from dx_user where sys_uid = $request[saleId])";
        };
        $userSample=D('userSample');
        $sampleList=D('userSample');
        $userSampleList=$userSample->where($where)->order("update_at desc")->page($page,$pageSize);
        $userSampleList=$userSampleList->$relation()->relation(true)->select();
        $count=M('user_sample')->where($where)->field("count(id) as count")->find();
        $request['count']=$count['count'];

        foreach ($userSampleList as &$v){
           // $v['item']=$userSample->userSampleProductList($v['item']);
            $sampleList->table('dx_user_product_example');
            $sampleList->where(['origin'=>$v['id']]);
            $v['sampleList']=$sampleList->sampleList()->relation(true)->select();
            foreach ($v['sampleList'] as &$v2){
                $cate=M('category')->where(['id'=>$v2['cate_id']])->find();
                $brand=M('brand')->where(['id'=>$v2['brand_id']])->find();
                if($cate) $v2['cate_name']=$cate['cate_name'];
                if($brand) $v2['brand_name']=$brand['brand_name'];
            }
            $v['sys_name']='';
            if(!$v['fcustjc']){
                $v['fcustjc']=$v['nick_name'];
            }
            if($v['sys_uid']){
                $sys=M('user','sys_')->where(['uid'=>$v['sys_uid']])->find();
                if($sys){
                    $v['sys_name']=$sys['femplname'];
                }
            }
        }
        $this->assign('request',$request);
        $this->assign('userSampleList',$userSampleList);
        //print_r(count($userSampleList));die();
        $this->display("Customer/storeSampleList");
    }
    //样品详情
    public  function storeSampleDetail(){
        $request=I('get.');
        //分页  每页20条
        $request['page']=$page=$request['page']?$request['page']:1;
        $request['pageSize']=$pageSize=$request['pageSize']?$request['pageSize']:20;
        $relation=isset($request['relation'])?$request['relation']:'userSampleList';
        if(isset($request['user_id'])&&$request['user_id']) $where['user_id']=$request['user_id'];
        if(isset($request['index'])&&$request['index']) $where['id']=$request['index'];
        if(isset($request['saleId'])&&$request['saleId']){
            $request['saleId']=(int)$request['saleId'];
            $where[]="user_id in (select id from dx_user where sys_uid = $request[saleId])";
        };
        $userSample=D('userSample');
        $sampleList=D('userSample');
        $userSampleList=$userSample->where($where)->order("update_at desc")->page($page,$pageSize);
        $userSampleList=$userSampleList->$relation()->relation(true)->select();
        $count=M('user_sample')->where($where)->field("count(id) as count")->find();
        $request['count']=$count['count'];

        foreach ($userSampleList as &$v){
            $v['item']=$userSample->userSampleProductList($v['item']);
            $sampleList->table('dx_user_product_example');
            $sampleList->where(['origin'=>$v['id']]);
            $v['sampleList']=$sampleList->sampleList()->relation(true)->select();
            foreach ($v['sampleList'] as &$v2){
                $cate=M('category')->where(['id'=>$v2['cate_id']])->find();
                $brand=M('brand')->where(['id'=>$v2['brand_id']])->find();
                if($cate) $v2['cate_name']=$cate['cate_name'];
                if($brand) $v2['brand_name']=$brand['brand_name'];
            }
            $v['sys_name']='';
            if(!$v['fcustjc']){
                $v['fcustjc']=$v['nick_name'];
            }
            if($v['sys_uid']){
                $sys=M('user','sys_')->where(['uid'=>$v['sys_uid']])->find();
                if($sys){
                    $v['sys_name']=$sys['femplname'];
                }
            }
        }
        $this->assign('request',$request);
        $this->assign('userSampleList',$userSampleList);
        $this->display("Customer/storeSampleDetail");
    }
    /**
     * @desc 样品申请列表ID
     * @param id 样品id
     * @param item 样品参数
     * @param action_status 操作状态 1为审核通过 2位拒绝
     */
    public function addSample(){
        $request=I('post.');
        $userSamList=M('user_sample')->where(['id'=>$request['id']])->find();
        if(!$userSamList||$userSamList['action_status']!=0){
            die(json_encode(['error'=>1,'msg'=>'非法操作']));
        }
        if($request['action_status']==2){
            $sampleAdd=M('user_sample')->where(['id'=>$request['id']])->save(['action_status'=>$request['action_status']]);
            if($sampleAdd){
                die(json_encode(['error'=>0,'msg'=>'更新状态成功']));
            }else{
                die(json_encode(['error'=>1,'msg'=>'更新状态失败']));
            }
        }else{
            $item=[];
            foreach ($request['item'] as $k=>&$v){
                if(isset($v['selectSample']['id'])&&$v['selectSample']['id']){
                    $postArr[$k]['user_name']='';
                    $postArr[$k]['uid']=$userSamList['user_id'];
                    $postArr[$k]['fitemno']=$v['selectSample']['fitemno'];
                    $postArr[$k]['p_sign']=$v['selectSample']['p_sign'];
                    $postArr[$k]['pid']=$v['selectSample']['id'];
                    $postArr[$k]['origin']=$request['id'];
                    $postArr[$k]['handle']='删去';
                    $postArr[$k]['max_num']=$v['p_num'];
                }
                $item[$k]['pnum']=$v['pnum'];
                $item[$k]['p_sign']=$v['p_sign'];
                $item[$k]['package']=$v['package'];
                $item[$k]['cate']=$v['cate'];
                $item[$k]['brand']=$v['brand'];
                $item[$k]['list']['id']=$v['selectSample']['id'];
                $item[$k]['list']['p_sign']=$v['selectSample']['p_sign'];
                $item[$k]['list']['fitemno']=$v['selectSample']['fitemno'];
            }
            M()->startTrans();
            if($postArr){
                $result=$this->customerProductSampleActions($postArr);
                if($result['error']==1){
                    M()->rollback();
                    die(json_encode($result));
                }
            }
            $sampleAdd=M('user_sample')->where(['id'=>$request['id']])->save(['item'=>json_encode($item),'action_status'=>$request['action_status']]);
            if($sampleAdd){
                M()->commit();
                die(json_encode(['error'=>0,'msg'=>'更新样品成功']));
            }else{
                M()->rollback();
                die(json_encode(['error'=>1,'msg'=>'更新样品失败']));
            }
        }
    }

    /**
     *
     *  @desc 多样品操作
     */
    public function customerProductSampleActions($request){
        if(!$request||!is_array($request)) return ['error'=>1,'msg'=>'参数错误1'];
        $result=['error'=>0];
        $sys_uid=session('adminId');
        foreach($request as $k=>$v){
            $oneResult=(new ProductbargainModel())->customerUserProductSampleAction($v,$sys_uid);
            if($oneResult['error']!==0){
                return ['error'=>1,'msg'=>$v['p_sign'].'信息有误:'.$oneResult['msg']];
            }
        }
        return ['error'=>0,'msg'=>'success'];
    }


}