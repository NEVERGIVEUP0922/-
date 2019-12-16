<?php
/**
 * Created by PhpStorm.
 * User: dxsj009
 * Date: 2018\11\26 0026
 * Time: 16:05
 */

namespace Admin\Controller;



use Admin\Model\SolutionModel;
use Common\Controller\Upload2Controller as Upload;
//use Home\Controller\SolutionUploadController;
//use Home\Controller\SolutionController as HomeSolution;

class SolutionController extends AdminController
{
	use Upload;
	protected function _initialize()
	{
		parent::_initialize();

//		$this->solModel = new SolutionModel();
		$this->solModel = D('Solution');
		$session=session();
		$this->adminFront=[
			'uid'=>$session['adminId'],
			'user_name'=>$session['adminInfo']['user_name'],
		];
		header('X-Frame-Options:SAMEORIGIN');
		if(IS_AJAX){
			header('Content-Type:application/json; charset=utf-8');
		}
	}
	protected  $solModel;
	protected 	$adminFront;
	//供应方案列表(非自营)
	public function solutions()
	{
		$request = I('get.');
//		print_e($request);die;
		$proList = $this->solModel->solution3($request,1);
		foreach($proList['list'] as &$v){
			if(!empty($v)){
				$v['pcba_list'] =explode(',',$v['pcba_list']);
				$v['imgs']		=explode(',',$v['imgs']);
				/*foreach($v['imgs'] as &$v){//转义至oss,可删除
					$v = '/Uploads/'.$v;
				}*/
			}
		}
		$types_temp	= $this->solModel->solTypes(1);
		$types		= $types_temp['list'];
		$sysUserInfo= $this->solModel->sysUserInfo('','uid,nickname');
		//print_e($proList);die;
		$request['where']['sol_status']['value']=$request['where']['sol_status']['value']?$request['where']['sol_status']['value']:3;
		$this->assign('request',$request);
		$this->assign('types',$types);
		$this->assign('sysUserInfo',$sysUserInfo);
		$this->assign('solList',$proList);
		$this->assign('prefix',C("access_oss.prefix"));//oss访问前.后缀
		$this->assign('suffix',C("access_oss.suffix"));
		$this->display("Solution/Solutions");
	}
	//需求方案列表(非自营)
	public function desirSolutions()
	{
		$request = I('get.');
		$proList=	 $this->solModel->solution3($request,2);
		$types_temp	= $this->solModel->solTypes(1);
		$types		= $types_temp['list'];
		$sysUserInfo=$this->solModel->sysUserInfo('','uid,nickname');
//		$proList['page'] = $page;
//		$proList['pageSize'] = $pageSize;
//		print_e($proList);die;
		$request['where']['sol_status']['value']=$request['where']['sol_status']['value']?$request['where']['sol_status']['value']:3;
		$this->assign('request',$request);
		$this->assign('types',$types);
		$this->assign('sysUserInfo',$sysUserInfo);
		$this->assign('solList',$proList);
		$this->display("Solution/desirSolutions");
	}
	//供应方案审核
	public function proAudit()
	{
		if(IS_AJAX && IS_POST){
			$request = I('post.');
			if($request['pro_sn'] && $request['check_status']){
				$this->solAudit($request,1,(int)$request['check_status']);
			}
		}
	}
	//需求方案审核
	public function desirAudit()
	{
		if(IS_AJAX && IS_POST){
			$request = I('post.');
//			print_e($request);
			if($request['desir_sn'] && $request['check_status']){
				$this->solAudit($request,2,(int)$request['check_status']);
			}
		}
	}
	//供应方案审核(自营)
	public function selfAudit_p()
	{
		if(IS_AJAX && IS_POST){
			$request = I('post.');
			if($request['pro_sn'] && $request['check_status']){
				$this->solAudit($request,3,(int)$request['check_status']);
			}
		}
	}
	//供应需求方案审核(省略)

	/*方案审核(公共部分)
	 * param:$request:需要的数据,必须包含编号
	 * param:$type:(int),1供应方案,2需求方案,3自营供应方案,4自营需求方案.
	 * param:action:(int),1通过,2不通过
	 * param:$data_add:包含写入信息表所需的,方案sol_id,方案发布人sol_uid
	 * return:ajax返回
	 * */
	private function solAudit($request,$type,$action)
	{
		$sys_userId = $this->adminFront['uid'];
		$data_add	= ['sol_uid'=>$request['sol_uid'],'sol_id'=>$request['sol_id']];
		$t = date('Y-m-d',time());
//			print_e($request);die;
		if($action===1){//通过
			$data_add['action'] = '通过';
			$data = ['check_status' => 1,'publish_status'=>1,'sys_uid'=>$sys_userId,'check_time'=>$t];
		}elseif($action===2){//不通过
			$data_add['action'] = '不通过';
			$data = ['check_status' => 2,'publish_status'=>0,'sys_uid'=>$sys_userId,'check_time'=>$t];
		}else{
			$this->ajaxReturnStatus(1002,'数据错误!');
		}
		if(($type === 1 ||$type === 3) && !empty($request['pro_sn'])){//供应方案
			if($type  === 3){//是否自营
				$where ['jl_self'] = 1;
			}
			$where['pro_sn'] = $request['pro_sn'];
			$rez  = $this->solModel->solAudit($data,$where,1,$data_add);
		}elseif(($type === 2 ||$type === 4) && !empty($request['desir_sn'])){//需求方案
			if(	  $type === 4){//是否自营
				$where ['jl_self'] = 1;
			}
			$where['desir_sn'] = $request['desir_sn'];
			$rez  = $this->solModel->solAudit($data,$where,2,$data_add);
		}else{
			$this->ajaxReturnStatus(1003,'数据错误!');
		}
		if($rez===true){//积分

			if(!isset($where['jl_self'])&&$data_add['sol_uid']){
				if($type ===1)	objectIntergral($data_add['sol_uid'],112,'供应方案');
				if($type ===2)	objectIntergral($data_add['sol_uid'],113,'需求方案');
			}
			$this->ajaxReturnStatus(1000,'操作成功!');
		}else{
			$this->ajaxReturnStatus(1001,'操作失败!');
		}
	}

	//查看洽谈
	public function converseList()
	{
		$request = I('get.');
//		print_e($request);die;
		if(!empty($request['desir_sn'])){		$where['desir_sn'] = $request['desir_sn'];	};
		$res = $this->solModel->converseList($where);
		$this->assign('res',$res);
		$this->display('Solution/converList');
	}
	//自营方案(查看方案)
	public function mysolList()
	{
		$request = I('get.');
		$mysolList  = $this->solModel->solution3($request,3);
		foreach($mysolList['list'] as &$v){
			if(!empty($v)){
				$v['pcba_list'] =explode(',',$v['pcba_list']);
				$v['imgs']		=explode(',',$v['imgs']);
				/*foreach($v['imgs'] as &$v){//可以优化路径储存//已优化
					$v = '/Uploads/'.$v;
				}*/
			}
		}
//		print_e($request);
//		print_e($mysolList);die;
		$types_temp	= $this->solModel->solTypes(1);
		$types		= $types_temp['list'];
		$sysUserInfo= $this->solModel->sysUserInfo('','uid,nickname');
		$request['where']['sol_status']['value']=$request['where']['sol_status']['value']?$request['where']['sol_status']['value']:3;
		$this->assign('request',$request);
		$this->assign('types',$types);
		$this->assign('sysUserInfo',$sysUserInfo);
		$this->assign('solList',$mysolList);
		$this->assign('prefix',C("access_oss.prefix"));//oss访问前.后缀
		$this->assign('suffix',C("access_oss.suffix"));
		$this->display("Solution/mysolList");

	}
	//上传自营方案(供应)
	public function addProvider()
	{
		$action = I('post.action');
		if(!IS_AJAX && IS_GET){//页面展示
			$types_temp	= $this->solModel->solTypes(1);
			$types		= $types_temp['list'];
			$this->assign('types',$types);
			$this->assign('tree','');
			$this->assign('list','');
			$this->assign('request','');
			$this->assign('fatherPath','');
			$this->display();
		}elseif(IS_AJAX && IS_POST && $action==1){//文件上传
			$solutionUpload = new \Home\Controller\SolutionUploadController(true);
			$rez = $solutionUpload -> webUpload_oss(true);
			if($rez == false){
				die(json_encode( ['error' => 1, 'msg'=>'上传失败,请稍后再试!']));
			}else{
				die(json_encode(['error'=>0,'msg' => $rez]));
			}
			//上传功能转移至Home/SolutionUpload类
//			$userId = session('adminId');
//			$upload = new \Think\Upload();// 实例化上传类
//			$upload->rootPath = './Uploads/'; // 设置附件上传根目录
//			$upload->savePath = 'Solution/'.$userId.'/';
//			$upload->subName ='imgs';
//			$upload->maxSize = 5*1024*1024;
//			$upload->exts = array('jpg','gif','png','jpeg');
//
//			$info =$upload->upload();
//			if(!$info) die(json_encode( ['error' => 1, 'msg' => $upload->getError()]));
//			$img  = './Uploads/'. $info['file']['savepath'].$info['file']['savename'];
//			$temp =$this->thumb($img,300,300,true,true );
//			if(isset($temp) && $temp != 1){
//				$info['file']['savename'] = $temp;
//			}
//			die(json_encode(['error'=>0,'msg'=>$info]));

		}elseif(IS_AJAX && IS_POST){//方案提交or保存
			$data = I('post.');
			$id   = $data['pro_id'];
			$draft= $data['draft'];
			//生成or修改html文件
			$data['pro_desc'] = isset($data['pro_desc'])?htmlspecialchars_decode($data['pro_desc']):'';
			if(!$id && $data['pro_desc']){//生成
				$html_name = session('adminId').time().rand(100,999);
				$html_rez  = $this->create_html($data['pro_desc'],$html_name);
				if($html_rez !== false){
					$data['pro_desc'] = $html_rez;
				}else{
					$this->ajaxReturnStatus(1034,'保存失败!');
				}
			}elseif($id && $data['html_path']){//修改
				$temp = $this->create_html($data['pro_desc'],$data['html_path'],true);
				if($temp == false) $this->ajaxReturnStatus(1033,'保存失败!');
				$data['pro_desc'] = $data['html_path'];
				unset($data['html_path']);
			}else{
				$this->ajaxReturnStatus(1021,'数据有误,提交失败!');
			}
			//数据验证.//处理数据
			$key_word  =['types','pcba','pcba_list','synopsis','pro_desc','pro_name','main_brand','main_model','imgs','sys_owner'];//关键字

			if($data['pro_id']){
				$key_word[]='pro_id';
			}
			if(isset($data['imgs']) && !empty($data['imgs'])){
				$cover_image  = $data['imgs'][0];
				$data['imgs'] = implode(',',$data['imgs']);
			}
			foreach($data as $k=>$v){
				$arr_k[]= $k;
			}
			foreach($data as $k=>$v){
				if($k=='synopsis'&&strlen($v)>200){
					$data[$k] = substr($v,0,200);
					continue;
				};
				if(($k =='pcba_list'&& empty($v) &&((int)$data['pcba']===0))){
					continue;
				}
				if(($k == 'pcba')&& ((int)$v ===0)){
					continue;
				}
				if(($k == 'pcba')&& ((int)$v ===0) && empty($data['pcba_list'])){
					//pcba ===0时,pcba_list可以为空
					continue;
				}
				$v = trimall($v);
				if(!in_array($k,$key_word)||!(isset($v) && !empty($v))){
					$this->ajaxReturnStatus(1019,'数据有误,提交失败!');
				}
			}
			if((count($key_word) != count($arr_k)) || array_diff($key_word,$arr_k)){
				$this->ajaxReturnStatus(1018,'数据有误,提交失败!');
			}
			$data['conver_image'] = $cover_image;//设置封面
//			print_e($cover_image);die;
			//业务员提交
			$data['uid'] = session('adminId');
			$data['jl_self'] = 1;
			$data['check_status'] = 0;
			$data['publish_status'] = 0;
			//保存or提交
			$field  = "jl_self,pro_sn,pro_name,main_model,main_brand,synopsis,pro_desc,types,pcba,pcba_list,conver_image,imgs,uid,sys_uid,sys_owner,draft,check_status,publish_status";
			if(isset($draft ) && $draft ==1){//草稿(后台要求不要草稿)
				if(isset($id) && $id){//草稿修改
					$rez= D('solution_provider')->where(['id'=>$id,'uid'=>$data['uid']])->field($field)->save($data);
					if($rez===false)$this->ajaxReturnStatus(1026,'修改失败!');
					if($rez===0		)$this->ajaxReturnStatus(1028,'没有内容修改!');
					$this->ajaxReturnStatus(1000,'修改成功!');
				}else{//(第一次)草稿保存
					$num   = $this->solModel->solSn(1);/*获得供应方案编号*/
					if(!$num) $this->ajaxReturnStatus(1034,'提交失败!');
					$data['pro_sn'] = $num;
					$rez   = D('solution_provider')->field($field)->add($data);
					if($rez){
						$this->ajaxReturnStatus(1000,'保存成功!',$rez);
					}elseif($rez===false){
						$this->ajaxReturnStatus(1026,'修改失败!');
					}elseif($rez===0	 ){
						$this->ajaxReturnStatus(1028,'没有内容修改!');
					}
				}
			}elseif($draft === null||$draft==0){//提交
				if(isset($id) && $id){//修改提交
					$data['draft']= 0;
					$rez   = D('solution_provider')->where(['id'=>$id,'uid'=>$data['uid']])->field($field)->save($data);
//					getsql();die;
					if($rez===false){
						$this->ajaxReturnStatus(1026,'提交不成功!');
					}elseif($rez===0){
						$this->ajaxReturnStatus(1028,'没有内容修改!');
					}else{
						$this->ajaxReturnStatus(1000,'提交成功!',$rez);
					}
				}else{//(第一次)直接提交
					$num   = $this->solModel->solSn(1);/*获得供应方案编号*/
					if(!$num) $this->ajaxReturnStatus(1034,'提交失败!');
					$data['pro_sn'] = $num;
					$rez   = D('solution_provider')->field($field)->add($data);
					if($rez===false){
						$this->ajaxReturnStatus(1026,'提交不成功!');
					}elseif($rez===0){
						$this->ajaxReturnStatus(1028,'没有内容修改!');
					}else{
						$this->ajaxReturnStatus(1000,'提交成功!',$rez);
					}
				}
			}
		}
	}
	//修改自营方案供应(上架.下架)
	public function updataProvider()
	{
		$request = I('post.');
		$_model	 ='solutionProvider';
		$sys_userId  = $this->adminFront['uid'];
		if(empty($request['actioin'])||$request['pro_sn']){
			$action  = $request['action'];
			$pro_sn  = $request['pro_sn'];
			$where 	 = ['pro_sn'=>$pro_sn];
		}else{
			$this->ajaxReturnStatus(1001,'请求数据错误!');
		}
		$info = M($_model)->where($where)->find();
		if(empty($info))	$this->ajaxReturnStatus(1003,'查询方案错误!');

//		if( $info['sys_uid'] != $sys_userId ){//判断业务范围
//			$this->ajaxReturnStatus(1004,'您不是此方案负责人,不能进行审核操作!');
//		}
		if($action ==2 && $info['publish_status']==1){//下架
			$data= ['publish_status'=>2];
			$rez = M($_model)->where($where)->save($data);
//			getsql();print_e($request);die;
			if(!empty($rez))	$this->ajaxReturnStatus(1000,'下架成功!');

		}elseif($action ==3 && ($info['publish_status']==0||$info['publish_status']==2) &&$info['check_status'] ==1){//上架
			$data= ['publish_status'=>1];
			$rez = M($_model)->where($where)->save($data);
			if(!empty($rez))	$this->ajaxReturnStatus(1000,'上架成功!');

		}else{
			$this->ajaxReturnStatus(1002,'未知错误,编辑过后的方案需重新审核');
		}
	}
	//自营方案编辑页面
	public function draftOfPro()
	{
		$sys_userId  = $this->adminFront['uid'];
		$request = I('get.');
		$_model	 = 'solutionProvider';
		$where 	 = ['id'=>$request['id']];
		if(!IS_AJAX && IS_GET){
			$types_temp	= $this->solModel->solTypes(1);
			$types		= $types_temp['list'];
			$sysUserInfo= $this->solModel->sysUserInfo('','uid,nickname');

			$info = M($_model)->where($where)->find();
			if($info['pro_desc']){
				$html	= file_get_contents('.'.$info['pro_desc']);
			}
//			print_e($sysUserInfo);getsql();print_e($info);die;
			$this->assign('desc_html',$html);
			$this->assign('types',$types);
			$this->assign('sysUserInfo',$sysUserInfo);
			$this->assign('proInfo',$info);
			$this->assign('prefix',C("access_oss.prefix"));//oss访问前.后缀
			$this->assign('suffix',C("access_oss.suffix"));
			$this->display('Solution/editPro');
		}
	}
	//删除方案
	public function delete_mysol()
	{
		$sys_userId  = $this->adminFront['uid'];
		$request = I('get.');
		$_model	 = 'solutionProvider';
		$where 	 = ['id'=>$request['id']];
		if(IS_AJAX && IS_GET){
			M()->startTrans();
			$info = M($_model)->where($where)->find();
//			getsql();
			if(!$info){
				M()->rollbock();
				$this->ajaxReturnStatus(1001,'没有数据!');
			}
			$temp = M($_model)->where($where)->field('delete')->save(['delete'=>1]);
//			getsql();die;
			if(!$temp){
				M()->rollbock();
				$this->ajaxReturnStatus(1002,'删除失败!');
			}
			M()->commit();
			$this->ajaxReturnStatus(1000,'删除成功!');
		}
	}
	//上传自营方案(需求)(暂时不需要)
	public function uploadDesired()
	{

	}
	//查看方案类型
	public function typesList()
	{
		$request = I('get.');
		$page	 =$request['page']?$request['page']:1;
		$pageSize=$request['pageSize']?$request['pageSize']:10;
		$limit	 =($page-1)*$pageSize.','.$pageSize;
		$action  =1;//1查看,
		//print_e($request);die;
		if(isset($request['keyword'])){
			$where['types'] = ['like','%'.$request['keyword'].'%'];
			$solTypes = $this->solModel->solTypes($action,'',$where,$page,$pageSize,$limit);
		}else{
			$solTypes = $this->solModel->solTypes($action,'','',$page,$pageSize,$limit);
		}
		$this->assign('request',$request);
		$this->assign('solTypes',$solTypes);
		$this->display('Solution/updataTypes');die;
	}
	//更改方案类型
	public function updataTypes()
	{
		if(IS_AJAX){//增加,修改,删除
			$request = I('post.');
		}
		$action  = $request['action']?$request['action']:1;//2增加,3删除,4修改
		switch($action){
			case 2://类型增加
				$where= ['types'=>$request['types']];
				$data = ['types'=>$request['types']];
				if(empty($data))$this->ajaxReturnStatus(1001,'数据有误');
				$res  = $this->solModel->solTypes($action,$data,$where);
				break;
			case 3://类型删除
				$ids   =$request['typesId'];
				if(empty($ids))$this->ajaxReturnStatus(1002,'数据有误');
				$where =['id'=>['in',$ids]] ;
				$res   = $this->solModel->solTypes($action,'',$where);
				break;
			case 4://类型修改
				$where =['id'=>$request['id']] ;
				$data  = ['types'=>$request['types']];;
				if(empty($where)||empty($data))$this->ajaxReturnStatus(1003,'数据有误');
				$res   = $this->solModel->solTypes($action,$data,$where);
				break;
			default:$this->ajaxReturnStatus(1004,'系统错误,请联系管理员');;
		}
		if($res===false){
			$this->ajaxReturnStatus(1005,'操作失败');
		}elseif($res===0){
			$this->ajaxReturnStatus(1006,'没有数据变化');
		}else{
			$this->ajaxReturnStatus(1000,$res);
		}
	}

	//	生成html文档;
	//当$update=true,所有参数为保存的数据:$data是数据;$name是文件名,
	//当$update=false,所有参数为修改的数据:$data是数据;$name是旧文件地址,
	public function create_html($data,$name,$update=false)
	{
		if($update==false){
			$content = $data;
			//print_e($content);die;
			$the_date=date('Ymd',time());
			$root_path = $_SERVER['DOCUMENT_ROOT'];//绝对路径根目录
			$content_path =$root_path. '/Uploads/htmls/'.$the_date.'/';//文件夹
			if(!is_dir($content_path)){
				$rez = mkdir($content_path,0777,true);
			}
			$base_path = '/Uploads/htmls/'.$the_date.'/'.$name.'.html';//文件路径
			$file_path = $root_path.$base_path;//绝对路径
			$files_new = fopen($file_path,'w+');//开始写入
			$write_new = fwrite($files_new,$content);
			$colse_new = fclose($files_new);
//			print_e($file_path);dump($files_new);dump($write_new);die;
			if($files_new && $write_new ){
				return $base_path;
			}else{
				return false;
			}
			die('创建成功');
		}elseif($update==true){
			if(empty($data)){
				return true;//如果没有数据,则不修改
			}else{
				$temp = file_put_contents('.'.$name,$data,LOCK_EX);
				if(!$temp) return false;
				return $temp;
			}
		}
	}
}