<?php

// +----------------------------------------------------------------------
// | FileName:   ProductModel.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/7 12:47
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace  Admin\Model;

use Think\Model;

class ProductModel extends BaseModel{

    public $price_init_set;
    public $tableInfo;

    /*
     * 产品列表
     *
     */
    public function productList($where='',$page='',$pageSize='',$level=true,$order='',$field='',$show_site='back',$tableArr='*'){
        $session=session();
        $method_name=MODULE_NAME;
        $key_method=strtolower($method_name);
        if($key_method=='admin'){
            $productPowers=(new UserModel())->departmentDataPower('product',$session['adminInfo']['department_id'],$where,'person_liable');
            if($productPowers['error']!=0) return $productPowers;
            $where=$productPowers['data']['where'];
        }

        if(!isset($where['is_online'])) $where['is_online']=['neq',100];
        $product=M('product')->where($where);
        if($field)$product->field($field);
        if($order)$product->order($order);
        if($page&&$pageSize) $product->limit(($page-1)*$pageSize,$pageSize);
        $productList=$product->select();
        $count=$product->where($where)->count();
        $is_online=$where;
        $is_online['is_online']=0;
        $online0=$product->where($is_online)->count();
        $is_online['is_online']=1;
        $online1=$product->where($is_online)->count();
        $is_online['is_online']=2;
        $online2=$product->where($is_online)->count();
        if(!$productList) return ['error'=>1,'msg'=>'产品信息错误'];
        $productId_arr=$brandId_arr=$person_liable=[];
        if($level){//是否取附表信息
            foreach($productList as $k=>$v){
                $productId_arr[]=$v['id'];
                $productItemNo_arr[]=$v['fitemno'];
                $brandId_arr[]=$v['brand_id'];
                $personLiable_arr[]=$v['person_liable'];
                if($productList[$k]['fitemno_access']){
                    if(count(explode('{}',$productList[$k]['fitemno_access']))==3){
                        $productList[$k]['fitemno_access']='';
                    }else{
                        $productList[$k]['fitemno_access']=preg_replace('/[^{}]+{}[^{}]+{}(.+)/','${1}',$productList[$k]['fitemno_access']);
                    }
                }
            }
            $category=new CategoryModel();

            $priceField='id,p_id,line,lft_num start,right_num end,unit_price price';
            if($show_site=='back') $priceField.=',price_ratio';
            $productPrice=$this->productPrice($productId_arr,$priceField);

            $erp=new ErpproductModel();
            $erpProduct=$erp->erpProduct($productItemNo_arr);//erp商品数据
            $stock_data=($erpProduct['error']==0)?$erpProduct['data']:'';
            $prodcutPdf=$this->prodcutPdf($productId_arr);//pdfs产品说明书
            $brand=$this->baseListType(M('brand'),$brandId_arr,'id');//品牌名称
            $personLiable=$this->baseListType(M('user','sys_'),$personLiable_arr,'uid');//产品负责人
            if($personLiable['error']==0){ $personLiableData=$personLiable['data']['list']; }
            if($brand['error']==0){ $brandData=$brand['data']['list']; }
            $pdf_data=$prodcutPdf['data']['list'];
            if($productPrice['error']!=0) return $productPrice;
            foreach($productList as $k=>$v){
                $one_prices=$productPrice['data'][$v['id']];
                $last_price=array_pop($one_prices);
                $productList[$k]['price_section']=$productPrice['data'][$v['id']];
                $productList[$k]['pdf']=$pdf_data[$v['id']]['pdf'];
                $productList[$k]['price']=$last_price['price'];
//                $productList[$k]['store']=($stock_data[$v['fitemno']]['store']>0)?$stock_data[$v['fitemno']]['store']:0;
                $productList[$k]['store']=$stock_data[$v['fitemno']]['store'];
                if($show_site=='back'){
                    $productList[$k]['last_fstcb']=$stock_data[$v['fitemno']]['last_fstcb'];
                    $productList[$k]['fstcb']=$stock_data[$v['fitemno']]['fstcb'];
                    $productList[$k]['person_liable_name']=$personLiableData[$v['person_liable']]['fullname'];
                }
                $oneCategory=$category->oneCategoryInfo($v['cate_id']);
                $productList[$k]['cate_name']=$oneCategory['data']['one']['cate_name'];
                $productList[$k]['brand_name']=$brandData[$v['brand_id']]['brand_name'];
            }
        }
        return ['error'=>0,'data'=>['list'=>$productList,'count'=>['count'=>$count,'is_online0'=>$online0,'is_online1'=>$online1,'is_online2'=>$online2]]];
    }


    /*
     * 产品价格列表
     * $productId_arr=[1,2,3,4]
     *
     */
    public function productPrice($productId_arr,$field='id,p_id,line,lft_num start,right_num end,unit_price price'){
        $price=M('product_price')->field($field)->where(['p_id'=>['in',$productId_arr]])->order('id asc,line asc')->select();
        $price_return=[];
        foreach($price as $k=>$v){ $price_return[$v['p_id']][]=$v; }
        return ['error'=>0,'data'=>$price_return];
    }

    /*
     * 产品规格书
     * $productId_arr=[1,2,3,4]
     *
     */
    public function prodcutPdf($pId_arr){
        $pdfs=M('product_detail')->where(['p_id'=>['in',$pId_arr]])->select();
        if(!$pdfs) return ['error'=>1,'msg'=>'pdf信息错误'];
        $return=[];
        foreach($pdfs as $k=>$v){
            $return[$v['p_id']]=$v;
        }
        return ['error'=>0,'data'=>['list'=>$return]];
    }

    /*
     * 初始化产品价格
     * $productId_arr=[1,2,3,4]
     * $one_pirce_set=[
           [
               'line'=>1,
                'lft_num' => 0,
                'right_num' => 100,
                'price_ratio' => 2.0,
           ],
            [
                'line'=>2,
                'lft_num' => 100,
                'right_num' => 3000,
                'price_ratio' => 1.5,
            ],
            [
                'line'=>3,
                'lft_num' => 3000,
                'right_num' => 0,
                'price_ratio' => 1.5
            ],
     * ]
     *
     */
    public function productPriceSave($productId,$one_price_set){
        extract($one_price_set[0]);
        if(!isset($price_ratio)||!isset($line)||!isset($start)||!isset($end)) return ['error'=>1,'msg'=>$productId.'----价格参数错误'];

        $oneProduct=D('product')->where(['id'=>$productId])->find();
        if(!$oneProduct) return ['error'=>1,'msg'=>$productId.'----产品信息错误价格'];

        $productPrice=D('product_price')->where(['p_id'=>$productId])->find();

        $price_cost=D('Erpproduct')->erpProduct([$oneProduct['fitemno']]);
        if($price_cost['error']!=0) return $price_cost;
        $one_cost=$price_cost['data'][$oneProduct['fitemno']]['fstcb'];
        if(!(float)$one_cost) return ['error'=>1,'msg'=>$oneProduct['fitemno'].'----成本价不能为0'];

        $error=0;
        D('product_price')->startTrans();
        if($productPrice){
            $delete_result=D('product_price')->where(['p_id'=>$productId])->delete();
            if(!$delete_result){
                $error=1;
                return ['error'=>1,'msg'=>'delete失败'];
            }
        }
        foreach($one_price_set as $set_k=>$set_v){
            $oneData=[
                'p_id'=>$oneProduct['id'],
                'line'=>$set_v['line'],
                'lft_num'=>$set_v['start'],
                'right_num'=>$set_v['end'],
                'price_ratio'=>$set_v['price_ratio'],
                'unit_price'=>$set_v['price_ratio']*$one_cost,
            ];
            $one_result=M('product_price')->add($oneData);
            if(!$one_result){ $error=1;break; }
        }
        if(!$error){
            D('product_price')->commit();
            return ['error'=>0,'msg'=>'插入成功'];
        }else{
            D('product_price')->rollback();
            return ['error'=>1,'msg'=>'插入失败2'];
        }
    }

    /*
     * 产品价格配置信息
     *
     */
    public function priceSelectSet(){
        if(!($price_init_set=$this->price_init_set)) $price_init_set=M('product_price_init','sys_')->order('name,min_num asc,line asc')->select();
        if(!$price_init_set) return ['error'=>1,'msg'=>'产品价格配置信息错误'];
        $price_set=[];
        foreach($price_init_set as $k=>$v){
            $price_set[$v['min_num']][]=$v;
        }
        return ['error'=>0,'data'=>$price_set[0]];
    }

    /*
     * 产品列表编辑或新增
     *
     */
    public function productListAction($post){
        $error=[];
        $log=[];
        //失败或成功 需被删除的PDF和图片目录
        $sqlFailNeedDel = [];
        foreach($post['data'] as $k=>$v){
            //在控制器 生成图片失败的PDf需删除
            if( $v['pdf'] === 'storeFaild' ){
                $sqlFailNeedDel['pdf'][] = $v['pdf'];
                $v['pdf'] = null;
            }

            $product_fitemno=$post['product_fitemno'];//关联更多erp型号
//            if(!is_array($product_fitemno)){
//                $log[]=[
//                    'error'=>1,
//                    'msg'=>'关联更多erp型号数错误'
//                ];
//                continue;
//            }
            $product_fitemno[]=[
                'fitemno'=>$v['fitemno'],
                'p_sign'=>$v['p_sign'],
            ];

            $oneAdd=$this->oneProductAction($v,$post['action'],$v['price_section'],$product_fitemno);
            //数据库写入失败
            if($oneAdd['error']!== 0) {
                //数据库写入失败 待删除的PDF文件和生成的图片目录
                $v['pdf'] && $sqlFailNeedDel['pdf'][] = $v['pdf'];
                $v['pdf_img_path'] && $sqlFailNeedDel['pdfImg'][] = $v['pdf_img_path'];
                $error[]=$oneAdd;
            }

            //数据库写入成功 但有待删除的旧的PDF文件和原来生成的图片目录
            if( $oneAdd['error'] === 0 && $oneAdd['oldDetail'] ){
                $sqlFailNeedDel['pdf'][] = $oneAdd['oldDetail']['pdf'];
                $sqlFailNeedDel['pdfImg'][] = $oneAdd['oldDetail']['pdf_img_path'];
            }
            $log[]=$oneAdd;
        }
        if(empty($error)) return ['error'=>0,'msg'=>'操作成功','log'=>$log, 'sqlFailNeedDel'=>$sqlFailNeedDel];
        else return ['error'=>1,'msg'=>'操作失败','data'=>$error,'log'=>$log,'sqlFailNeedDel'=>$sqlFailNeedDel];
    }

    /*
     * 单个产品编辑或新增
     *
     */
    public function oneProductAction($product,$action,$one_price_set,$product_fitemno=[]){
        if(!$product_fitemno||!is_array($product_fitemno)) return ['error'=>1,'msg'=>'商城关连型号错误'];
        //参数默认值
        $product['sys_uid']=session('adminId');//操作人
//        $product['is_online']=2;//待上架

        $goods=[
            'fitemno'=>'',
            'p_sign'=>'',
//            'name'=>'',
//            'model'=>'',
            'unit'=>'',
            'pack_unit'=>'',
            'min'=>'',
            'min_open'=>'',
//            'minorder'=>'',//改
            'parameter'=>'',
            'package'=>'',
//            'batch'=>'',
            'is_earnest'=>'',
            'earnest_scale'=>'',
            'is_delivery'=>'',
            'delivery'=>'',
            'is_online'=>'',
//            'is_default'=>'',
            'show_site'=>'',
            'fitemno_access'=>'',
            'tax'=>'',
            'is_tax'=>'',

//            'sys_uid'=>'',
            'person_liable'=>'',
            'min_price'=>'',
            'cate_id'=>'',
        ];
        foreach($goods as $k=>$v){
            if(!isset($product[$k])) return ['error'=>1,'msg'=>$k.'----商品参数未设置','id'=>$product['id']];
        }
        if(!$product['fitemno']||!$product['p_sign']) return ['error'=>1,'msg'=>'erp型号或商城型号不能为空','id'=>''];

        $fitemno_arr=[$product['fitemno']];
        $personLiable=D('erpproduct')->productPersonLiable($fitemno_arr);
        if($personLiable['error']!==0) return ['error'=>1,'msg'=>'产品负责人信息错误'];
        $personLiable_arr=$personLiable['data'];
        $product['person_liable']=$personLiable_arr[$product['fitemno']];

        //参数检测
        $cate=D('category')->where(['id'=>$product['cate_id']])->find();
        if(!$cate||$cate['lft']+1!=$cate['rht']) return ['error'=>1,'msg'=>'分类信息错误','id'=>$product['id']];
        $brand=D('brand')->where(['id'=>$product['brand_id']])->find();
        if(!$brand) return ['error'=>1,'msg'=>'品牌信息错误','id'=>$product['id']];
        $category = new CategoryModel();
        $categoryIsBottom=$category->categoryIsBottom($product['cate_id']);//是否是底层分类
        if($categoryIsBottom['error']!=0) return array_merge($categoryIsBottom,['id'=>$product['id']]);

        $erpProduct=M('product','erp_')->where(['FItemNo'=>$product['fitemno']])->find();
        if(!$erpProduct) return ['error'=>1,'msg'=>'erp型号错误','id'=>$product['fitemno']];
        if($erpProduct['fstcb']>=$product['min_price']) return ['error'=>1,'msg'=>'最低建议价需要大于成本','id'=>$goods['fitemno']];

        $product['fitemno_access']=$product['p_sign'].'{}'.'---'.'{}'.$product['fitemno_access'];//方便搜索

        $pdf=false;
        if( $product['pdf'] && $product['pdf'] !== 'storeFaild' ){
            $pdf = true;
            //详情写入数据
            $detailData = [
                'p_name'=>$product['p_sign'],
                'pdf'=>$product['pdf'],
                'img_path'=>$product['pdf_img_path'],
            ];
        }

        if($action=='edit'){//编辑
            if(!D('product')->where(['id'=>$product['id']])->find()) return ['error'=>1,'msg'=>$product['id'].'---产品信息错误','id'=>$product['id']];
            $count=M('product')->where([['p_sign'=>$product['p_sign']],'id'=>['neq',$product['id']]])->count();
            if($count) return ['error'=>1,'msg'=>'erp型号或商城型号重复','id'=>$product['id']];
            //旧的PDF和图片等信息
            $oldDetail = [];
            if( $pdf ){
                //查询原来是否已上传PDF
                $detailInfo = M('product_detail')->where(['p_id'=>$product['id']])->find();
                if( $detailInfo ){
                    $oldDetail = [
                        'pdf'=>$detailInfo['pdf'],
                        'pdf_img_path'=>$detailInfo['img_path'],
                    ];
                }
            }

            D('product')->startTrans();
            $product_result=M('product')->field('p_sign',true)->where(['id'=>$product['id']])->save($product);
            $price_result=$this->productPriceSave($product['id'],$one_price_set);
            $product_fitemno_delete=M('product_fitemno')->where(['p_sign'=>$product['p_sign']])->delete();
            if($product_fitemno_delete===false){
                D('product')->rollback();
                return ['error'=>1,'msg'=>'商城关连型号错误','data'=>$price_result,'id'=>$product['id']];
            }
            $product_fitemno_save=M('product_fitemno')->field('p_sign,fitemno')->addAll($product_fitemno);
            if(!$product_fitemno_save){
                D('product')->rollback();
                return ['error'=>1,'msg'=>'商城关连型号错误2','data'=>$price_result,'id'=>$product['id']];
            }
            if($price_result['error']!==0) return $price_result;
            if( $pdf ){
                if( $oldDetail ){
                    $product_pdfRes = M('product_detail')->where(['p_id'=>$product['id']])->save( $detailData );
                }else{
                    $detailData['p_id'] = $product['id'];
                    $product_pdfRes = M('product_detail')->add( $detailData );
                }
                if( $product_pdfRes === false ){
                    D('product')->rollback();
                    return ['error'=>1,'msg'=>'更新失败','data'=>$price_result,'id'=>$product['id']];
                }
            }
            if($product_result!==false&&$price_result['error']==0){
                D('product')->commit();
                return ['error'=>0,'msg'=>'更新成功','id'=>$product['id'], 'oldDetail'=>$oldDetail];
            }else{
                D('product')->rollback();
                return ['error'=>1,'msg'=>'更新失败','data'=>$price_result,'id'=>$product['id']];
            }
        }else if($action=='add'){
            $count=M('product')->where(['p_sign'=>$product['p_sign']])->count();
            if($count) return ['error'=>1,'msg'=>'erp型号或商城型号重复','id'=>''];

            D('product')->startTrans();
            $product_result=M('product')->add($product);
            $price_result=$this->productPriceSave($product_result,$one_price_set);
            $product_fitemno_save=M('product_fitemno')->field('p_sign,fitemno')->addAll($product_fitemno);
            if(!$product_fitemno_save){
                D('product')->rollback();
                return ['error'=>1,'msg'=>'商城关连型号错误2','data'=>$price_result,'id'=>$product['id']];
            }
            if( $pdf ){
                $detailData['p_id'] = $product['id'];
                $product_pdfRes = M('product_detail')->add( $detailData );
                if( $product_pdfRes === false ){
                    D('product')->rollback();
                    return ['error'=>1,'msg'=>'新增失败','data'=>$price_result,'id'=>$product_result];
                }
            }
            if($product_result&&$price_result['error']==0){
                D('product')->commit();
                return ['error'=>0,'msg'=>'新增成功','id'=>$product_result];
            }else{
                D('product')->rollback();
                return ['error'=>1,'msg'=>'新增失败','data'=>$price_result,'id'=>$product_result];
            }
        }
        return ['error'=>1,'msg'=>'field','id'=>$product['id']];
    }


    /*
     * 产品信息部分修改
     * $productId_arr=[1,2,3,4]
     *
     */
    public function productListActionPart($product_arr){
        $error='';
        $log=[];
        if(!$product_arr){
            return ['error'=>1,'msg'=>'没有参数'];
        }
        foreach($product_arr as $k=>$v){
            $one_data=[];
            if(isset($v['id'])){
                $one_data['id']= $v['id'];
            }else{
                return ['error'=>1,'msg'=>'id参数少了'];
            }
            if(isset($v['is_online'])){
                if(!in_array($v['is_online'],[0,1,2,100])) return ['error'=>1,'msg'=>'is_online---值范围错误'];
                $one_data['is_online']= $v['is_online'];
            }
            if(isset($v['cate_id'])){
                if(!M('category')->where(['id'=>$v['cate_id']])->find()) return ['error'=>1,'msg'=>'分类信息错误'];
                $one_data['cate_id']= $v['cate_id'];
            }
            if(isset($v['show_site'])){
                if(!in_array($v['show_site'],[0,1,2,3])) return ['error'=>1,'msg'=>'show_site---值范围错误'];
                $one_data['show_site']= $v['show_site'];
            }

            $one_result=M('product')->save($one_data);
            if($one_result!==false){
                $log[$v['id']]='success';
            }else{
                $error=false;
                $log[$v['id']]='field';
            }
        }
        if(!$error) return ['error'=>0,'log'=>$log];
        else return ['error'=>1,'log'=>$log];
    }

    /*
     *
     * 产品成本和最低建议售价
     *
     */
    public function productMinPriceFstcb($pId_arr){
        $list=$this->productList(['id'=>['in',$pId_arr]]);
        if($list['error']!=0) return $list;
        $data=$list['data']['list'];
        $return_data=[];
        foreach($data as $k=>$v){
            $return_data[$v['id']]=[
                'min_price'=>$v['min_price'],
                'tax'=>$v['tax'],
                'fstcb'=>$v['fstcb'],
                'last_fstcb'=>$v['last_fstcb'],
                'p_id'=>$v['id'],
            ];
        }
        return ['error'=>0,'data'=>['list'=>$return_data]];
    }

    /*
     *
     * 分类商品的一键移动
     *
     */
    public function productCategoryToCategory($cateId,$cateId_to){
        $is_to=D('category')->categoryIsBottom($cateId_to);
        if($is_to['error']!=0) return $is_to;
        $result=M('product')->where(['cate_id'=>$cateId])->save(['cate_id'=>$cateId_to]);
        if($result===false) return ['error'=>1,'msg'=>'更新失败'];
        else return ['error'=>0,'msg'=>'更新成功'];
    }


    /*
     *
     * 顾客商品优惠价格
     * $productList=[
     *      [
     *          pid=>1,
     *          num=>1,
     *          .....
     *          ]
     * ]
     *
     */
    public function customerProductPrice($cusId,$productList){
        $pId_arr=[];
        foreach($productList as $k=>$v){ $pId_arr[]=$v['id']; }
        $productBargain=new ProductbargainModel();
        $list=$productBargain->productDiscountPriceInfo($cusId,$pId_arr);
        return $list;
    }


    /*
     * excel产品新增
     *
     */
    public function productExcelReadInit($data){
		$productExcel = APP_PATH.'/Admin/Conf/productExcel.php';
		$productExcel = include($productExcel);
        $position_config=$productExcel['POSITION'];
        $ftemno_access_config=$productExcel['FITEMNO_ACCESS'];//关联型号
        $pack_unit=$productExcel['PACK_UNIT'];
        $return=[];

        $category=$this->baseList(M('category'));
        $category_type=[];
        foreach($category['data']['list'] as $k=>$v){ $category_type[$v['cate_name']]=$v['id']; }

        $brand=$this->baseList(M('brand'));
        $brand_type=[];
        foreach($brand['data']['list'] as $k=>$v){ $brand_type[$v['brand_name']]=$v['id']; }

        $erpproduct=$this->baseList(M('product','erp_'));
        $erpproduct_type=[];
        foreach($erpproduct['data']['list'] as $k=>$v){
            $erpproduct_type[$v['fitemno']]=$v['fstcb'];
        }

        unset($data[1]);
        unset($data[2]);
        unset($data[3]);

        $one_product=array_values($data);
        $i=-1;
        $j=0;

        $oneProductError=$oneProductPriceError=0;
        $productM=M('product');
        $p_sign_error='';
        foreach($one_product as $k=>$v) {

            $j++;
            $temp = $i;

            $one_add_product=[];
            foreach($position_config as $k2=>$v2){
                $one_add_product[$k2]=$v[$v2];
            }

            if ($j == 4) {
                $j = 1;
                if (!$oneProductError && !$oneProductPriceError) {
                    $product_id='';
                    $productM->commit();
                } else {
                    $productM->rollback();
                }
            }

            if ($k % 3 == 0) {
                $oneProductError=$oneProductPriceError=0;
                $oneResult=false;
                if(!$v[$position_config['p_sign']]) break;
                $productM->startTrans();
                $i++;
                $fstcb = $one_product[$k][$position_config['fstcb']];//表格的成本
            }

            if ($temp != $i) {
                $p_sign_error=$one_product[$k][$position_config['p_sign']];
                if(!isset($erpproduct_type[$one_product[$k][$position_config['fitemno']]])){//erp型号不存在
                    $return['faild'][] = $p_sign_error.'===erp型号不对';//错误记录
                    continue;
                }

                $fitemno_access = $one_product[$k][$position_config['p_sign']] . '{}' . '---' . '{}';
                foreach ($ftemno_access_config as $k2 => $v2) {
                    if ($one_product[$k][$v2]) $fitemno_access .= $one_product[$k][$v2] . '{}';
                }

                $config[$i] = [
                    'cate_id' => $category_type[trim($one_product[$k][$position_config['cate_id']])],
                    'brand_id' => $brand_type[trim($one_product[$k][$position_config['brand_id']])],
                    'pack_unit' => $pack_unit[$one_product[$k][$position_config['pack_unit']]],//包装名称(盘，管，袋)
                    'is_earnest' => (float)$v[$position_config['is_earnest']] ? 1 : 0,//是否需要定金
                    'fitemno_access' => $fitemno_access,//搜索时用（替代料号）
                    'tax' => (int)$one_product[$k][$position_config['tax']] / 100 + 1,//税率
                    'sys_uid' => session('adminId'),//操作人
                    'person_liable' => session('adminId'),
                ];
                $config[$i]['show_site']=(int)$config[$i]['show_site'];
                $config[$i]['is_inquiry_table']=(int)$config[$i]['is_inquiry_table'];
                $config[$i]=array_merge($one_add_product,$config[$i]);
                $product_id=M('product')->field('id')->where(['p_sign'=>$config[$i]['p_sign'],'fitemno'=>$config[$i]['fitemno']])->find();
                $p_id='';
                if($product_id){
                    $config[$i]['id']=$product_id['id'];
                    $price_delete=M('product_price')->where(['p_id'=>$product_id['id']])->delete();
                    $oneResult = $productM->save($config[$i]);
                    $p_id=$product_id['id'];
                }else{
                    $oneResult = $productM->add($config[$i]);
                    $p_id=$oneResult;
                }

                if($oneResult===false||($product_id&&!$price_delete)){
                    $DbError=D()->getDbError();
                    preg_match('/([^\[]+)\[/',$DbError,$showDbError);
                    $originError=$showDbError[0];

                    if(!$this->tableInfo)$this->tableInfo=$productM->query("SHOW full COLUMNS FROM dx_product");
                    $tableInfo=$this->tableInfo;
                    foreach($tableInfo as $k6=>$v6){
                        list($x1,$x2)=explode("'",$showDbError[0]);
                        if($x2==$v6['field']){
                            $showDbError[0]=$v6['comment'];break;
                        }
                    }

                    $return['faild'][] = $one_product[$k][$position_config['p_sign']].'==='.$showDbError[0]."($originError)";//错误记录
                    $destination = C('LOG_PATH').'product_init_'.date('y_m_d').'.log';
//                    \Think\Log::write(,'ALERT','',$destination);
                    \Think\Log::write($DbError.'-----'.D()->getLastSql(),'ALERT','',$destination);
                    $oneProductError = 1;
                }else{
                    $return['success'][] = $one_product[$k][$position_config['p_sign']];
                }
            }

            if($oneResult!==false){
                $onePrice = [
                    'p_id' => $p_id,
                    'line' => $k % 3,
                    'lft_num' => $one_product[$k][$position_config['lft_num']] ? $one_product[$k][$position_config['lft_num']] : 0,
                    'right_num' => $one_product[$k][$position_config['right_num']] ? $one_product[$k][$position_config['right_num']] : 0,
                    'price_ratio' => (float)($one_product[$k][$position_config['price_ratio']] / 100),
                    'fstcb' => $fstcb,
                    'unit_price' => (float)$fstcb * (float)($one_product[$k][$position_config['price_ratio']] / 100),
                ];

                if($one_product[$k][$position_config['p_sign']]){
                    if(!(int)($onePrice['unit_price']*10000)){
                        $oneProductPriceError=2;
                    }
                }else{
                    if($onePrice['right_num']||$onePrice['lft_num']||$onePrice['unit_price']) {
                        if ((int)($onePrice['unit_price'] * 10000)) {
                            if ($onePrice['right_num'] && $onePrice['right_num'] <= $onePrice['lft_num']) {
                                $oneProductPriceError = 2;
                            }else if(!$onePrice['right_num']&&!$onePrice['lft_num']){
                                $oneProductPriceError = 2;
                            }
                        }else{
                            $oneProductPriceError = 2;
                        }
                    }else{
                        continue;
                    }
                }

                if($oneProductPriceError==2){
                    $return['faild'][] = $p_sign_error.'===价格错误';//错误记录
                    continue;
                }

                $config[$i]['goods'][] = $onePrice;
                $price_result = M('product_price')->add($onePrice);

                if (!$price_result) $oneProductPriceError = 1;
            }
        }

        if (!$oneProductError && !$oneProductPriceError) {
            $product_id='';
            $productM->commit();
        } else {
            $productM->rollback();
        }

        return $return;
    }

    /**
     * @desc 产品下载
     *
     */
    public function productExcelDownloadData($productId_arr,$categoryId_arr,$vmId_arr){
        $productExcel = APP_PATH.'/Admin/Conf/productExcel.php';
        $productExcel = include($productExcel);
        $position_config=$productExcel['POSITION_OUT'];
        $price=$productExcel['POSITION_OUT_PRICE'];
        $ftemno_access_config=$productExcel['FITEMNO_ACCESS'];//关联型号
        $pack_unit=$productExcel['PACK_UNIT'];
        $pack_unit_flip=array_flip($pack_unit);
        $config=[ $position_config, $ftemno_access_config, $pack_unit,$price ];

        $where=[];
        if($productId_arr) $where['p.id']=['in',$productId_arr];
        if($categoryId_arr) $where['p.cate_id']=['in',$categoryId_arr];
        if($vmId_arr) $where['p.person_liable']=['in',$vmId_arr];

        $list=M('product')->alias('p')->join('left join dx_product_price pp on p.id=pp.p_id')->where($where)->select();
        $cateId_arr=[];
        $fitemno_arr=[];
        $personLiable_arr=[];
        foreach($list as $k=>$v){
            $cateId_arr[]=$v['cate_id'];
            $fitemno_arr[]=$v['fitemno'];
            $personLiable_arr[]=$v['person_liable'];
        }
        $brands=M('brand')->field('id,brand_name')->select();
        $category=$this->baseListType(M('category'),$cateId_arr,'id');
        $personLiable=$this->baseListType(M('user','sys_'),$personLiable_arr,'uid');
        $erpProduct=$this->baseListType(M('product','erp_'),$fitemno_arr,'FItemNo','fitemno,fstcb');
        $brandId_arr=[];
        foreach($brands as $v){
            $brandId_arr[$v['id']]=$v['brand_name'];
        }
        $count=count($list);
        $outData=[];
        $xuhao=1;
        if($list){
            $x=0;
            foreach($list as $k=>$v){
                $access=explode('{}',$v['fitemno_access']);
                unset($access[0]);
                unset($access[1]);
                if($access){
                    $i=0;
                    foreach($access as $k3=>$v3){
                        $position_config[$ftemno_access_config[$i]]=$ftemno_access_config[$i];
                        $v[$ftemno_access_config[$i]]=$v3;
                        $i++;
                    }
                }
                array_pop($v);
                array_pop($position_config);
                $v['brand_id']=$brandId_arr[$v['brand_id']];
                $v['person_liable']=$personLiable['data']['list'][$v['person_liable']]['fullname'];
                $v['fstcb']=$erpProduct['data']['list'][$v['fitemno']]['fstcb'];
                $v['tax']=($v['tax']-1)*100;
                $v['price_ratio']=(int)($v['price_ratio']*100);
                $x=$k+4;
                $v['cate_id']=$category['data']['list'][$v['cate_id']]['cate_name'];
                $v['pack_unit']=$pack_unit_flip[$v['pack_unit']];
                foreach($v as $k2=>$v2){
                    if($position_config[$k2]) $outData[$position_config[$k2].$x]=$v2;
                }
                $xuhao++;
                $outData['A'.$x]=ceil($xuhao/3);//序号
            }
            return ['error'=>0,'data'=>$outData,'count'=>$count,'config'=>$config];
        }else{
            return ['error'=>1,'msg'=>'商品信息错误'];
        }
    }

    /**
     * @desc 产品下载头部信息
     *
     */
    public function productExcelOutTitle($path){
        $data=(new \Admin\Controller\ExcelController())->excelRead($path);
        if($data['error']!=0) return $data;
        $outTitle=[];
        foreach($data['data']['list'] as $k=>$v){
            foreach($v as $k2=>$v2){
                $outTitle[$k2.$k]=$v2;
            }
        }
        return ['error'=>0,'data'=>['list'=>$outTitle]];
    }

    /**
     * @desc 产品删除
     *
     */
    public function deleteProduct($request){
        $pId_arr=$request['pId_arr'];
        if(!is_array($pId_arr)) return ['error'=>1,'msg'=>'参数错误'];
        $m=M('product');
        $m->startTrans();
        $result=$m->where(['id'=>['in',$pId_arr],'is_online'=>['neq',1]])->delete();
        $result2=M('product_price')->where(['p_id'=>['in',$pId_arr]])->delete();
        $result3=M('product_attr')->where(['p_id'=>['in',$pId_arr]])->delete();

        $has=M('product_detail')->field('pdf,img_path')->where(['p_id'=>['in',$pId_arr]])->select();
        if($has){
            foreach($has as $k=>$v){
                if($v['pdf']&& $v !== '.' && $v !== '..' && $v !== '/'){
                    $v['pdf']=SITE_PATH.$v['pdf'];
                    if(file_exists($v['pdf'])){
                        unlink($v['pdf']);
                    }
                }
                if($v['img_path']&& $v !== '.' && $v !== '..' && $v !== '/'){
                    $v['img_path']=SITE_PATH.$v['img_path'];
                    if(file_exists($v['img_path'])){
//                        deldir( $v['img_path'] );
                    }
                }
            }
            $result4=M('product_detail')->where(['p_id'=>['in',$pId_arr]])->delete();
        }

        if(!$result||!$result2||$result3===false||(isset($result4)&&$result4===false)){
            $m->rollback();
            return ['error'=>1,'msg'=>'删除失败'];
        }else{
            $m->commit();
            return ['error'=>0,'msg'=>'删除成功'];
        }
    }












}