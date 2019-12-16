<?php

// +----------------------------------------------------------------------
// | FileName:   HomeController.class.php
// +----------------------------------------------------------------------
// | Dscription:   前台基类控制器
// +----------------------------------------------------------------------
// | Date:  2017/7/31 13:32
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace  Home\Controller;

use Common\Controller\BaseController;
use EES\System\Redis;
use THink\Controller;
use Common\Controller\Category;
use Common\Controller\Address;

class CodeController extends BaseController
{

		public function checkCode(){
			$save['code']=I('code');
			if(!$save['code']){
				die(json_encode(['error'=>1,'msg'=>"中奖码不能为空"]));
			}
			$save['user_id']=$_SESSION['id'];
			if(!$save['user_id']){
				die(json_encode(['error'=>1,'msg'=>"请先登录"]));
			}
			if($_SESSION['user_type']==20){
				die(json_encode(['error'=>1,'msg'=>"您是子账号,暂时不能参与"]));
			}
			$save['create_time']=time();
			
			$fres=M('code_goods')->where(['code'=>$save['code'],'status'=>['neq',2]])->find();
			if($fres){
				die(json_encode(['error'=>1,'msg'=>"您的抽奖码已经存在,请检查后再提交"]));
			}
			$fres=M('code_goods')->where(['user_id'=>$save['user_id'],'status'=>['neq',2]])->find();
			if($fres){
				die(json_encode(['error'=>1,'msg'=>"您已经提交过中奖码"]));
			}
			$add=M('code_goods')->add($save);
			if($add===false){
				die(json_encode(['error'=>1,'msg'=>"提交失败"]));
			}else{
				die(json_encode(['error'=>1,'msg'=>"提交成功,请等待审核"]));
			}
		}
		
		public function codeIndex(){
			if(!$_SESSION['id']){
				die(json_encode(['error'=>1,'msg'=>"您没权限,请先登录"]));
			}
			$res=M('code_goods')->where(['user_id'=>$_SESSION['id']])->order("create_time desc")->select();
			$this->assign('res',$res);
			$this->assign('isCode',true);
			$this->display("Code/codeWinning");
		}
		
		
		public  function adminCodeIndex(){
			//$res=M('code_goods')->where(['user_id'=>$_SESSION['id']])->order("create_time desc")->select();
			$res=M()->query("select du.nick_name,dxg.* from dx_code_goods dxg,dx_user du WHERE  dxg.user_id=du.id");
			$this->assign('res',$res);
			$this->display();
		}
		public  function codeOne(){
			$id=I('id');
			$save['status']=I('status');
			$save['goods']=I('goods');
			$save['remark']=I('remark');
			$s=M('code_goods')->where(['id'=>$id])->save($save);
			if($s===false){
				die(json_encode(['error'=>1,'msg'=>'保存失败']));
			}else{
				die(json_encode(['error'=>0,'msg'=>'保存成功']));
			}
		}
	
		public  function codeTwo(){
			$id=I('id');
			$save['status']=I('status');
			$s=M('code_goods')->where(['id'=>$id])->save($save);
			if($s===false){
				die(json_encode(['error'=>1,'msg'=>'保存失败']));
			}else{
				die(json_encode(['error'=>0,'msg'=>'保存成功']));
			}
		}

}