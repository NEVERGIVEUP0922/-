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
namespace Admin\Controller;

use Common\Controller\BaseController;
use Common\Controller\Category;
use Common\Controller\TreeController;
use EES\System\Redis;
use http\Env\Response;
use Common\Libs\Weixin\ComPay;//红包与企业支付
use Common\Libs\Weixin\WechatAuth;//JSSDK 需要用到accessToken
use Common\Libs\Weixin\JSSDK;//JSSDK
use alipay\Query;

class  AdvertController extends AdminController
{
	/*
	 * 轮番图管理
	 */
	public function photoAdevert(){
			//表 dx_advert_photo
		$request=I('get.');
        $request['page']=$page=$request['page']?$request['page']:1;

        $request['pageSize']=$pageSize=$request['pageSize']?$request['pageSize']:10;
		$photoAdvert=M("advert_photo")->order("sort desc")->limit(($request['page']-1)*$request['pageSize'],$pageSize)->select();

        $count=M("advert_photo")->field("count(id) as c")->order("sort desc")->select();
        $request['count']=$count[0]['c']?$count[0]['c']:0;
		//分页参数
		$this->assign("request",$request);
		//数值参数
		$this->assign('photoAdvert',$photoAdvert);
		$this->display("advert/index");
	}
	public function add(){
		$advertText=M("advert_text")->field("id,text_titile")->order("sort desc")->select();
		
		$this->assign("advertText",$advertText);
        $this->display("advert/add");
    }
	/*
	 * 添加轮番图 修改轮番图
	 */
	public function addPhoto(){
		$request=I('post.');
		if(isset($request['id']) && $request['id']){
		    $arr=['id'=>$request['id']];
		    unset($request['id']);
          
            $add=M("advert_photo")->where($arr)->save($request);
        
            if($add==="false"){
                die(json_encode(['status'=>1000,'msg'=>'添加失败']));
            }else{
                die(json_encode(['status'=>0,'msg'=>'添加成功']));
            }
        }else{
            $add=M("advert_photo")->add($request);
          	
            if($add==="false"){
                die(json_encode(['status'=>1000,'msg'=>'添加失败']));
            }else{
                die(json_encode(['status'=>0,'msg'=>'添加成功']));
            }
        }


	}

	/*
	 *获取轮番图信息
	 * @param id int
	 * @return json
	 */
    public function infoPhoto(){
        $request=I('post.');
        $info=M("advert_photo")->where($request)->find();
        if($info){
            die(json_encode(['status'=>0,'msg'=>$info]));
        }else{
            die(json_encode(['status'=>1000,'msg'=>'非法操作']));
        }
    }
	/*
	 * 删除轮番图
	 *
	 */
	public function delPhoto(){
		$request=I('post.');
		$add=M("advert_photo")->where($request)->delete();

		if($add==="false"){
			die(json_encode(['status'=>1000,'msg'=>'删除失败']));
		}else{
			die(json_encode(['status'=>0,'msg'=>'删除成功']));
		}
	}
	/*
	 * 文件广告管理
	 */
	public function textAdevert(){
		//表dx_advert_text
		$request=I('get.');
        $request['page']=$page=$request['page']?$request['page']:1;

        $request['pageSize']=$pageSize=$request['pageSize']?$request['pageSize']:10;
		$textAdvert=M("advert_text")->order("sort desc")->limit(($request['page']-1)*$request['pageSize'],$pageSize)->select();
		
		$count=M("advert_text")->field("count(id) as c")->order("sort desc")->select();
		$request['count']=$count[0]['c']?$count[0]['c']:0;
		//分页参数
		$this->assign("request",$request);
		//数值参数
		$this->assign('textAdvert',$textAdvert);
		$this->display("advert/textAdevert");
	}
	/*
	 *获取轮番图信息
	 * @param id int
	 * @return json
	 */
	public function infoText(){
		$request=I('post.');
		$info=M("advert_text")->where($request)->find();
		$info['text']=htmlspecialchars_decode($info['text']);
		if($info){
			die(json_encode(['status'=>0,'msg'=>$info]));
		}else{
			die(json_encode(['status'=>1000,'msg'=>'非法操作']));
		}
	}
    /*
     *  文件广告添加
     *  修改文本广告
     */
    public function addText_show(){
        $this->display("advert/addText_show");
    }
	public function addText(){
		$request=I('post.');
		if(isset($request['id']) && $request['id']){
			$arr=['id'=>$request['id']];
			unset($request['id']);
			$add=M("advert_text")->where($arr)->save($request);
			if($add==="false"){
				die(json_encode(['status'=>1000,'msg'=>'添加失败']));
			}else{
				die(json_encode(['status'=>0,'msg'=>'添加成功']));
			}
		}else{
			$add=M("advert_text")->add($request);
			if($add==="false"){
				die(json_encode(['status'=>1000,'msg'=>'添加/修改失败']));
			}else{
				die(json_encode(['status'=>0,'msg'=>'添加/修改成功']));
			}
		}
	}
	/*
	 *  文本广告删除
	 */
	public function delText(){
		$request=I('post.');
		$add=M("advert_text")->where($request)->delete();
		if($add==="false"){
			die(json_encode(['status'=>1000,'msg'=>'删除失败']));
		}else{
			die(json_encode(['status'=>0,'msg'=>'删除成功']));
		}
	}
}