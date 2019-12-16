<?php

// +----------------------------------------------------------------------
// | FileName:   OauthModel.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/10 15:51
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Small\Logic;

use Think\Model;

class ProductLogic extends Model
{
    public $limit='0,10';

    public function _initialize(){
        $this->limit='0,'.C('PAGE_PAGESIZE');
    }

    /**
     * @desc 商品列表
     *
     */
    public function productList($where=[],$limit='',$field='',$order='',$relation=false,$user_id=''){
        if(!$field) $field='p_sign,pack_unit,parameter,sell_num,fitemno,id,package,IFNULL(describe_image,"") AS describe_image,describe,brand_id,cate_id,min';
//        if(!$limit) $limit=$this->limit;
//        if( !in_array($relation,['order_detail'])) $where['is_online']=1;

        if($relation=='productDetail'){
            $field='id,p_sign,pack_unit,parameter,sell_num,fitemno,id,package,describe_image,describe,brand_id,cate_id,min,IFNULL(describe_image,"") AS describe_image,attr_id';
        }
        $relation_where='';
        if($relation=='productBargain'){
            if(!$user_id) return ['error'=>-500,'msg'=>'未绑定商城帐号2'];
            $relation_where['user_product_bargain']='is_del=0 and uid = '.$user_id;
        }
        if($relation=='backetGoods'){
            if(!$user_id) return ['error'=>-500,'msg'=>'未绑定商城帐号2'];
            $relation_where['user_product_bargain']='is_del=0 and uid = '.$user_id;
        }

        $m=D('Product');
        $m->where($where)->field($field);
        if($order) $m->order($order);
        if($relation) $m->$relation($relation_where)->relation(true);
        $list=$m->limit($limit)->select();

        if(!$list) return ['error'=>-400,'msg'=>'没有数据'];
        $count=$m->where($where)->count();

        if($relation=='productDetail'){
            $big_path='';
            $big_path=$list[0]['img_path'].'big/';
            if(is_dir($big_path)){
                $big_path_all=scandir($big_path);
                foreach($big_path_all as $k=>$v){
                    if($k<2) continue;
                    $list[0]['product_handle'][]=$big_path.$v;
                }
            }
            if($user_id){
                $memberCenter=D('Small/MemberCenter','Logic');
                //浏览历史
                $memberCenter->myAction('myHistory',['user_id'=>$user_id,'p_id'=>$list[0]['id']]);
                //是否已收藏
                $collect=$memberCenter->my(['id'=>$user_id],'','id','','myCollect');
                if($collect['error']>=0){
                    $pId_arr=[];
                    foreach($collect['data']['list'] as $k=>$v){
                        $pId_arr[]=$v['id'];
                    }
                    in_array($list[0]['id'],$pId_arr)?($list[0]['myCollect']=1):($list[0]['myCollect']=0);
                }
            }
            foreach ($list as &$list_v){
                $whereType[] = 'find_in_set('.$list_v['cate_id'].',cate_id) ';
                $whereType['id']=$list_v['attr_id'];
                $attr_res=M("product_attribute_type")->where($whereType)->find();
                if(!$attr_res){
                    $where2= 'find_in_set('.$list_v['cate_id'].',cate_id) ';
                    $res_attr=M("product_attribute_type")->where($where2)->find();
                    if($res_attr){
                        $list_v['attr_id']=$res_attr['id'];
                        $list_v['attr_list']=$res_attr;
                    }else{
                        $list_v['cate_id']=0;
                        $list_v['attr_list']=[];
                    }
                }else{
                    $list_v['attr_list']=$attr_res;
                }
            }
        }

        return ['error'=>0,'msg'=>'success','data'=>['list'=>$list,'count'=>$count]];
    }

    /**
     * @desc 商品执行价格  basketLogic.class.php->basket 调用
     * @param   [[product_price=>[],user_product_bargain=>[]]],is_invoice 是否开票
     *
     */
    public function productSettlementPrice($basketSettlementInfo,$is_invoice=1){
        if(!$basketSettlementInfo||!is_array($basketSettlementInfo)) return ['error'=>-1,'msg'=>'参数错误'];

        $one_product=[];
        foreach($basketSettlementInfo as $k=>&$v){
            if($v['user_product_bargain']){//有议价
                $one_product=$this->settlementPriceBargain($v,$is_invoice);
                if($one_product['error']>=0){
                    $v=array_merge($v,$one_product['data']);
                }else{
                    //错误日志记录
                }
            }

            if(!isset($v['price_true'])||!$v['price_true']){//没有议价,或议价没有执行
                $one_product=$this->settlementPriceSection($v,$is_invoice);
                if($one_product['error']<0) return $one_product;
                $v=array_merge($v,$one_product['data']);
            }
        }
        unset($v);

        return ['error'=>0,'data'=>$basketSettlementInfo];
    }

    /**
     * @desc 一个商品执行价格 议价结算
     *
     */
    public function settlementPriceBargain($oneProduct,$is_invoice=1){
        if(!$oneProduct['user_product_bargain']) return ['error'=>-1,'msg'=>'价格错误'];
        if(!$oneProduct['num']) return ['error' => 1, 'msg' => '商品数量num没有设置'];

        //议价的是否是默认的erp型号
        $fitmenoIsDefault=$this->fitmenoIsDefault($oneProduct);
        if($fitmenoIsDefault['error']<0) return $fitmenoIsDefault;
        $oneProduct=$fitmenoIsDefault['data'];

        $bargain=$oneProduct['user_product_bargain'];
        //价格执行的数据等级
        $data_level=2;
        $admin=D('Small/Admin','Logic')->adminList(['uid'=>$bargain['sys_uid']]);
        if($admin['error']<0) return $admin;

        $data_level=($admin['data'][0]['department_level']!=2)?3:2;//临时调整业务的等级

        //议价结算
        $settlementPrice=$this->settlementPrice($oneProduct,$data_level,$is_invoice);
        if($settlementPrice['error']<0) return $settlementPrice;

        return $settlementPrice;
    }

    /**
     * @desc 议价结算
     *
     */
    public function settlementPrice($oneProduct,$data_level,$is_invoice=1){
        //议价格是否可执行基础判断
        $bargainIsPass=$this->bargainPirceIsPass($oneProduct,$data_level);
        if($bargainIsPass['error']<0) return $bargainIsPass;
        $bargain=$oneProduct['user_product_bargain'];
        $bargainPass=$bargainIsPass['data'];
        if($bargainPass['price_invoice_change_pass']==1) $bargain['discount_price_tax']=$bargain['discount_price_invoice_change'];

        $order_status=0;//开票，未进项，没有议价或换型号开票没启用---订单需要审核
        $subtotal=0;//小计
        $is_discount_num=1;//折扣限额比例
        $price_show=$price_true=0;
        $discount_subtotal=0;//优惠小计

        if( $is_invoice && !$oneProduct['is_tax'] && !$bargainPass['price_invoice_change_pass']) $order_status=1;//开票，未进项，没有议价或换型号开票没启用---订单需要审核

        if($oneProduct['num']>=$bargain['min_buy']){//大于最小购买量

            if($bargainPass['price_pass']&&$bargainPass['price_tax_pass']){//含税和不含税都有议价
                $price_show=$bargain['discount_price_tax'];//显示价格
                if($is_invoice==1){//开票
                    $price_true=$bargain['discount_price_tax'];//计算单价
                }else{//不开票
                    $price_true=$bargain['discount_price'];//计算单价

                    $discount_subtotal=($price_show-$price_true)*$oneProduct['num'];//优惠小计
                }
            }else if(!$bargainPass['price_pass']&&$bargainPass['price_tax_pass']){//只含税有议价

                $price_show=$bargain['discount_price_tax'];//显示价格
                $price_true=$price_show;//计算单价

                //折扣限
                if($is_invoice!=1 && $bargain['discount_price_tax']*$oneProduct['num']>=$oneProduct['discount_num']){//不开票
                    if($bargainPass['price_invoice_change_pass']==1) $oneProduct['tax']=1.1;//换型号的计算优惠时税率固定1.1
                    $discount_subtotal=$bargain['discount_price_tax']*$oneProduct['num']*(1-1/$oneProduct['tax']);

                    $price_true=$price_show/$oneProduct['tax'];//计算单价

                    $is_discount_num=0.9;
                }
            }else if($bargainPass['price_pass']&&!$bargainPass['price_tax_pass']){//只不含税有议价

                $price_show=$bargain['discount_price']*$oneProduct['tax'];//显示价格

                if($is_invoice==1){//开票

                    $price_true=$price_show;//计算单价

                }else if($is_invoice!=1){//不开票

                    $price_true=$bargain['discount_price'];//计算单价

                    $discount_subtotal=($price_show-$price_true)*$oneProduct['num'];//优惠小计
                }
            }
        }

        $subtotal=$price_show*$oneProduct['num'];//显示小计

        $data_return=[
            'subtotal'=>$subtotal,//显示小计
            'discount_subtotal'=>$discount_subtotal,//优惠小计
            'price_show'=>$price_show,
            'price_true'=>$price_true,
            'order_status'=>$order_status,
            'is_discount_num'=>$is_discount_num
        ];

        return ['error'=>0,'data'=>$data_return];
    }

    /**
     * @desc 议价的是否是默认的erp型号
     *
     */
    public function fitmenoIsDefault($oneProduct){
        $bargainFitemno=$oneProduct['user_product_bargain']['fitemno']?:'';
        if(!$bargainFitemno||$bargainFitemno==$oneProduct['fitemno']){
            $oneProduct['user_product_bargain']['fitemno']=$oneProduct['fitemno'];
            return ['error'=>0,'data'=>$oneProduct];
        }

        $one=M('product','erp_')->field('fstcb,store,ftem as fitemno')->where(['ftem'=>$bargainFitemno])->find();
        if(!$one) return ['error'=>-1,'msg'=>'型号错误'];

        $oneProduct['fstcb']=$one['fstcb'];
        $oneProduct['fitemno']=$one['fitemno'];
        $oneProduct['store']=$one['store'];

        return ['error'=>0,'data'=>$oneProduct];
    }


    /**
     * @desc 议价格是否可执行,根据数据等级
     *
     */
    public function bargainPirceIsPass($oneProduct,$data_level=2){
        $bargain=$oneProduct['user_product_bargain'];

        $price_pass=$price_tax_pass=$price_invoice_change_pass=0;//0优惠不通过,1通过
        $priceTrue=$bargain['discount_price']-$bargain['return_price'];//未税
        $priceTrue_tax=$bargain['discount_price_tax']-$bargain['return_price'];//含税
        $priceTrue_invoice_change=$bargain['discount_price_invoice_change']-$bargain['return_price'];//换型号含税

        //组长
        if($priceTrue>0&&(string)$priceTrue>=(string)$oneProduct['fstcb']){
            $price_pass=1;
        }
        if($priceTrue_tax>0&&(string)($priceTrue_tax/1.1)>=(string)$oneProduct['fstcb']){
            $price_tax_pass=1;
        }
        if($priceTrue_invoice_change>0&&(string)($priceTrue_invoice_change/1.1)>=(string)$oneProduct['fstcb']){
            $price_invoice_change_pass=1;
        }

        //总监
        if($data_level==3&&$bargain['init_cost']>=$oneProduct['fstcb']){
            if($priceTrue_tax>0) $price_tax_pass=1;
            if($priceTrue>0) $price_pass=1;
            if($bargain['is_invoice_change']&&$priceTrue_invoice_change>0) $price_invoice_change_pass=1;
        }

        if($price_invoice_change_pass){//换型号开票
            $price_tax_pass=$price_invoice_change_pass;
            $priceTrue_tax=$priceTrue_invoice_change;
        }

        $return_data=[
            'price_pass'=>$price_pass,
            'price_tax_pass'=>$price_tax_pass,
            'price_invoice_change_pass'=>$price_invoice_change_pass,

            'priceTrue'=>$priceTrue,
            'priceTrue_tax'=>$priceTrue_tax,
        ];

        if($return_data['price_pass']==0&&$return_data['price_tax_pass']==0) return ['error'=>-1,'msg'=>'没有可执行的议价'];

        return ['error'=>0,'data'=>$return_data];
    }

    /**
     * @desc 一个商品执行价格 价格区间结算
     *
     */
    public function settlementPriceSection($oneProduct,$is_invoice=1){
        if(!$oneProduct['product_price']) return ['error'=>-1,'msg'=>'价格错误2'];
        if (!$oneProduct['num']) return ['error' => 1, 'msg' => '商品数量num没有设置2'];

        $price_true=$one_price_total=0;//选中的区间价
        foreach ($oneProduct['product_price'] as $k => $v) {
            if ($oneProduct['num'] < $v['right_num']) {
                $price_true=$v['unit_price'];
                break;//根据区间选中的价格
            }
        }

        if (!$price_true){
            $last_price=array_shift($oneProduct['product_price']);
            $price_true=$last_price['unit_price'];
        }

        if (!$price_true){
             return ['error'=>-1,'msg'=>'价格错误2'];
        }

        $price_show=$price_true;
        $discount_subtotal=0;//优惠小计
        $is_discount_num=1;//是否用了折扣

        if (!$is_invoice) {//不开票达到折扣限
            if ($one_price_total >= $oneProduct['discount_num']) {

                $price_true=$price_show/$oneProduct['tax'];
                $is_discount_num = 0.9;
            }
        }
        $subtotal=$oneProduct['num'] * $price_show;

        $return_data=[
            'subtotal'=>$subtotal,//显示付价
            'price_true'=>$price_true,//实付价
            'price_show'=>$price_show,//显示价
            'discount_subtotal'=>$discount_subtotal,//优惠小计
            'is_discount_num'=>$is_discount_num,//是否用了折扣
        ];

        return ['error'=>1,'data'=>$return_data];
    }

    /**
     * @desc 用户分享保存
     *
     */
    public function shareSave($request){
        $where=[
            'share_user_id'=>$request['share_user_id'],
            'share_detail_index'=>$request['share_detail_index'],
            'share_type'=>$request['share_type'],
        ];
        $shareM=M('share','xcx_');
        $one=$shareM->field('id,scan_time,scan_user_id')->where($where)->find();
        $result='';
        $request['scan_time']=date('Y-m-d H:i:s',time());
        if(!$one){
            $result=$shareM->field('scan_user_id,share_user_id,share_detail_index,share_type,scan_time')->add($request);
        }else{
            $save_date=[
                'scan_user_id'=>$one['scan_user_id'].','.$request['scan_user_id'],
                'scan_time'=>$one['scan_time'].','.$request['scan_time']
            ];
            $result=$shareM->field('scan_user_id,scan_time')->where($where)->save($save_date);
        }
        if(!$result) return ['error'=>-1,'msg'=>'field'];

        return ['error'=>0,'msg'=>'success'];
    }


}