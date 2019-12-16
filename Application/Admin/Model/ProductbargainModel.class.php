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

class ProductbargainModel extends BaseModel
{

    public $price_init_set;

    /*
     * 客户产品议价列表
     *
     */
    public function productBargainList($where='',$page='',$pageSize='',$order='',$tableArr=[]){
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
                $must_where['uid']=['in',$customers['data']];
            }
        }
        if($where){
            if($must_where){
                $where=[$where,$must_where];
            }
        }else if($must_where){
            $where=$must_where;
        }

        $bargain=M('user_product_bargain');
        $list=$this->baseList($bargain,$where,$page,$pageSize,$order);
        if($list['error']!=0) return $list;
        $data=$list['data']['list'];
        $uid=$p_id=$sys_uid=[];
        if($data){
            foreach($data as $k=>$v){
                $one_status='';
                $uid[]=$v['uid'];
                $p_id[]=$v['p_id'];
                $sys_uid[]=$v['sys_uid'];
            }
            $result_pass=$this->productDiscountPriceInfo('',$p_id,$data);//判断价格是否可执行
            foreach($data as $k=>$v){
                $one_pass=$result_pass['data']['list'][$v['uid']][$v['p_id']];
                $list['data']['list'][$k]['is_pass']='是';
                if($one_pass['price_pass']==0&&$one_pass['price_tax_pass']==0&&$one_pass['price_invoice_change_pass']==0){
                    $list['data']['list'][$k]['is_pass']='否';
                }
            }
        }

        $userList=$this->baseListType(M('user'),array_unique($uid),'id');
        $userData=$userList['data']['list'];
        $sale_arr=[];
        if($userData){
            foreach($userData as $k=>$v){
                $sale_arr[]=$v['sys_uid'];
            }
        }
        $sys_uid=array_unique(array_merge($sys_uid,$sale_arr));
        $p_id=array_unique($p_id);
        $pIdList=$this->baseListType(M('product'),$p_id,'id','*');
        $pIdData=$pIdList['data']['list'];

        $sysUserList=$this->baseListType(M('user','sys_'),$sys_uid,'uid');
        $sysUserData=$sysUserList['data']['list'];

        $pSign_arr=[];
        foreach($list['data']['list'] as $k=>$v){
            $sale_one=$userData[$v['uid']]['sys_uid'];
            $one=[
                'fitemno'=>$v['fitemno']?$v['fitemno']:$pIdData[$v['p_id']]['fitemno'],
                'customer_name'=>$userData[$v['uid']]['user_name'],
                'customer_nick_name'=>$userData[$v['uid']]['nick_name'],
                'p_sign'=>$pIdData[$v['p_id']]['p_sign'],
                'is_tax'=>$pIdData[$v['p_id']]['is_tax'],
                'admin_name'=>$sysUserData[$v['sys_uid']]['fullname'].'('.$sysUserData[$v['sys_uid']]['user_name'].')',
                'sale_name'=>$sysUserData[$sale_one]['fullname'].'('.$sysUserData[$sale_one]['user_name'].')',
            ];
            $pSign_arr[]=$one['p_sign'];
            $list['data']['list'][$k]=array_merge($list['data']['list'][$k],$one);
        }

        if(in_array('dx_product_ftiemno',$tableArr['tableName'])){
            if($pSign_arr){
                $prodcuFitemno=$this->baseListType(M('product_fitemno'),array_unique($pSign_arr),'p_sign','p_sign,fitemno','',1);
            }
            foreach($list['data']['list'] as $k=>$v){
                $list['data']['list'][$k]['product_fitemno']=$list['data']['list'][$k]['productFitemno']=$prodcuFitemno['data']['list'][$v['p_sign']];
            }
        }

        return $list;
    }


    /*
     *
     * 客户产品议价批量新增
     *
     */
    public function productBargainActions($request){
        $return=['error'=>0];
        foreach($request as $k=>$v){
            $one=$this->oneProductBargainAction($v);
            if($one['error']==1){
                $return['failed'][]=$one;
                $return['error']=1;
            }else{
                $return['success'][]=$one;
            }
        }
        return $return;
    }


    /*
     *
     * 客户产品议价新增
     *
     */
    public function oneProductBargainAction($data){
        if(!$data) return ['error'=>1,'msg'=>'参数错误'];
        if(!$data['check_id']){
            $check=[
                'uid'=>1,
                'p_id'=>1,
                'discount_price'=>1,
                'discount_price_tax'=>1.1,
                'min_buy'=>1,
                'return_price'=>1,
                //            'is_invoice_change'=>1,
                //            'discount_price_invoce_change'=>1,
            ];
            foreach($check as $k=>$v){
                if(!isset($data[$k])){
                    return ['error'=>1,'msg'=>$k.'-------参数未设置'];
                }else if(!$data[$k]){
                    $data[$k]=0;
                }
            }
        }else{
            $oneCheck=$this->baseList(M('user_product_bargain'),['id'=>$data['check_id']]);
            if($oneCheck['error']!==0) return ['error'=>1,'msg'=>'参数错误'];
            $data=$oneCheck['data']['list'][0];
            unset($data['id']);
        }

        if( $data['is_invoice_change'] && !$data['discount_price_invoice_change']) return ['error'=>1,'msg'=>'换型号开票价格未录入'];//开票变更价格录入
        if( (int)($data['discount_price_invoice_change']*10000) && !$data['is_invoice_change']) return ['error'=>1,'msg'=>'换型号开票价格录入,未开启换型号开票'];//开票变更价格录入

        $session=session();
        $data['sys_user_name']=$session['adminInfo']['user_name'];
        $data['is_invoice_change']=$data['is_invoice_change']?1:0;

        $data['sys_uid']=$session['adminId'];//固定值
        if(!($productInfo=M('product')->where(['id'=>$data['p_id']])->find())){ return ['error'=>1,'msg'=>'产品信息错误']; }
        if(!M('product_fitemno')->field('fitemno')->where(['p_sign'=>$productInfo['p_sign'],'fitemno'=>$data['fitemno']])->find()){
            return ['error'=>1,'msg'=>'商城型号与erp型号关联错误'];
        }
        if(!M('user')->where(['id'=>$data['uid'],'user_type'=>['neq',20]])->find()){ return ['error'=>1,'msg'=>'客户信息错误']; }

        $oneProduct=(new \Admin\Model\ProductModel)->productList(['id'=>$data['p_id']],'','',true,'','','back','',[$data['p_id']=>$data['fitemno']]);
        if($oneProduct['error']!=0)  return $oneProduct;
        $oneData=$oneProduct['data']['list'][0];
        if(!($oneData['fstcb']>0)) return ['error'=>1,'msg'=>'成本信息未设置'];
        if(!($oneData['min_price']>0)) return ['error'=>1,'msg'=>'最低建议价信息未设置'];

        $department=M('user_department','sys_')->where(['id'=>$session['adminInfo']['department_id']])->find();
        if(!$department) return ['error'=>1,'msg'=>'部门信息错误'];
        $role_level=$department['department_level'];

        $role_level=($role_level==1)?2:$role_level;//临时业务员和组长一样

        $msg='添加成功';
        $check_msg='';
        if($role_level==1){//业务员
            if(($data['discount_price']&&(string)($data['discount_price']-$data['return_price'])<(string)$oneData['min_price'])||($data['discount_price_tax']&&(string)($data['discount_price_tax']/$oneData['tax']-$data['return_price'])<(string)$oneData['min_price'])){
                $msg='优惠价需要大于最低建议价';
                $check_msg='(需要组长审核)';
//                return ['error'=>1,'msg'=>'优惠价需要大于最低建议价'];
            }

            if( $data['is_invoice_change'] ){//开票变更价格录入
                if((string)($data['discount_price_invoice_change']/1.1-$data['return_price'])<(string)$oneData['min_price']){
                    $msg='优惠价/1.1-返点单价需要大于最低建议价';
                    $check_msg='(需要经理审核)';
//                    return ['error'=>1,'msg'=>'优惠价/1.1-返点单价需要大于最低建议价'];
                }
            }
        }else if($role_level==2){//组长
            if(($data['discount_price']&&(string)($data['discount_price']-$data['return_price'])<(string)$oneData['fstcb'])||($data['discount_price_tax']&&(string)($data['discount_price_tax']/$oneData['tax']-$data['return_price'])<(string)$oneData['fstcb'])){
                $msg='优惠价需要大于成本价';
                $check_msg='(需要经理审核)';
//                return ['error'=>1,'msg'=>'优惠价需要大于成本价'];
            }

            if( $data['is_invoice_change'] ){//开票变更价格录入
                if((string)($data['discount_price_invoice_change']/1.1-$data['return_price'])<(string)$oneData['fstcb']){
                    $msg='优惠价/1.1-返点单价需要大于成本价';
                    $check_msg='(需要经理审核)';
//                    return ['error'=>1,'msg'=>'优惠价/1.1-返点单价需要大于成本价'];
                }
            }
        }

        //最小购买量必须大于等于价格区间的第二当数量
//        $price_section=$oneData['price_section'];
//        if($price_section[1]['end']){
//            if($data['min_buy']<$price_section[1]['end']) return ['error'=>1,'msg'=>'最小购买量必须设置大于'.$price_section[1]['end']];
//        }

        $last_data=[
            'init_cost'=>$oneData['fstcb'],
            'init_suggest_price'=>$oneData['min_price'],
            ];

        $data=array_merge($data,$last_data);
        $bargain=M('user_product_bargain');

        $delete_id_arr=[$data['p_id']];

        $old=$bargain->field('p_id')->where(['id'=>$data['id']])->find();
        if($old) $delete_id_arr[]= $old['p_id'];

        $bargain->startTrans();
        if(!is_null($delete_id_arr)) $result1=$bargain->where(['uid'=>$data['uid'],'p_id'=>['in',$delete_id_arr]])->save(['is_del'=>1]);
        if(!$data['discount_price_invoice_change']){
            $data['discount_price_invoice_change']=0;
        }
        $result2=$bargain->field('id',true)->add($data);
        if($result1!==false&&$result2){
            $bargain->commit();
            return ['error'=>0,'msg'=>$msg.$check_msg,'id'=>$result2];
        }else{
            $bargain->rollback();
            return ['error'=>1,'msg'=>'添加失败'];
        }
    }

    /*
     *
     * 客户产品议价删除
     *
     */
    public function oneBargainDelete($request){
        $result=M('user_product_bargain')->where(['uid'=>$request['uid'],'p_id'=>$request['p_id']])->save(['is_del'=>1]);
        if($result!==false) return ['error'=>0,'msg'=>'删除成功'];
        return ['error'=>1,'msg'=>'删除失败'];
    }

    /*
     *
     * 客户可用产品议价条目
     *
     * uid;
     * return = [
     *      list=>[
     *          p_id=> []
     *      ]
     * ];
     *
     */
    public function customerDiscountPriceList($cusId,$pid_arr){
        $pid_arr=implode(',',$pid_arr);
        $where=['id in (select max(id) from dx_user_product_bargain where uid = '.$cusId.' and p_id in ('.$pid_arr.') group by p_id) ',['is_del'=>0]];
        $list=$this->productBargainList($where,$page='',$pageSize='');
        if($list['error']!=0) return $list;
        $data=$list['data']['list'];
        $return_data=[];
        foreach($data as $k=>$v){
            $return_data[$v['p_id']]=$v;
        }
        return ['error'=>0,'data'=>['list'=>$return_data]];
    }

    /*
     *
     * 客户产品议价是否可执行信息
     * $cusId=uid,
     * pId_arr=[1,2,3]
     *  $role_level=1/2/3/0
     *  1--(录入人) 业务员，   优惠价高于建议价  ------优惠价 - 返点单价  小于   建议价  作废  ， 优惠价  大于等于   建议价继续执行
        2--(录入人) 组长， 	优惠价高于成本价 -------优惠价 - 返点单价  小于   成本价  作废  ， 优惠价  大于等于   成本价继续执行
        3--(录入人) 总监		不限			-----------------------成本价或建议变高   作废       ， 变低继续执行
     *
     */
    public function productDiscountPriceInfo($cusId,$pId_arr,$data_argument=''){
        $discountData=$pId_fitemno_arr=$discountData_admin=[];

        if($data_argument){//后台查询返回用户的商品的优惠[uid=>p_id=>]
            foreach($data_argument as $k=>$v){
                $discountData[$v['uid']][$v['p_id']]=$v;
            }

            foreach($discountData as $k2=>$v2){//关联erp型号替换
                foreach($v2 as $k=>$v){
                    if($v['fitemno']){
                        $pId_fitemno_arr[$v['p_id']]=$v['fitemno'];
                    }else{
                        $oneProduct=M('product')->field('fitemno')->where(['id'=>$v['p_id']])->find();
                        if(!$oneProduct) return ['error'=>1,'msg'=>'产品id为'.$v['p_id'].'已被删除'];
                        $pId_fitemno_arr[$v['p_id']]=$oneProduct['fitemno'];
                        $discountData[$k2][$k]['fitemno']=$oneProduct['fitemno'];
                    }
                }
            }

            $productResult=(new ProductModel())->productMinPriceFstcb($pId_arr,$pId_fitemno_arr);
            if($productResult['error']!=0) return $productResult;
            $fstcbData=$productResult['data']['list'];

            $sys_uid=[];
            $discountData_admin=[];
            foreach($discountData as $k2=>$v2){
                foreach($v2 as $k=>$v){
                    $discountData[$k2][$k]['min_price']=$fstcbData[$v['p_id']]['min_price'];
                    $discountData[$k2][$k]['fstcb']=$fstcbData[$v['p_id']]['fstcb'];
                    $discountData[$k2][$k]['tax']=$fstcbData[$v['p_id']]['tax'];
                    $sys_uid[]=$v['sys_uid'];
                }
            }

            $user=new UserModel();
            $admin=$user->adminRolePriceLevel($sys_uid);

            if($admin['error']!=0) return $admin;
            $adminData=$admin['data']['list'];
            foreach($discountData as $k2=>$v2){
                foreach($v2 as $k=>$v){
                    $discountData[$k2][$k]['role_level']=$adminData[$v['sys_uid']]['department_level'];
                }
            }

            //执行条件判断,建议价和成本价都是未税的
            foreach($discountData as $k2=>$v2){
                foreach($v2 as $k=>$v){
                    $price_pass=$price_tax_pass=$price_invoice_change_pass=0;//0优惠不通过,1通过
                    $priceTrue=$v['discount_price']-$v['return_price'];
                    $priceTrue_tax=$v['discount_price_tax']-$v['return_price'];
                    $priceTrue_invoice_change=$v['discount_price_invoice_change']-$v['return_price'];
                    $v['price_show']=$v['price_tax_show']=0;

                    $v['role_level']=($v['role_level']==1)?2:$v['role_level'];//临时业务员和组长一样

                    if($v['role_level']==1){//业务员
                        if($priceTrue>0&&(string)$priceTrue>=(string)$v['min_price']) $price_pass=1;
                        if($priceTrue_tax>0&&(string)($priceTrue_tax/$v['tax'])>=(string)$v['min_price']) $price_tax_pass=1;
                        if($v['is_invoice_change']&&$priceTrue_invoice_change>0&&(string)($priceTrue_invoice_change/1.1)>=(string)($v['min_price'])) $price_invoice_change_pass=1;
                    }else if($v['role_level']==2){//组长
                        if($priceTrue>0&&(string)$priceTrue>=(string)$v['fstcb']) $price_pass=1;
                        if($priceTrue_tax>0&&(string)($priceTrue_tax/$v['tax'])>=(string)$v['fstcb']) $price_tax_pass=1;
                        if($v['is_invoice_change']&&$priceTrue_invoice_change>0&&(string)($priceTrue_invoice_change/1.1)>=(string)$v['fstcb']) $price_invoice_change_pass=1;
                    }else if($v['role_level']==3){//总监
                        if($priceTrue>0&&(string)$priceTrue>=(string)$v['fstcb']){
                            $price_pass=1;
                        }
                        if($priceTrue_tax>0&&(string)($priceTrue_tax/1.1)>=(string)$v['fstcb']){
                            $price_tax_pass=1;
                        }
                        if($priceTrue_invoice_change>0&&(string)($priceTrue_invoice_change/1.1)>=(string)$v['fstcb']){
                            $price_invoice_change_pass=1;
                        }

                        if($v['init_cost']>=$v['fstcb']){
                            if($priceTrue_tax>0) $price_tax_pass=1;
                            if($priceTrue>0) $price_pass=1;
                            if($v['is_invoice_change']&&$priceTrue_invoice_change>0) $price_invoice_change_pass=1;
                        }
                    }

                    $discountData[$k2][$k]['price_pass']=$price_pass;
                    $discountData[$k2][$k]['price_tax_pass']=$price_tax_pass;
                    $discountData[$k2][$k]['price_invoice_change_pass']=$price_invoice_change_pass;
                    if($price_invoice_change_pass){
                        $discountData[$k2][$k]['price_tax_pass']=$price_invoice_change_pass;
                        $discountData[$k2][$k]['discount_price_tax']=$v['discount_price_invoice_change'];
                    }
                }
            }

            if($discountData) return ['error'=>0,'data'=>['list'=>$discountData]];
            else  return ['error'=>1,'msg'=>'没有可执行的议价信息'];

        }else{//前台查询返回商品的优惠[p_id=>]
            $list=$this->customerDiscountPriceList($cusId,$pId_arr);
            if($list['error']!=0) return $list;
            $discountData=$list['data']['list'];
        }

        foreach($discountData as $k=>$v){//关联erp型号替换
            if($v['fitemno']){
               $pId_fitemno_arr[$v['p_id']]=$v['fitemno'];
            }else{
                $oneProduct=M('product')->field('fitemno')->where(['id'=>$v['p_id']])->find();
                if(!$oneProduct) return ['error'=>1,'msg'=>'产品id为'.$v['p_id'].'已被删除'];
                $pId_fitemno_arr[$v['p_id']]=$oneProduct['fitemno'];
                $discountData[$k]['fitemno']=$oneProduct['fitemno'];
            }
        }

        $productResult=(new ProductModel())->productMinPriceFstcb($pId_arr,$pId_fitemno_arr);
        if($productResult['error']!=0) return $productResult;
        $fstcbData=$productResult['data']['list'];

        $sys_uid=[];
        $discountData_admin=[];
        foreach($discountData as $k=>$v){
            $discountData[$k]['min_price']=$fstcbData[$v['p_id']]['min_price'];
            $discountData[$k]['fstcb']=$fstcbData[$v['p_id']]['fstcb'];
            $discountData[$k]['tax']=$fstcbData[$v['p_id']]['tax'];
            $sys_uid[]=$v['sys_uid'];
        }

        $user=new UserModel();
        $admin=$user->adminRolePriceLevel($sys_uid);

        if($admin['error']!=0) return $admin;
        $adminData=$admin['data']['list'];
        foreach($discountData as $k=>$v){
            $discountData[$k]['role_level']=$adminData[$v['sys_uid']]['department_level'];
        }

        //执行条件判断,建议价和成本价都是未税的
        foreach($discountData as $k=>$v){
            $price_pass=$price_tax_pass=$price_invoice_change_pass=0;//0优惠不通过,1通过
            $priceTrue=$v['discount_price']-$v['return_price'];
            $priceTrue_tax=$v['discount_price_tax']-$v['return_price'];
            $priceTrue_invoice_change=$v['discount_price_invoice_change']-$v['return_price'];
            $v['price_show']=$v['price_tax_show']=0;

            $v['role_level']=($v['role_level']==1)?2:$v['role_level'];//临时业务员和组长一样

            if($v['role_level']==1){//业务员
                if($priceTrue>0&&(string)$priceTrue>=(string)$v['min_price']) $price_pass=1;
                if($priceTrue_tax>0&&(string)($priceTrue_tax/$v['tax'])>=(string)$v['min_price']) $price_tax_pass=1;
                if($v['is_invoice_change']&&$priceTrue_invoice_change>0&&(string)($priceTrue_invoice_change/1.1)>=(string)($v['min_price'])) $price_invoice_change_pass=1;
            }else if($v['role_level']==2){//组长
                if($priceTrue>0&&(string)$priceTrue>=(string)$v['fstcb']) $price_pass=1;
                if($priceTrue_tax>0&&(string)($priceTrue_tax/$v['tax'])>=(string)$v['fstcb']) $price_tax_pass=1;
                if($v['is_invoice_change']&&$priceTrue_invoice_change>0&&(string)($priceTrue_invoice_change/1.1)>=(string)$v['fstcb']) $price_invoice_change_pass=1;
            }else if($v['role_level']==3){//总监
                if($priceTrue>0&&(string)$priceTrue>=(string)$v['fstcb']){
                    $price_pass=1;
                }
                if($priceTrue_tax>0&&(string)($priceTrue_tax/1.1)>=(string)$v['fstcb']){
                    $price_tax_pass=1;
                }
                if($priceTrue_invoice_change>0&&(string)($priceTrue_invoice_change/1.1)>=(string)$v['fstcb']){
                    $price_invoice_change_pass=1;
                }

                if($v['init_cost']>=$v['fstcb']){
                    if($priceTrue_tax>0) $price_tax_pass=1;
                    if($priceTrue>0) $price_pass=1;
                    if($v['is_invoice_change']&&$priceTrue_invoice_change>0) $price_invoice_change_pass=1;
                }
            }

            $discountData[$k]['price_pass']=$price_pass;
            $discountData[$k]['price_tax_pass']=$price_tax_pass;
            $discountData[$k]['price_invoice_change_pass']=$price_invoice_change_pass;
            if($price_invoice_change_pass){
                $discountData[$k]['price_tax_pass']=$price_invoice_change_pass;
                $discountData[$k]['discount_price_tax']=$v['discount_price_invoice_change'];
            }
        }

        if($discountData) return ['error'=>0,'data'=>['list'=>$discountData]];
        else  return ['error'=>1,'msg'=>'没有可执行的议价信息'];
    }

    /**
     * @desc 议价的报备信息列表
     */
    public function productReportList($where='',$page='',$pageSize='',$order='',$field='',$is_field=''){
        $method_name=MODULE_NAME;
        $key_method=strtolower($method_name);
        if($key_method=='admin'){
            $session=session();
            $productPowers=(new UserModel())->departmentDataPower('order',$session['adminInfo']['department_id'],$where,'sys_uid');
            if($productPowers['error']!=0) return $productPowers;
            $where=$productPowers['data']['where'];
        }
        $list=$this->baseList(M('report'),$where,$page,$pageSize,$order,$field,$is_field);
        if($list['error']==0){
            $reportId_arr=$sysUid_arr=$customerId_arr=[];
            foreach($list['data']['list'] as $k=>$v){
                $reportId_arr[]=$v['id'];
                $customerId_arr[]=$v['user_id'];
                if($v['sys_uid']) $sysUid_arr[]=$v['sys_uid'];
            }
            if($sysUid_arr){
                $adminList=$this->baseListType(M('user','sys_'),$sysUid_arr,'uid','uid,fullname');
                $adminData=$adminList['data']['list'];
            }
            if($customerId_arr){
                $customerList=$this->baseListType(M('user'),$customerId_arr,'id','id,user_name,nick_name,fcustjc');
                $customerListData=$customerList['data']['list'];
            }

            $items=M('report_item')->where(['report_id'=>['in',$reportId_arr]])->select();
            $item_list=[];
            foreach($items as $k=>$v){
                $item_list[$v['report_id']][]=$v;
            }
            foreach($list['data']['list'] as $k=>$v){
                $list['data']['list'][$k]['item']=$item_list[$v['id']];
                $list['data']['list'][$k]['sale_name']=$adminData[$v['sys_uid']]['fullname'];
                $list['data']['list'][$k]['customer_name']=($customerListData[$v['user_id']]['fcustjc']?:$customerListData[$v['user_id']]['user_name'])?:'路人';
            }
        }
        return $list;
    }

    /**
     * @desc 议价的报备抛砖
     */
    public function reportChangeSale($request){
        $where=[
            'id'=>$request['id']
        ];
        $result=M('report')->field('sys_uid')->where($where)->save($request);
        if($result===false) return ['error'=>1,'msg'=>'false'];
        else return ['error'=>0,'msg'=>'success'];
    }

    /**
     * @desc 议价的excel导入
     */
    public function productBargainExcelInit($data){
        $result=[];
        $fcustno_arr=$pSign_arr=$fitemno_arr=[];
        foreach($data as $k=>$v){
            if($v['A']) $fcustno_arr[]=$v['A'];
            if($v['C']) $pSign_arr[]=$v['C'];
            if($v['B']) $fitemno_arr[]=$v['B'];
        }
        $fcustno_arr=array_unique($fcustno_arr);
        $pSign_arr=array_unique($pSign_arr);
        $fitemno_arr=array_unique($fitemno_arr);
        $list=M('user')->field('id,fcustno')->where(['fcustno'=>['in',$fcustno_arr]])->select();
        $list_pSign=M('product')->field('id,p_sign,fitemno')->where(['p_sign'=>['in',$pSign_arr],'fitemno'=>['in',$fitemno_arr],'_logic'=>'or'])->select();

        $fitemno_num=M('product')->field('count(id) as id_num,fitemno')->group('fitemno')->where(['fitemno'=>['in',$fitemno_arr]])->select();//检查是否有fitemno一对多的p_sign
        $fitemno_duplicate_entry=[];
        foreach($fitemno_num as $k=>$v){
            if($v['id_num']>1){
                $fitemno_duplicate_entry[]=$v['fitemno'];
            }
        }

        $uid_arr=$pid_arr=[];
        foreach($list as $k=>$v){
            $uid_arr[$v['fcustno']]=$v['id'];
        }
        $psign_arr=[];
        foreach($list_pSign as $k=>$v){
            $pid_arr[$v['p_sign']]=$v['id'];
            $psign_arr[$v['fitemno']]=$v['id'];
        }

        foreach($data as $k=>$v){
            $v['E']=$v['E']?$v['E']:0;
            $v['D']=$v['D']?$v['D']:0;
            $one_save=[
                'discount_price'=>$v['D'],
                'is_invoice_change'=>(trim($v['F'])=='是')?1:0,
                'discount_price_tax'=>(trim($v['F'])=='是')?0:$v['E'],
                'discount_price_invoice_change'=>(trim($v['F'])=='是')?$v['E']:0,
                'min_buy'=>$v['H']?(int)$v['H']:0,
                'return_price'=>$v['G']?(float)$v['G']:0,
            ];
            if(isset($uid_arr[$v['A']])){
                $one_save['uid']=$uid_arr[$v['A']];
            }else{
                $result['faild'][]=$v['A'].'客户信息不对';
                continue;
            }

            if($v['C']){
                if($pid_arr[$v['C']]){
                    $one_save['p_id']=$pid_arr[$v['C']];
                }
            }else if($v['B']){
                if($psign_arr[$v['B']]){
                    if(in_array($v['B'],$fitemno_duplicate_entry)){
                        $result['faild'][]=$v['B'].'有一对多的p_sign';
                        continue;
                    }
                    $one_save['p_id']=$psign_arr[$v['B']];
                }
            }
            if(!$one_save['p_id']){
                $result['faild'][]='erp型号:'.$v['B'].'{---}商城型号:'.$v['C'].'产品信息不对';
                continue;
            }

            $one_result=$this->oneProductBargainAction($one_save);
            $one_result['sign']='ERP客户编码:'.$v['A'].'----原装料号:'.$v['B'];
            if($one_result['error']!=0){
                $result['failed'][]=$one_result;
            }else{
                $result['success'][]=$one_result;
            }
        }
        return $result;
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
            $oneResult=$this->customerProductSampleAction($v,$sys_uid);
            if($oneResult['error']!==0){
                $result['error']=1;
                $result['field'][]=$oneResult;
            }else{
                $result['success'][]=$oneResult;
            }
        }
        return $result;
    }


    /**
     *
     *  @desc 单样品操作
     */
    public function customerProductSampleAction($one,$sys_uid='',$is_delete=false){
        if(!$sys_uid)$sys_uid=session('adminId');
        if(!$sys_uid) return ['error'=>1,'msg'=>'登陆信息错误'];
        if(!$is_delete){
            if(!$one['fitemno']) return ['error'=>1,'msg'=>'参数错误2'];
        }
        $one['sys_uid']=$sys_uid;
        $where_customer=[
            'id'=>$one['uid'],
            'user_type'=>['neq',20]
        ];
        $customer=(new \Admin\Model\CustomerModel)->customerList($where_customer);
        $oneCustomer=$customer['data']['list'][0]['nick_name'];
        if($customer['error']!==0) return ['error'=>1,'msg'=>$oneCustomer.'客户信息错误'];
        $where_product=[
            'id'=>$one['pid'],
            'is_online'=>1
        ];
        $product=(new \Admin\Model\ProductModel)->productList($where_product);
        $oneProduct=$product['data']['list'][0]['p_sign'];
        if($product['error']!==0) return ['error'=>1,'msg'=>$oneProduct.'产品信息错误'];

        if(!$is_delete) {
            if (trim($one['fitemno']) != $product['data']['list'][0]['fitemno']) {
                $is_product = M('product_fitemno')->field('p_sign')->where([
                    'p_sign' => $product['data']['list'][0]['p_sign'],
                    'fitemno' => trim($one['fitemno'])
                ])->find();
                if (!$is_product['p_sign']) return ['error' => 1, 'msg' => '产品信息错误2'];
            }
        }

        $m=M('user_product_example');
        $deleteRsult=$m->where(['uid'=>$one['uid'],'pid'=>$one['pid']])->delete();
        if($is_delete){
            if(!$deleteRsult) return ['error'=>1,'msg'=>'删除失败'];
            else return ['error'=>0,'msg'=>'删除成功'];
        }
        $result=$m->field('uid,pid,sys_uid,fitemno')->add($one);
        $msgHead=$oneCustomer.'---'.$oneProduct;
        if(!$result){
            //错误调试
            $errorSql=D()->getDbError();
            if(APP_DEBUG)$msgHead.='--'.$errorSql;

            return ['error'=>1,'msg'=>$msgHead.':: field'];
        }else{
            return ['error'=>0,'msg'=>$msgHead.':: success'];
        }
    }
    /**
     *
     *  @desc 单样品操作
     */
    public function customerUserProductSampleAction($one,$sys_uid='',$is_delete=false){
        if(!$sys_uid)$sys_uid=session('adminId');
        if(!$sys_uid) return ['error'=>1,'msg'=>'登陆信息错误'];
        if(!$is_delete){
            if(!$one['fitemno']) return ['error'=>1,'msg'=>'参数错误2'];
        }
        $one['sys_uid']=$sys_uid;
        $where_customer=[
            'id'=>$one['uid'],
            'user_type'=>['neq',20]
        ];
        $customer=(new \Admin\Model\CustomerModel)->customerList($where_customer);
        $oneCustomer=$customer['data']['list'][0]['nick_name'];
        if($customer['error']!==0) return ['error'=>1,'msg'=>$oneCustomer.'客户信息错误'];
        $where_product=[
            'id'=>$one['pid'],
            'is_online'=>1
        ];
        $product=(new \Admin\Model\ProductModel)->productList($where_product);
        $oneProduct=$product['data']['list'][0]['p_sign'];
        if($product['error']!==0) return ['error'=>1,'msg'=>$oneProduct.'产品信息错误'];

        if(!$is_delete) {
            if (trim($one['fitemno']) != $product['data']['list'][0]['fitemno']) {
                $is_product = M('product_fitemno')->field('p_sign')->where([
                    'p_sign' => $product['data']['list'][0]['p_sign']
                ])->find();
                if (!$is_product['p_sign']) return ['error' => 1, 'msg' => '产品信息错误2'];
            }
        }

        $m=M('user_product_example');
        $deleteRsult=$m->where(['uid'=>$one['uid'],'pid'=>$one['pid']])->delete();
        if($is_delete){
            if(!$deleteRsult) return ['error'=>1,'msg'=>'删除失败'];
            else return ['error'=>0,'msg'=>'删除成功'];
        }
        $result=$m->field('uid,pid,sys_uid,fitemno,origin,max_num')->add($one);
        $msgHead=$oneCustomer.'---'.$oneProduct;
        if(!$result){
            //错误调试
            $errorSql=D()->getDbError();
            if(APP_DEBUG)$msgHead.='--'.$errorSql;

            return ['error'=>1,'msg'=>$msgHead.':: field'];
        }else{
            return ['error'=>0,'msg'=>$msgHead.':: success'];
        }
    }
    /**
     *
     *  @desc 样品列表
     */
    public function customerProductSampleList($where='',$page='',$pageSize='',$order='id desc',$field=''){
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
                $must_where['uid']=['in',$customers['data']];
            }
        }
        if($where){
            if($must_where){
                $where=[$where,$must_where];
            }
        }else if($must_where){
            $where=$must_where;
        }

        $bargain=M('user_product_example');
        $list=$this->baseList($bargain,$where,$page,$pageSize,$order,$field);
        if($list['error']!==0) return $list;

        $data=$list['data']['list'];
        $uid_arr=$pid_arr=$sale_arr=[];
        foreach($data as $k=>$v){
            $uid_arr[]=$v['uid'];
            $pid_arr[]=$v['pid'];
            $sysUid_arr[]=$v['sys_uid'];
        }
        $customer=$this->baseListType(M('user'),$uid_arr,'id','id,sys_uid,nick_name');
        if($customer['error']===0){
            foreach($customer['data']['list'] as $k=>$v){
                $sale_arr[]=$v['sys_uid'];
            }
        }
        $sale_arr=array_unique(array_merge($sale_arr,$sysUid_arr));
        $product=$this->baseListType(M('product'),$pid_arr,'id','id,p_sign,fitemno');
        $sale_arr=$this->baseListType(M('user','sys_'),$sale_arr,'uid','uid,fullname');

        foreach($data as $k=>$v){
            $v['p_sign']=$product['data']['list'][$v['pid']]['p_sign'];
            $v['fitemno']=$v['fitemno']?$v['fitemno']:$product['data']['list'][$v['pid']]['fitemno'];
            $v['customerName']=$customer['data']['list'][$v['uid']]['nick_name'];
            $sale_id=$customer['data']['list'][$v['uid']]['sys_uid'];
            $v['sale_name']=$sale_arr['data']['list'][$sale_id]['fullname'];
            $v['handle_name']=$sale_arr['data']['list'][$v['sys_uid']]['fullname'];
            $list['data']['list'][$k]=$v;
        }

        return $list;
    }








}