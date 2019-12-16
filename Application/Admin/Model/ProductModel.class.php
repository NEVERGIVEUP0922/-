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

     * $pId_fitemno_arr=[

     *  p_id=>fitemno

     * ];

     *

     */

    public function productList($where='',$page='',$pageSize='',$level=true,$order='',$field='',$show_site='back',$tableArr=[],$pId_fitemno_arr=[],$p_sign=''){

//        echo microtime().'------------------------<br/>';

        $session=session();

        $method_name=MODULE_NAME;

        $key_method=strtolower($method_name);

        if($key_method=='admin'){

            $productPowers=(new UserModel())->departmentDataPower('product',$session['adminInfo']['department_id'],$where,'person_liable');

            if($productPowers['error']!=0) return $productPowers;

            $where=$productPowers['data']['where'];

        }



        if(!isset($where['is_online'])) $where['is_online']=['neq',100];

        $product=M('product')->where([$where]);

        if($field)$product->field($field);

        if($order)$product->order($order);

        if($page&&$pageSize) $product->limit(($page-1)*$pageSize,$pageSize);

        $productList=$product->select();



        if(!$productList) return ['error'=>1,'msg'=>'产品信息错误'];

        $fitemno_arr=$productId_arr=$brandId_arr=$person_liable=$pSign_arr=$package_arr=[];



        $count=$product->where($where)->count();

        $online_where=$where;

        unset($online_where['is_online']);

        $is_online=$is_online1=$is_online2=$online_where;

        $is_online[]['is_online']=0;

        $is_online1[]['is_online']=1;

        $is_online2[]['is_online']=2;



        $online0=$product->where($is_online)->count();

        $online1=$product->where($is_online1)->count();

        $online2=$product->where($is_online2)->count();



        if($level){//是否取附表信息

            foreach($productList as $k=>$v){



                $productList[$k]['p_sign']=trim($v['p_sign']);

                $v['p_sign']=trim($v['p_sign']);



                if($pId_fitemno_arr&&$pId_fitemno_arr[$v['id']]){

                    $v['fitemno']=$pId_fitemno_arr[$v['id']];

                    $productList[$k]['fitemno']=$v['fitemno'];

                }

                $productId_arr[]=$v['id'];

                $productItemNo_arr[]=$v['fitemno'];

                $pSign_arr[]=$v['p_sign'];

                $brandId_arr[]=$v['brand_id'];

                //$attrId_arr[]=$v['attr_id'];

                $package_arr[]=$v['package'];

                if($productList[$k]['fitemno_access']){

                    $one_fitemno_access=explode('{}',$productList[$k]['fitemno_access']);

                    if(count($one_fitemno_access)==3&&!$one_fitemno_access[2]){

                        $productList[$k]['fitemno_access']='';

                    }else{

                        $productList[$k]['fitemno_access']=preg_replace('/[^{}]+{}[^{}]+{}(.+)/','${1}',$productList[$k]['fitemno_access']);

                    }

                }

            }

            $pSign_arr=array_unique($pSign_arr);

            if($p_sign){

                $fitemno_where=[ 'p_sign'=>[['in',$pSign_arr],['like','%'.$p_sign.'%']],'fitemno'=>['like','%'.$p_sign.'%'],'_logic'=>'or'];

            }else{

                $fitemno_where=[ 'p_sign'=>['in',$pSign_arr]];

            }

            if(isset($productPowers['data']['fitemno_where'])&&$productPowers['data']['fitemno_where']) $fitemno_where[]=$productPowers['data']['fitemno_where'];



            $product_fitemno=$this->baseList(M('product_fitemno'),$fitemno_where,'','','','p_sign,fitemno');

            $productAttribute=$this->baseListType(M('product_attribute'),$productId_arr,'id');//产品属性



            //取出产品封面图片

            $package_arr=array_unique($package_arr);

            $coverImg=D('Admin/Product','Event')->productsInfo($package_arr);



            if($product_fitemno['error']===0){

                $productItemNo_arr2=[];

                foreach($product_fitemno['data']['list'] as $k=>$v){

                    $productItemNo_arr2[]=$v['fitemno'];

                }

                $productItemNo_arr2=array_unique($productItemNo_arr2);

                $productItemNo_arr=array_merge($productItemNo_arr2,$productItemNo_arr);

            }



            $productItemNo_arr=array_unique($productItemNo_arr);

            $category=new CategoryModel();



            $priceField='id,p_id,line,lft_num start,right_num end,unit_price price';

            if($show_site=='back') $priceField.=',price_ratio';

            $productPrice=$this->productPrice($productId_arr,$priceField);



            if(in_array('dx_product_fitemno',$tableArr)){

                $pSignFitemno_arr=$this->baseListType(M('product_fitemno'),$pSign_arr,'p_sign','p_sign,fitemno','',1,$fitemno_where);

            }



            $erp=new ErpproductModel();

            $erpProduct=$erp->erpProduct($productItemNo_arr);//erp商品数据

//            echo D()->getLastSql().'------------------------<br/>';

//            echo microtime().'------------------------<br/>';

            $stock_data=($erpProduct['error']==0)?$erpProduct['data']:'';

            $prodcutPdf=$this->prodcutPdf($productId_arr);//pdfs产品说明书

            //$attr=$this->baseListType(M('product_attribute_type'),$attrId_arr,'id');//品牌名称

            $brand=$this->baseListType(M('brand'),$brandId_arr,'id');//品牌名称

            $person_liable_show=$this->fitemnoShowPersonLiable($productItemNo_arr);//产品负责人



            if($brand['error']==0){ $brandData=$brand['data']['list']; }

            $pdf_data=$prodcutPdf['data']['list'];

            if($productPrice['error']!=0) return $productPrice;

            foreach($productList as $k=>$v){

                $one_prices=$productPrice['data'][$v['id']];

                $last_price=array_pop($one_prices);

                $productList[$k]['price_section']=$productPrice['data'][$v['id']];

                $productList[$k]['pdf']=$pdf_data[$v['id']]['pdf'];

                $productList[$k]['cover_image']=$coverImg[0][$v['package']][0]['img'];//产品封面

                $productList[$k]['price']=$last_price['price'];

                $productList[$k]['store']=$stock_data[$v['fitemno']]['store'];

                if($show_site=='back'){

                    $productList[$k]['last_fstcb']=$stock_data[$v['fitemno']]['last_fstcb'];

                    $productList[$k]['fstcb']=$stock_data[$v['fitemno']]['fstcb'];

                }

                $oneCategory=$category->oneCategoryInfo($v['cate_id']);

                $productList[$k]['cate_name']=$oneCategory['data']['one']['cate_name'];

                $productList[$k]['brand_name']=$brandData[$v['brand_id']]['brand_name'];

                //$productList[$k]['attr_list']=$attr['data']['list'][$v['attr_id']];

                $productList[$k]['person_liable_name']='';

                if(in_array('dx_product_fitemno',$tableArr)){

                    $v['p_sign']=trim($v['p_sign']);

                    foreach($pSignFitemno_arr['data']['list'][$v['p_sign']] as $k2=>$v2){

                        $v['fitemno']=trim($v['fitemno']);

                        $productList[$k]['person_liable_name'].=$v2['fitemno'].'('.$person_liable_show['data'][$v2['fitemno']].')<br/>';

                        $pSignFitemno_arr['data']['list'][$v['p_sign']][$k2]['person_liable_name']=$person_liable_show['data'][$v2['fitemno']];

                    }

                    $productList[$k]['product_fitemno']=$pSignFitemno_arr['data']['list'][$v['p_sign']];

                }

//                print_r($productAttribute['data']['list']);die();

                if($productAttribute['data']['list'][$v['id']]){//产品属性

                    foreach($productAttribute['data']['list'][$v['id']] as $kk2=>$vv2){

                        if(in_array($kk2,['current_start', 'current_end', 'voltage_input_start', 'voltage_input_end', 'voltage_output_start', 'voltage_output_end','custom_start' ,'custom_end','custom1_start' ,'custom1_end','custom2_start' ,'custom2_end'])){

                            $productAttribute['data']['list'][$v['id']][$kk2]=$vv2/1000;

                        }

                    }

                    $productList[$k]=array_merge($productList[$k],$productAttribute['data']['list'][$v['id']]);

                }

            }

        }



//        echo microtime().'------------------------<br/>';die();



        return ['error'=>0,'data'=>['list'=>$productList,'count'=>['count'=>$count,'is_online0'=>$online0,'is_online1'=>$online1,'is_online2'=>$online2]]];

    }



    public function productHotList($where='',$page='',$pageSize='',$level=true,$order='',$field='',$show_site='back',$tableArr=[],$pId_fitemno_arr=[]){

        //        echo microtime().'------------------------<br/>';

        $session=session();

        $method_name=MODULE_NAME;

        $key_method=strtolower($method_name);

        if($key_method=='admin'){

            $productPowers=(new UserModel())->departmentDataPower('product',$session['adminInfo']['department_id'],$where,'person_liable');

            if($productPowers['error']!=0) return $productPowers;

            $where=$productPowers['data']['where'];

        }



        if(!isset($where['is_online'])) $where['is_online']=['neq',100];

        $product=M('product')->alias("dp")->join("dx_brand as dxb on dxb.id=dp.brand_id")->join("dx_category as dc on dc.id=dp.cate_id")->join("dx_product_attribute as dpa on dpa.id=dp.id")->where([$where]);

        if($field)$product->field($field);

        if($order)$product->order($order);

        if($page&&$pageSize) $product->limit(($page-1)*$pageSize,$pageSize);

        $productList=$product->select();



        if(!$productList) return ['error'=>1,'msg'=>'产品信息错误'];

        $fitemno_arr=$productId_arr=$brandId_arr=$person_liable=$pSign_arr=$package_arr=[];



        $count=M('product')->alias("dp")->join("dx_brand as dxb on dxb.id=dp.brand_id")->join("dx_category as dc on dc.id=dp.cate_id")->join("dx_product_attribute as dpa on dpa.id=dp.id")->where([$where])->count();



        $online_where=$where;

        unset($online_where['is_online']);

        $is_online=$is_online1=$is_online2=$online_where;

        $is_online[]['is_online']=0;

        $is_online1[]['is_online']=1;

        $is_online2[]['is_online']=2;



        $online0=$product->where($is_online)->count();

        $online1=$product->where($is_online1)->count();

        $online2=$product->where($is_online2)->count();



        if($level){//是否取附表信息

            foreach($productList as $k=>$v){



                $productList[$k]['p_sign']=trim($v['p_sign']);

                $v['p_sign']=trim($v['p_sign']);



                if($pId_fitemno_arr&&$pId_fitemno_arr[$v['id']]){

                    $v['fitemno']=$pId_fitemno_arr[$v['id']];

                    $productList[$k]['fitemno']=$v['fitemno'];

                }

                $productId_arr[]=$v['id'];

                $productItemNo_arr[]=$v['fitemno'];

                $pSign_arr[]=$v['p_sign'];

                $brandId_arr[]=$v['brand_id'];

                $package_arr[]=$v['package'];

                if($productList[$k]['fitemno_access']){

                    $one_fitemno_access=explode('{}',$productList[$k]['fitemno_access']);

                    if(count($one_fitemno_access)==3&&!$one_fitemno_access[2]){

                        $productList[$k]['fitemno_access']='';

                    }else{

                        $productList[$k]['fitemno_access']=preg_replace('/[^{}]+{}[^{}]+{}(.+)/','${1}',$productList[$k]['fitemno_access']);

                    }

                }

            }

            $pSign_arr=array_unique($pSign_arr);

            $fitemno_where=[ 'p_sign'=>['in',$pSign_arr] ];

            if(isset($productPowers['data']['fitemno_where'])&&$productPowers['data']['fitemno_where']) $fitemno_where[]=$productPowers['data']['fitemno_where'];



            $product_fitemno=$this->baseList(M('product_fitemno'),$fitemno_where,'','','','p_sign,fitemno');

            $productAttribute=$this->baseListType(M('product_attribute'),$productId_arr,'id');//产品属性



            //取出产品封面图片

            $package_arr=array_unique($package_arr);

            $coverImg=D('Admin/Product','Event')->productsInfo($package_arr);



            if($product_fitemno['error']===0){

                $productItemNo_arr2=[];

                foreach($product_fitemno['data']['list'] as $k=>$v){

                    $productItemNo_arr2[]=$v['fitemno'];

                }

                $productItemNo_arr2=array_unique($productItemNo_arr2);

                $productItemNo_arr=array_merge($productItemNo_arr2,$productItemNo_arr);

            }



            $productItemNo_arr=array_unique($productItemNo_arr);

            $category=new CategoryModel();



            $priceField='id,p_id,line,lft_num start,right_num end,unit_price price';

            if($show_site=='back') $priceField.=',price_ratio';

            $productPrice=$this->productPrice($productId_arr,$priceField);



            if(in_array('dx_product_fitemno',$tableArr)){

                $pSignFitemno_arr=$this->baseListType(M('product_fitemno'),$pSign_arr,'p_sign','p_sign,fitemno','',1,$fitemno_where);

            }



            $erp=new ErpproductModel();

            $erpProduct=$erp->erpProduct($productItemNo_arr);//erp商品数据

            //            echo D()->getLastSql().'------------------------<br/>';

            //            echo microtime().'------------------------<br/>';

            $stock_data=($erpProduct['error']==0)?$erpProduct['data']:'';

            $prodcutPdf=$this->prodcutPdf($productId_arr);//pdfs产品说明书

            $brand=$this->baseListType(M('brand'),$brandId_arr,'id');//品牌名称

            $person_liable_show=$this->fitemnoShowPersonLiable($productItemNo_arr);//产品负责人



            if($brand['error']==0){ $brandData=$brand['data']['list']; }

            $pdf_data=$prodcutPdf['data']['list'];

            if($productPrice['error']!=0) return $productPrice;

            foreach($productList as $k=>$v){

                $one_prices=$productPrice['data'][$v['id']];

                $last_price=array_pop($one_prices);

                $productList[$k]['price_section']=$productPrice['data'][$v['id']];

                $productList[$k]['pdf']=$pdf_data[$v['id']]['pdf'];

                $productList[$k]['cover_image']=$coverImg[0][$v['package']][0]['img'];//产品封面

                $productList[$k]['price']=$last_price['price'];

                $productList[$k]['store']=$stock_data[$v['fitemno']]['store'];

                if($show_site=='back'){

                    $productList[$k]['last_fstcb']=$stock_data[$v['fitemno']]['last_fstcb'];

                    $productList[$k]['fstcb']=$stock_data[$v['fitemno']]['fstcb'];

                }

                $oneCategory=$category->oneCategoryInfo($v['cate_id']);

                $productList[$k]['cate_name']=$oneCategory['data']['one']['cate_name'];

                $productList[$k]['brand_name']=$brandData[$v['brand_id']]['brand_name'];

                $productList[$k]['person_liable_name']='';

                if(in_array('dx_product_fitemno',$tableArr)){

                    $v['p_sign']=trim($v['p_sign']);

                    foreach($pSignFitemno_arr['data']['list'][$v['p_sign']] as $k2=>$v2){

                        $v['fitemno']=trim($v['fitemno']);

                        $productList[$k]['person_liable_name'].=$v2['fitemno'].'('.$person_liable_show['data'][$v2['fitemno']].')<br/>';

                        $pSignFitemno_arr['data']['list'][$v['p_sign']][$k2]['person_liable_name']=$person_liable_show['data'][$v2['fitemno']];

                    }

                    $productList[$k]['product_fitemno']=$pSignFitemno_arr['data']['list'][$v['p_sign']];

                }

                if($productAttribute['data']['list'][$v['id']]){//产品属性

                    foreach($productAttribute['data']['list'][$v['id']] as $kk2=>$vv2){

                        if(in_array($kk2,['current_start', 'current_end', 'voltage_input_start', 'voltage_input_end', 'voltage_output_start', 'voltage_output_end', ])){

                            $productAttribute['data']['list'][$v['id']][$kk2]=$vv2/1000;

                        }

                    }

                    $productList[$k]=array_merge($productList[$k],$productAttribute['data']['list'][$v['id']]);

                }

            }

        }



        //        echo microtime().'------------------------<br/>';die();



        return ['error'=>0,'data'=>['list'=>$productList,'count'=>['count'=>$count,'is_online0'=>$online0,'is_online1'=>$online1,'is_online2'=>$online2]]];

    }



    /*

     * 根据fitemno产品负责人信息

     *

     */

    public function fitemnoShowPersonLiable($fitemno_arr,$field=['trim(fitemno) as fitemno,person_liable']){

        $personLiable=$this->baseList(M('product_fitemno'),['fitemno'=>['in',$fitemno_arr]],'','','',$field[0]);

        $personLiable_arr=$admin_where=[];

        foreach($personLiable['data']['list'] as $k=>$v){

            $personLiable_arr[$v['fitemno']]=$v['person_liable'];

            $admin_where[]=$v['person_liable'];

        }

        $admin_where=array_unique($admin_where);

        $admin=$this->baseListType(M('user','sys_'),$admin_where,'uid','uid,fullname');

        foreach($personLiable_arr as $k=>$v){

            $personLiable_arr[$k]= $admin['data']['list'][$v]['fullname'];

        }

        return ['error'=>0,'data'=>$personLiable_arr];

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

        kelly_log('post-----------：'.json_encode($post),'kelly_one_product__','ALERT');

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



            $product_fitemno_request=$v['product_fitemno'];//关联更多erp型号

//            if(!$product_fitemno_request){

//                $log[]=[

//                    'error'=>1,

//                    'msg'=>'关联更多erp型号参数错误'

//                ];

//                continue;

//            }



            $product_fitemno=[];

            foreach($product_fitemno_request as $k3=>$v3){

                //产品负责人

                $one_person_liable=M('user','sys_')->field('uid')->where(["femplno = (select vm from erp_product where fitemno = '".$v3."')"])->find();

                $product_fitemno[]=[

                    'fitemno'=>$v3,

                    'p_sign'=>$v['p_sign'],

                    'person_liable'=>$one_person_liable['uid'],

                ];

            }



            //产品负责人

            $person_liable=M('user','sys_')->field('uid')->where(["femplno = (select vm from erp_product where fitemno = '".$v[fitemno]."')"])->find();

            if(!$person_liable){

                $error[]=['error'=>1,'msg'=>$v[fitemno].':产品负责人错误'];

                continue;

            }



            $product_fitemno[]=[

                'fitemno'=>$v['fitemno'],

                'p_sign'=>$v['p_sign'],

                'person_liable'=>$person_liable['uid'],

            ];



            $has_unique=[];

            foreach($product_fitemno as $k2=>$v2){//数组去重

                $md5_key=md5(serialize($v2));

                if(in_array($md5_key,$has_unique)) unset($product_fitemno[$k2]);

                else $has_unique[]=$md5_key;

            }



            $oneAdd=$this->oneProductAction($v,$post['action'],$v['price_section'],$product_fitemno);

            //数据库写入失败

            if($oneAdd['error']!== 0) {

                //数据库写入失败 待删除的PDF文件和生成的图片目录

                $v['pdf'] && $sqlFailNeedDel['pdf'][] = $v['pdf'];

                $v['pdf_img_path'] && $sqlFailNeedDel['pdfImg'][] = $v['pdf_img_path'];

                $error[]=$oneAdd;

                \Think\Log::write('productListAction---------'.D()->getDbError().'--------------'.json_encode($post),'ALERT');

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

        $product['note_isShow']=($product['note_isshow']==1)?1:0;

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

//            'person_liable'=>'',

            'min_price'=>'',

            'cate_id'=>'',

            'attr_id'=>'',

        ];

        $position_describe=[];

        foreach($goods as $k=>$v){

            if(!isset($product[$k])){

                if(!$position_describe){

                    $productExcel = APP_PATH.'/Admin/Conf/productExcel.php';

                    $productExcel = include($productExcel);

                    $position_describe=$productExcel['POSITION_DESCRIBE'];

                }

                return ['error'=>1,'msg'=>$position_describe[$k].'----商品参数未设置','id'=>$product['id']];

            }

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



        $erpProduct=M('product','erp_')->where(['ftem'=>$product['fitemno']])->find();

        if(!$erpProduct) return ['error'=>1,'msg'=>'erp型号错误','id'=>$product['fitemno']];

        if($erpProduct['fstcb']>=$product['min_price']) return ['error'=>1,'msg'=>'最低建议价需要大于成本','id'=>$goods['fitemno']];



        if(trim($product['fitemno_access'])) $product['fitemno_access']=$product['p_sign'].'{}'.'---'.'{}'.$product['fitemno_access'];//方便搜索

        else $product['fitemno_access']=$product['p_sign'].'{}'.'---'.'{}';



        $pdf=false;

        if( $product['pdf']!='null'&&$product['pdf'] && $product['pdf'] !== 'storeFaild' ){

            $pdf = true;

            //详情写入数据

            $detailData = [

                'p_name'=>$product['p_sign'],

                'pdf'=>$product['pdf'],

                'img_path'=>$product['pdf_img_path'],

            ];

        }

        $attribute_m=M('product_attribute');
		//以下作备份
		//        $v_str_1000=['current_start','current_end','voltage_input_start','voltage_input_end','voltage_output_start','voltage_output_end','custom_start','custom_end','custom1_start','custom1_end','custom2_start','custom2_end'];
		//        foreach($product as $kk2=>$vv2){
		//            if(in_array($kk2,$v_str_1000)){
		//                $product[$kk2]=$vv2;
		//            }
		//        }
		$v_str_1000=['current_start','current_end','voltage_input_start','voltage_input_end','voltage_output_start','voltage_output_end','custom_start','custom_end','custom1_start','custom1_end','custom2_start','custom2_end','volume_length','volume_width'];

		foreach($product as $kk2=>$vv2){

			if(in_array($kk2,$v_str_1000)){

				$product[$kk2]=(int)($vv2*1000);
			}
		}

        $product_field='id,fitemno,p_sign,cate_id,brand_id,sell_num,click_num,unit,pack_unit,min,min_open,parameter,package,batch,is_earnest,earnest_scale,create_time,update_time,is_delivery,delivery,is_online,cover_image,show_site,fitemno_access,is_tax,tax,discount_num,sys_uid,note,note_isShow,describe,is_inquiry_table,min_price,describe_image,search_top,is_search_top,attr_id';



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



            $add_product_data=$product;

            unset($add_product_data['price_section']);

            $add_product_data['show_site']=$add_product_data['show_site']?:0;

            D('product')->startTrans();

            $product_field_edit='fitemno,cate_id,brand_id,sell_num,click_num,unit,pack_unit,min,min_open,parameter,package,batch,is_earnest,earnest_scale,create_time,update_time,is_delivery,delivery,is_online,cover_image,show_site,fitemno_access,is_tax,tax,discount_num,sys_uid,note,note_isShow,describe,is_inquiry_table,min_price,describe_image,search_top,is_search_top,attr_id';

            $product_result=M('product')->field($product_field_edit)->where(['id'=>$product['id']])->save($add_product_data);

            $price_result=$this->productPriceSave($product['id'],$one_price_set);

            $product_fitemno_delete=M('product_fitemno')->where(['p_sign'=>$product['p_sign']])->delete();

            if($product_fitemno_delete===false){

                D('product')->rollback();

                return ['error'=>1,'msg'=>'商城关连型号错误','data'=>$price_result,'id'=>$product['id']];

            }



            //商品更多属性编辑

            $has_attribute=$attribute_m->field('id')->where(['id'=>$product['id']])->find();

            $attribute_field='id,current_start,current_end,voltage_input_start,voltage_input_end,voltage_output_start,voltage_output_end,volume_length,volume_width,custom_start,custom_end,custom1_start,custom1_end,custom2_start,custom2_end';

            $attribute_result='';

            if($has_attribute){

                $attribute_result=$attribute_m->field($attribute_field)->where(['id'=>$product['id']])->save($product);

                //print_r(D()->getLastSql());

            }else{

                $attribute_result=$attribute_m->field($attribute_field)->where(['id'=>$product['id']])->add($product);

            };

            if($attribute_result===false){

                D('product')->rollback();

                return ['error'=>1,'msg'=>'商品属性错误1','data'=>$price_result,'id'=>$product['id']];

            }



            $product_fitemno_save=M('product_fitemno')->field('p_sign,fitemno,person_liable')->addAll($product_fitemno);

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

//            $count=M('product')->where(['p_sign'=>$product['p_sign']])->count();

            $count=M('product')->where('p_sign="'.$product['p_sign'].'"')->count();

            if($count) return ['error'=>1,'msg'=>'erp型号或商城型号重复','id'=>''];



            $add_product_data=$product;

            unset($add_product_data['price_section']);



            D('product')->startTrans();

            $add_product_data['show_site']=$add_product_data['show_site']?:0;

            $product_result=M('product')->field($product_field)->add($add_product_data);

            if(!$product_result){

                kelly_log('商品添加错误:-------'.D()->getLastSql(),'kelly_product_attribute_error','ALTER');

                D('product')->rollback();

                return ['error'=>1,'msg'=>$add_product_data['p_sign'].'商品添加错误'];

            }

            $price_result=$this->productPriceSave($product_result,$one_price_set);

            $product_fitemno_save=M('product_fitemno')->field('p_sign,fitemno,person_liable')->addAll($product_fitemno);



            $product['id']=$product_result;

            //商品更多属性编辑

            $attribute_field='id,current_start,current_end,voltage_input_start,voltage_input_end,voltage_output_start,voltage_output_end,volume_length,volume_width,custom_start,custom_end,custom1_start,custom1_end,custom2_start,custom2_end';

            $attribute_result='';

//            $attribute_result=$attribute_m->field($attribute_field)->where(['id'=>$product['id']])->add($product);

            $attribute_add_data=$product;

            foreach($attribute_add_data as $k=>$v){

                if(strpos($attribute_field,$k)===false) unset($attribute_add_data[$k]);

            }

            $attribute_result=$attribute_m->field($attribute_field)->add($attribute_add_data);

            if($attribute_result===false){

                D('product')->rollback();

                kelly_log('商品属性错误22:-------'.D()->getLastSql(),'kelly_product_attribute_error','ALTER');

                return ['error'=>1,'msg'=>'商品属性错误22','data'=>$price_result,'id'=>$product['id']];

            }



            if(!$product_fitemno_save){

                D('product')->rollback();

                return ['error'=>1,'msg'=>'商城关连型号错误3','data'=>$price_result,'id'=>$product['id']];

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

        $msg=D()->getLastSql().'error_sql:'.D()->getDbError();

        kelly_log('产品上传：'.$msg,'kelly_one_product__','ALERT');

        kelly_log('提交的数据：'.json_encode([$product,$action,$one_price_set,$product_fitemno]),'kelly_one_product__','ALERT');

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

            $field='';

            $one_data=[];

            if(isset($v['id'])){

                $one_data['id']= $v['id'];

            }else{

                return ['error'=>1,'msg'=>'id参数少了'];

            }

            if(isset($v['is_online'])){

                if(!in_array($v['is_online'],[0,1,2,100])) return ['error'=>1,'msg'=>'is_online---值范围错误'];

                $one_data['is_online']= $v['is_online'];

                $field.='is_online';

            }

            if(isset($v['cate_id'])){

                if(!M('category')->where(['id'=>$v['cate_id']])->find()) return ['error'=>1,'msg'=>'分类信息错误'];

                $one_data['cate_id']= $v['cate_id'];

                $field.=',cate_id';

            }

            if(isset($v['show_site'])){

                if(!in_array($v['show_site'],[0,1,2,3])) return ['error'=>1,'msg'=>'show_site---值范围错误'];

                $one_data['show_site']= $v['show_site'];

                $field.=',show_site';

            }

            if($field&&strpos($field,',')!==false){

                $field=substr($field,1);

            }



            $one_result=M('product')->field($field)->where(['id'=>$one_data['id']])->save($one_data);

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

    public function productMinPriceFstcb($pId_arr,$pId_fitemno_arr=[]){

        $list=$this->productList(['id'=>['in',$pId_arr]],'','',true,'','','back','',$pId_fitemno_arr);

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

        $pack_unit=$productExcel['PACK_UNIT'];

        $position_config_flip=array_flip($position_config);

        $return=[];



        $category=$this->baseList(M('category'));

        $category_type=[];

        foreach($category['data']['list'] as $k=>$v){ $category_type[$v['cate_name']]=$v['id']; }



        $brand=$this->baseList(M('brand'));//品牌

        $brand_type=[];

        foreach($brand['data']['list'] as $k=>$v){ $brand_type[$v['brand_name']]=$v['id']; }



        $erpproduct=$this->baseList(M('product','erp_'),'','','','','id,fstcb,store,vm,vm_name,create_time,last_time,ftem,ftem as fitemno');//成本

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

                if($v[$position_config['fitemno_access']]){

                    $fitemno_access.=  str_replace(';','{}',$v[$position_config['fitemno_access']]);

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

                $config[$i]['note_isShow']=($config[$i]['note_isshow']==1)?1:0;

                $config[$i]['is_inquiry_table']=(int)$config[$i]['is_inquiry_table'];

                $config[$i]=array_merge($one_add_product,$config[$i]);

                $product_id=M('product')->field('id')->where([['p_sign'=>$config[$i]['p_sign'],'fitemno'=>$config[$i]['fitemno']]])->find();

                $p_id='';



                if($product_id){

                    $config[$i]['id']=$product_id['id'];

                    $price_delete=M('product_price')->where([['p_id'=>$product_id['id']]])->delete();



                    $product_field='fitemno,cate_id,brand_id,unit,pack_unit,min,min_open,parameter,package,batch,is_earnest,earnest_scale,create_time,update_time,is_delivery,delivery,is_online,cover_image,show_site,fitemno_access,is_tax,tax,discount_num,sys_uid,note,note_isShow,describe,is_inquiry_table,min_price,describe_image,search_top,is_search_top';

                    $product_save_data=$config[$i];

                    unset($product_save_data['id']);

                    foreach($product_save_data as $k_prudct=>$v_product){

                        if(strpos($product_field,$k_prudct)===false) unset($product_save_data[$k_prudct]);

                    }

                    $oneResult = $productM->where([['id'=>$product_id['id']]])->field($product_field)->save($product_save_data);

//                    //临时

//                    $oneResult = $productM->field('is_search_top',true)->save($config[$i]);



                    $p_id=$product_id['id'];

                }else{

                    $product_field='p_sign,fitemno,cate_id,brand_id,unit,pack_unit,min,min_open,parameter,package,batch,is_earnest,earnest_scale,create_time,update_time,is_delivery,delivery,is_online,cover_image,show_site,fitemno_access,is_tax,tax,discount_num,sys_uid,note,note_isShow,describe,is_inquiry_table,min_price,describe_image,search_top,is_search_top';

                    $oneResult = $productM->field($product_field)->add($config[$i]);

                    $p_id=$oneResult;

//                    //临时

//                    $oneResult = $productM->field('is_search_top',true)->add($config[$i]);

//                    $p_id=$oneResult;

                }



//                if($oneResult===false||($product_id&&$price_delete!==false)){//错误处理

                if($oneResult===false||($price_delete===false)){//错误处理

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

//                    \Think\Log::write($DbError.'-----'.D()->getLastSql(),'ALERT','',$destination);

                    kelly_log($DbError.'-----'.D()->getLastSql(),'kellyExcelProduct____','ALERT');

                    kelly_log($one_product,'kellyExcelProduct____','ALERT');

                    kelly_log($config[$i],'kellyExcelProduct____','ALERT');

                    $oneProductError = 1;

                }else{

                    //产品属性添加或编辑

                    $attributeM=M('product_attribute');



                    $val_x_1000=['AD','AE','AF','AG','AH','AI'];//单位转换



                    $attributeR='';

                    $attributeData=[];

                    $attributeData['id']=$p_id;

                    foreach($v as $kk1=>$vv1){

                        if(in_array($kk1,$val_x_1000)){

                            $vv1*=1000;

                        }

                        $attributeData[$position_config_flip[$kk1]]=(int)$vv1;

                    }

                    $attributeM_field='id,current_start,current_end,voltage_input_start,voltage_input_end,voltage_output_start,voltage_output_end,volume_length,volume_width';

                    if($attributeM->field('id')->where([['id'=>$p_id]])->find()){

                        $attributeR=$attributeM->field($attributeM_field)->where([['id'=>$p_id]])->save($attributeData);

                    }else{

                        $v['id']=$p_id;

                        $attributeR=$attributeM->field($attributeM_field)->add($attributeData);

                    }

                    if($attributeR===false){

                        $return['faild'][] = $one_product[$k][$position_config['p_sign']].'===产品属性更新失败';//错误记录

                        $oneProductError = 1;

                        continue;

                    }



                    //多关联erp型号product_fitemno

                    $one_productFitemno= trim($one_product[$k][$position_config['product_fitemno']]);



                    $one_productFitemno_arr=[];

                    if(trim($one_productFitemno)) $one_productFitemno_arr=explode(';',$one_productFitemno);

                    $one_productFitemno_arr[]=$one_product[$k][$position_config['fitemno']];

                    $one_productFitemno_arr=array_unique($one_productFitemno_arr);

                    $one_count=M('product','erp_')->where([['FItemNo'=>['in',$one_productFitemno_arr]]])->count();



                    if((int)count($one_productFitemno_arr)&&(int)$one_count!==(int)count($one_productFitemno_arr)){

                        $return['faild'][] = $one_product[$k][$position_config['p_sign']].'===erp型号错误';//错误记录

                        $oneProductError = 1;

                        continue;

                    }

                    $one_product_fitemno=[];

                    foreach($one_productFitemno_arr as $k9=>$v9){

                        if($v9) $v9=trim($v9);

                        $sysUser=M('user','sys_')->field('uid')->where('FEmplNo = (select vm as FEmplNo from erp_product where fitemno="'.$v9.'")')->find();

                        if(!$sysUser){

                            $return['faild'][] = $v9.'===产品负责人错误2';//错误记录

                            $oneProductError = 1;

                            break;

                        }

                        $one_product_fitemno[]=[

                            'p_sign'=>$one_product[$k][$position_config['p_sign']],

                            'fitemno'=>$v9,

                            'person_liable'=>$sysUser['uid']

                        ];

                    }

                    $product_fitemno='p_sign,fitemno,create_at,update_at,person_liable';

                    if($oneProductError==0){

                        if(M('product_fitemno')->where([['p_sign'=>$one_product[$k][$position_config['p_sign']]]])->delete()===false){

                            $return['faild'][] = $one_product[$k][$position_config['p_sign']].'===多erp型号保存错误';//错误记录

                            $oneProductError = 1;

                        }else{

                            $product_fitemno_result=M('product_fitemno')->field($product_fitemno)->addAll($one_product_fitemno);

                            if(!$product_fitemno_result){

                                $return['faild'][] = $one_product[$k][$position_config['p_sign']].'===多erp型号保存错误2';//错误记录

                                $oneProductError = 1;

                            }

                        }

                    }



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



                $price_code=0;

                if($one_product[$k][$position_config['p_sign']]){

                    if(!(int)($onePrice['unit_price']*10000)){

                        $price_code=1;

                        $oneProductPriceError=2;

                    }

                }else{

                    if($onePrice['right_num']||$onePrice['lft_num']||$onePrice['unit_price']) {

                        if ((int)($onePrice['unit_price'] * 10000)) {

                            if ($onePrice['right_num'] && $onePrice['right_num'] <= $onePrice['lft_num']) {

                                $price_code=2;

                                $oneProductPriceError = 2;

                            }else if(!$onePrice['right_num']&&!$onePrice['lft_num']){

                                $price_code=3;

                                $oneProductPriceError = 2;

                            }

                        }else{

                            $price_code=4;

                            $oneProductPriceError = 2;

                        }

                    }else{

                        continue;

                    }

                }



                if($oneProductPriceError==2){

                    $return['faild'][] = $p_sign_error.'===价格错误';//错误记录

                    \Think\Log::write($p_sign_error.'===价格错误.code:'.$price_code,'ALERT ');

                    continue;

                }



                $product_price_field='p_id,line,lft_num,right_num,price_ratio,unit_price';

                $config[$i]['goods'][] = $onePrice;

                $price_result = M('product_price')->field($product_price_field)->add($onePrice);



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

        $position_config=$productExcel['POSITION'];

        $position_describe=$productExcel['POSITION_DESCRIBE'];

        $price=$productExcel['POSITION_OUT_PRICE'];

        $pack_unit=$productExcel['PACK_UNIT'];

        $pack_unit_flip=array_flip($pack_unit);



        $config=$productExcel;



        $where=[];

        if($productId_arr) $where['p.id']=['in',$productId_arr];

        if($categoryId_arr) $where['p.cate_id']=['in',$categoryId_arr];



        if($vmId_arr){

            $vm_str='"';

            $vm_str.=implode('","',$vmId_arr);

            $vm_str.='"';

            $where[]='p.p_sign in (select p_sign from dx_product_fitemno where person_liable in ('.$vm_str.'))';

        }



        $list=M('product')->field('p.*,pp.p_id,pp.line,pp.lft_num,pp.right_num,pp.price_ratio,pp.unit_price')->alias('p')->join('left join dx_product_price pp on p.id=pp.p_id')->order('p_sign asc')->where($where)->select();

        $list_pSign=M('product')->field('p_sign,count(pp.id) as price_num')->alias('p')->join('left join dx_product_price pp on p.id=pp.p_id')->where($where)->group('p_sign')->select();

        $list_pSign_arr=[];

        $count=count($list_pSign)*3;

        foreach($list_pSign as $k=>$v){

            $list_pSign_arr[$v['p_sign']]=$v['price_num'];

        }



        $pSign_arr__=array_keys($list_pSign_arr);

        $pSign_fitemno=M('product_fitemno')->field('p_sign,fitemno')->where(['p_sign'=>['in',$pSign_arr__]])->select();//多关联erp型号

        $pSign_fitemno_arr=[];

        foreach($pSign_fitemno as $k=>$v){

            if(isset($pSign_fitemno_arr[$v['p_sign']])){

                $pSign_fitemno_arr[$v['p_sign']].=';'.$v['fitemno'];

            }else{

                $pSign_fitemno_arr[$v['p_sign']]=$v['fitemno'];

            }

        }



        $cateId_arr=$fitemno_arr=$personLiable_arr=$pId_arr=[];

        foreach($list as $k=>$v){

            $cateId_arr[]=$v['cate_id'];

            $fitemno_arr[]=$v['fitemno'];

            $pId_arr[]=$v['id'];

        }

        $productAttribute=$this->baseListType(M('product_attribute'),$pId_arr,'id','*');



        if($productAttribute['error']===0){

            foreach($productAttribute['data']['list'] as $kkk=>$vvv){

                foreach($vvv as $kkk2=>$vvv2){

                    if(in_array($kkk2,['current_start','current_end','voltage_input_start','voltage_input_end','voltage_output_start','voltage_output_end'])){

                        $productAttribute['data']['list'][$kkk][$kkk2]=$vvv2/1000;

                    }

                }

            }

        }

        $person_liable=$this->baseListType(M('product_fitemno'),$fitemno_arr,'FItemNo','fitemno,person_liable');

        foreach($list as $k=>$v){

            $personLiable_arr[]= $person_liable['data']['list'][$v['fitemno']]['person_liable'];

            $list[$k]['person_liable']=$person_liable['data']['list'][$v['fitemno']]['person_liable'];

            if($productAttribute['data']['list'][$v['id']]){

                $list[$k]=array_merge($productAttribute['data']['list'][$v['id']],$list[$k]);

            }

        }



        $brands=M('brand')->field('id,brand_name')->select();

        $category=$this->baseListType(M('category'),$cateId_arr,'id');

        $personLiable=$this->baseListType(M('user','sys_'),$personLiable_arr,'uid');

        $erpProduct=$this->baseListType(M('product','erp_'),$fitemno_arr,'FItemNo','fitemno,fstcb');

        $brandId_arr=[];

        foreach($brands as $v){

            $brandId_arr[$v['id']]=$v['brand_name'];

        }

        $outData=[];

        $xuhao=1;

        if($list){

            $x=4;

            $excel_data=[];

            $shangyici_pSign='';



            foreach($list as $k=>$v){//补齐3个

                $excel_data[]=$v;

                if($list_pSign_arr[$v['p_sign']]<3){

                    if((int)$list_pSign_arr[$v['p_sign']]==0){

                        $excel_data[]=[];

                        $excel_data[]=[];

                    }else if((int)$list_pSign_arr[$v['p_sign']]==1){

                        $excel_data[]=[];

                        $excel_data[]=[];

                    }else if((int)$list_pSign_arr[$v['p_sign']]==2){

                        if($shangyici_pSign==$v['p_sign']){

                            $excel_data[]=[];

                        }

                    }

                }

                $shangyici_pSign=$v['p_sign'];

            }



            foreach($excel_data as $k=>$v){



                $v['fitemno_access']=preg_replace('/([^{}]+{})([^{}]+{})(.*)/','${3}',$v['fitemno_access']);

                $v['fitemno_access']=str_replace('{}',';',substr($v['fitemno_access'],0,-2));



                $v['brand_id']=$brandId_arr[$v['brand_id']];

                $v['person_liable']=$personLiable['data']['list'][$v['person_liable']]['fullname'];

                $v['fstcb']=$erpProduct['data']['list'][$v['fitemno']]['fstcb'];

                $v['tax']=($v['tax']-1)*100;

                $v['price_ratio']=(int)($v['price_ratio']*100);

                $v['cate_id']=$category['data']['list'][$v['cate_id']]['cate_name'];

                $v['pack_unit']=$pack_unit_flip[$v['pack_unit']];

                $v['product_fitemno']=$pSign_fitemno_arr[$v['p_sign']];



                foreach($v as $k2=>$v2){//设置表格位置

                    if($position_config[$k2]) $outData[$position_config[$k2].$x]=$v2;

                }



                $shangyici_pSign=$v['p_sign'];



                $xuhao++;

                $outData['A'.$x]=ceil($xuhao/3);//序号

                $x++;

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

     * @desc 产品Pdf删除

     *

     */

    public function deleteProductPdf($request){

        $pId_arr=$request['pId_arr'];

        if(!is_array($pId_arr)) return ['error'=>1,'msg'=>'参数错误'];

        $m=M('product');

        $m->startTrans();

        $proPdf=M('product_detail')->where(['p_id'=>['in',$pId_arr]])->find();

        $delPdf=M('product_detail')->where(['p_id'=>['in',$pId_arr]])->delete();



        if($delPdf===false){

            M()->rollback();

            return ['error'=>1,'msg'=>'删除失败'];

        }else{

            foreach ($proPdf as $v){

                if(file_exists($v['pdf'])){//是否存在图片

                    unlink ($v['pdf']);

                }

            }

            M()->commit();

            return ['error'=>0,'msg'=>'操作成功'];

        }

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





    /**

     * @desc 产品封装列表

     *

     */

    public function productPackageList($where,$page,$pageSize){

        $return = ['error'=>1,'msg'=>'没有数据'];



        $where[]=['p.package'=>['neq','']];

        $list=M('product')->alias('p')

            ->join('left join dx_product_package_img as ppi on ppi.package=p.package')

            ->where($where)

            ->field('p.package,ppi.img,p.update_time')

            ->group('p.package')

            ->limit(($page-1)*$pageSize,$pageSize)

            ->select();

        $count=M('product')->alias('p')

            ->join('left join dx_product_package_img as ppi on ppi.package=p.package')

            ->where($where)

            ->field('p.package')

            ->group('p.package')

            ->select();

        $count=count($count);

        if($list){

            $return = ['error'=>0,'data'=>['list'=>$list,'count'=>$count]];

        }

        return $return;

    }



    /**

     * @desc 产品封装图片编辑

     *

     */

    public function productPackageAction($request){

        $m=M('product_package_img');

        if($m->where(['package'=>$request['package']])->field('img')->find()){

            $result=$m->where(['package'=>$request['package']])->field('img')->save($request);

        }else{

            $result=$m->field('package,img')->add($request);

        }

        if(!$result) return ['error'=>1,'msg'=>'fail'];

        else return ['error'=>0,'msg'=>'success'];

    }



    /**

     * @desc 产品批量下架

     */

    public function massProductLowerFrame($where,$request){

        if(!$where) return ['error'=>1,'msg'=>'参数错误'];

        $is_online=(int)$request['is_online']?:0;

        $result=M('product')->field('is_online')->where($where)->save(['is_online'=>$is_online]);

        if(!$result) return ['error'=>1,'msg'=>'fail'];

        else return ['error'=>0,'msg'=>'success'];

    }

















}