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
namespace Small\Model;


class UserModel extends LinkModel
{

    protected $_link = array(
        'user_order_address'  =>[
            'mapping_type'  => self::HAS_MANY,
            'foreign_key'=>'user_id',
            'mapping_key'  => 'id',
            'mapping_fields'=>'consignee,area_code,zipcode,mobile,status',
        ],
        'user_history'  =>[
            'mapping_type'  => self::HAS_ONE,
            'foreign_key'=>'user_id',
            'mapping_key'  => 'id',
            'mapping_fields'=>'his',
        ],
        'user_collect'  =>[
            'mapping_type'  => self::HAS_ONE,
            'foreign_key'=>'user_id',
            'mapping_key'  => 'id',
            'mapping_fields'=>'collect',
        ],
    );

    /*
     * @desc 收贷地址
     *
     */
    public function orderAddress($where=''){
        $this->_link=[
            'user_order_address'  =>[
                'mapping_type'  => self::HAS_MANY,
                'foreign_key'=>'user_id',
                'mapping_key'  => 'id',
                'mapping_fields'=>'id,consignee,area_code,address,zipcode,mobile,status',
                'condition'=>$where
            ]
        ];
        return $this;
    }

    /*
     * @desc 我的收藏
     *
     */
    public function myCollect(){
        $this->_link=[
            'user_collect'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'user_id',
                'mapping_key'  => 'id',
                'as_fields'=>'collect:myCollect',
            ]
        ];
        return $this;
    }

    /*
     * @desc 我的浏览历史
     *
     */
    public function myHistory(){
        $this->_link=[
            'user_history'  =>[
                'mapping_type'  => self::HAS_ONE,
                'foreign_key'=>'user_id',
                'mapping_key'  => 'id',
                'as_fields'=>'his:myHistory',
            ]
        ];
        return $this;
    }

    /*
     * @desc 注册商城用户----个人
     *
     */
    public function registerPC($request){
        $mAction_field='user_name,user_mobile,user_pass,user_type,password,user_mobile';
        $action_data=[];
        foreach($request as $k=>$v){
            if(strpos($mAction_field,$k)!==false){
                $action_data[$k]=$v?:'';
            }
        }

        $action_data['user_pass']=hash_string($request['password']);
        $request['user_name']=$request['user_mobile'];

        $m=M('user');
        $normal = M('user_normal');
        $m->startTrans();

        $result=$m->field('user_name,user_mobile,user_pass,user_type,user_mobile')->add($action_data);
        $pinyin=[
            'customerId_arr'=>[$result]
        ];
        $pinyin_result=D('Admin/Customer')->updateCompanyNameFirstString($pinyin);//更新拼音

        $dataNormal = [ 'user_id' => $result ];
        $normalId = $normal->field('user_id')->add($dataNormal);

        if(!$result||!$normalId){
            $m->rollback();
            return ['error'=>-1,'msg'=>'商城用户注册失败'];
        }else{
            $m->commit();
            return ['error'=>0,'msg'=>'商城用户注册成功','data'=>['user_id'=>$result,'user_name'=>$request['user_name']]];
        }
    }

    /*
     * @desc 注册商城用户----企业
     *
     */
    public function registerPCCompany($request){
        $user_field='user_name,user_mobile,user_pass,user_type,password';
        $user_company_field='user_id,company_name,company_people_num,company_area,company_city,company_address,company_phone_num,company_business_nature,company_business_product,company_business_brand,company_annual_turnover,company_sales_channel,company_user_name,company_user_sector,company_user_position,company_user_phone,company_user_qq,company_user_wechat,company_logo,change_num,company_user_email';
        $action_data=[];
        foreach($request as $k=>$v){
            if(strpos($user_field,$k)!==false&&strpos($user_company_field,$k)!==false) $action_data[$k]=$v?:'';
        }

        $action_data['user_pass']=hash_string($request['password']);
        $request['user_name']=$request['user_mobile'];

        $m=M('user');
        $normal = M('user_normal');
        $m->startTrans();

        $result=$m->field($user_field)->add($action_data);
        $pinyin=[
            'customerId_arr'=>[$result]
        ];
        $pinyin_result=D('Admin/Customer')->updateCompanyNameFirstString($pinyin);//更新拼音

        $dataNormal = [ 'user_id' => $result ];
        $normalId = $normal->field('user_id')->add($dataNormal);
        $company = M('user_company')->field($user_company_field)->add($action_data);

        if(!$result||!$normalId||!$company){
            $m->rollback();
            return ['error'=>-1,'msg'=>'商城用户注册失败'];
        }else{
            $m->commit();
            return ['error'=>0,'msg'=>'商城用户注册成功','data'=>['user_id'=>$result,'user_name'=>$request['user_name']]];
        }
    }
    /**
     * @desc 账期是否执行
     *
     */
    public function userAccountIsPass($userId){
        $userAccount=M('accounts','erp_')->where(['user_id'=>$userId])->find();
        if(!$userAccount||!$userAccount['since_name']||trim($userAccount['since_name'])=='现金'){
            return ['error'=>1,'msg'=>'没有可用账期','one'=>$userAccount];
        }
        if((int)($userAccount['used_debt']*100)>0) return ['error'=>2,'msg'=>'账期有预期未还','data'=>$userAccount['used_debt'],'one'=>$userAccount];
        if($userAccount['used_quota']<0){
            return ['error'=>0,'data'=>$userAccount['quota']-$userAccount['used_quota'],'data1'=>$userAccount['quota'],'used_quota'=>$userAccount['used_quota'],'one'=>$userAccount];
        }else{
            return ['error'=>0,'data'=>$userAccount['quota']-$userAccount['used_quota'],'data1'=>$userAccount['quota']-$userAccount['used_quota'],'used_quota'=>$userAccount['used_quota'],'one'=>$userAccount];
        }

    }

}