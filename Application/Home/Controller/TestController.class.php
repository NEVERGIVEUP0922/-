<?php
/**
 * Created by PhpStorm.
 * User: daxin
 * Date: 2017/8/1
 * Time: 下午4:17
 */
namespace Home\Controller;

use Think\Controller;

class TestController extends Controller
{
    public function index()
    {
		echo  C("depart_id");die();
 		$this->pdf2png(SITE_PATH."Uploads/Pdf/2018-02-01/5a72eda6762c6.pdf.pdf","Uploads/productPdf/2018-02-01/");
    }
  
 	public  function pdf2png($PDF,$Path){
             if(!extension_loaded('imagick')){
                 echo 111;
                 return false;
             }
             if(!file_exists($PDF)){
                echo 1121;
                 return false;
             }
             $im = new \Imagick();  
             $im->setResolution(280,450);
             $im->setCompressionQuality(100);
             $im->readImage($PDF);
             foreach($im as $Key => $Var){
                 $Var->setImageFormat('png');
                 $Filename = $Path.'/'.'img-'.$Key.'.png';
                 if($Var->writeImage($Filename)==true){
                     $Return[]= $Filename;
                 }
             }
             return $Return;
  }

    public function productList(){
        $productList=D('product')->limit(0,5)->select();
        $img=[
            'http://f.hiphotos.baidu.com/image/pic/item/48540923dd54564e9f275d4ab9de9c82d1584f06.jpg',
            'http://g.hiphotos.baidu.com/image/pic/item/9a504fc2d56285359b5f185a9aef76c6a7ef630a.jpg',
            'http://b.hiphotos.baidu.com/image/pic/item/c2fdfc039245d688f5a1236eadc27d1ed31b245c.jpg',
            'http://f.hiphotos.baidu.com/image/pic/item/5882b2b7d0a20cf40404f8157f094b36adaf9967.jpg',
            'http://e.hiphotos.baidu.com/image/pic/item/cc11728b4710b912d4556034cafdfc0393452267.jpg'
        ];
        foreach($productList as $k=>$v){
            $productList[$k]['num']=1;
            $productList[$k]['img']=$img[$k];
        }
        header('Content-Type:application/json; charset=utf-8');
        die(json_encode($productList));
    }


    public function tableSql(){
        $sql='select COLUMN_NAME from information_schema.COLUMNS where table_name = "dx_product"';
        $test=M()->query($sql);
        $str='';
        foreach($test as $k=>$v){
            $str.=$v['column_name'].',';
        }
        print_r($str);
    }




}