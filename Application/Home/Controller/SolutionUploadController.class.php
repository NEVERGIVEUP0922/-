<?php
/**
 * Created by PhpStorm.
 * User: dxsj009
 * Date: 2019\2\13 0013
 * Time: 11:34
 */

namespace Home\Controller;
use OSS\OssClient;
use OSS\Core\OssException;
use Think\Controller;

class SolutionUploadController extends Controller
{
	private $oss_config;
	private $oss_http;
	public function __construct($isAdmin =false)
	{
		if (($isAdmin==false && empty($_SESSION['userId'])) ||($isAdmin==true && empty($_SESSION['adminId']))) {
			die;
		}
		$access_path = I('path.');
		if($access_path[1] != 'editorUPload_oss'){
			vendor('aliyun.autoload');
			$this->oss_config = C('Aliyun_oss');
		}
	}

	//通过富文本编辑器上传
	public function editorUpload_oss()
	{
		$path = vendor('ueditor2.controller');
	}

	//通过webuploader上传
	public function webUpload_oss($isAdmin=false)
	{
		/*vendor('aliyun.autoload');*/
		$data = I('post.');
		$upload = new \Think\Upload();// 实例化上传类
		$upload->rootPath = './Uploads/Solution/'; // 设置附件上传根目录
		$upload->saveName = array('uniqid','');
		switch($data['modname']){
			case 'imgs':
				//$upload->subName ='imgs';
				$upload->maxSize= 2*1024*1024;
				$upload->exts   = array('jpg','gif','png','jpeg');
				$this->oss_http	= array(//定义oss的http头
					OssClient::OSS_HEADERS => array(
						'Cache-Control'=>"max-age=2592000"//网页缓存一个月
					));
				break;
			default:
				$upload->maxSize= 3*1024*1024;
				$upload->exts 	= array('jpg','gif','png','jpeg','bmp','doc','docx','pdf','xlsx','xls','txt','pdf','zip','rar');
				$this->oss_http = array(//定义oss的http头
					OssClient::OSS_HEADERS => array(
						'Content-Disposition'=>'attachment',//强制下载
						'Cache-Control'=>"max-age=2592000"//网页缓存一个月
					));
		}
		$info =$upload->upload();
		if(!$info) die(json_encode( ['error' => 1, 'msg' => $upload->getError()]));//上传至本地失败
		//图片裁剪式压缩`暂时不用
		//if($data['modname'] == 'imgs'){
		//	$img  = './Uploads/'. $info['file']['savepath'].$info['file']['savename'];
		//	$temp = $this->thumb($img,300,300,true,true);
		//	if(isset($temp) && $temp != 1){
		//		$info['file']['savename'] = $temp;
		//	}
		//}

		//上传图片到oss
		$ossConfig = $this->oss_config;
		$accessKeySecret = $ossConfig['KeySecret'];//阿里云后台获取秘钥
		$accessKeyId = $ossConfig['KeyId'];//阿里云后台获取秘钥
		$endpoint = $ossConfig['Endpoint'];//OSS地址
		$bucket = $ossConfig['Bucket'];

		$ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
		//判断bucketname是否存在，不存在就去创建
		if( !$ossClient->doesBucketExist($bucket)){
			$ossClient->createBucket($bucket);
		}
		$object ='solutionFile/'.date('Ymd').'/'.$info['file']['savename'];//想要保存文件的名称
		$file = './Uploads/Solution/'.$info['file']['savepath'].$info['file']['savename'];//文件路径，必须是本地的。

		try{//上传成功
			$headers['Content-Disposition'] ='attachment';
			$rez = $ossClient->uploadFile($bucket,$object,$file,$this->oss_http);
			//这里可以删除上传到本地的文件。
			unlink($file);
			//$oss = $rez['info']['url'];//这是文件访问地址
			if($isAdmin==true) return $object;
			die(json_encode(['error'=>0,'msg'=>$object]));

		} catch(OssException $e){//上传失败
			//print_e($e->getMessage()."\n");
			if($isAdmin==true) return false;
			die(json_encode( ['error' => 1, 'msg' => '上传失败,请稍后再试']));
		}
	}

	//(webUpload)删除图片
	public function delete_img_oss(){
		vendor('aliyun.autoload');
		$data = I('post.');
		$object = $data['src'];
		$ossConfig = $this->oss_config;
		$accessKeySecret = $ossConfig['KeySecret'];//阿里云后台获取秘钥
		$accessKeyId = $ossConfig['KeyId'];//阿里云后台获取秘钥
		$endpoint = $ossConfig['Endpoint'];//OSS地址
		$bucket = $ossConfig['Bucket'];

		try{
			$ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
			$rez = $ossClient->deleteObject($bucket, $object);
			print_e('success');die;
		} catch(OssException $e) {
			printf(__FUNCTION__ . ": FAILED\n");
			printf($e->getMessage() . "\n");
			return;
		}
	}

	//文件下载
	public function file_download(){
		$req = I('post.');
		if(!$req){ 	die;}
		/*$src_arr = json_decode($req);
		$pro_name = $src_arr['pro_name'];unset($src_arr['pro_name']);*/
		$filePath = $req;
		/*if( preg_match('/\.jpg|\.png|\.gif$/is', $filePath) ) {
			//输出
		}*/

		//上传图片到oss
		$ossConfig = $this->oss_config;
		$accessKeySecret = $ossConfig['KeySecret'];//阿里云后台获取秘钥
		$accessKeyId = $ossConfig['KeyId'];//阿里云后台获取秘钥
		$endpoint = $ossConfig['Endpoint'];//OSS地址
		$bucket = $ossConfig['Bucket'];
		// object 表示您在下载文件时需要指定的文件名称，如abc/efg/123.jpg。
		$object = "<yourObjectName>";
		// 指定文件下载路径。
		$localfile = "<yourLocalFile>";
		$options = array(
			OssClient::OSS_FILE_DOWNLOAD => $localfile
		);
		try{
			$ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);

			$ossClient->getObject($bucket, $object, $options);
		} catch(OssException $e){
			printf(__FUNCTION__.": FAILED\n");
			printf($e->getMessage()."\n");
			return;
		}
	}
}