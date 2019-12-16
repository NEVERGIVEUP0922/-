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

namespace  Wallet\Model;


class IntegralRuleModel extends EntryModel
{
    protected function _initialize(){
        parent::_initialize();
    }

    protected $trueTableName='wa_integral_rule';
    protected $fields='id,type,cell_code,min_amount,max,scale,scale_step,start_time,end_time,note,status,num,sys_uid';

    protected $_link=[ ];

    /**
     * @desc 添加积分规则
     *
     */
    public function integralRuleAction($data){
        $data['sys_uid']=session('adminId');
        switch ($data['type']){
            case 101://下单商品
                $result=$this->integralRuleProduct($data);
                break;
            case 1://下单单笔
                $result=$this->integralRuleOrder($data);
                break;
            case 21://下单月度
                $result=$this->integralRuleOrder($data);
                break;
            case 41://下单年度
                $result=$this->integralRuleOrder($data);
                break;
            case 111://固定积分111（注册，方案，论坛)
                $result=$this->integralRuleSpecial($data);
                break;
        }
        return $result;
    }

    /**
     * @desc 商品积分规则添加
     *
     */
    public function integralRuleProduct($data){
        $oneProduct=M('product')->where(['id'=>$data['cell_code']])->find();
        if(!$oneProduct) return ['error'=>1,'msg'=>'商品信息错误'];

        $has=M('integral_rule','wa_')->where(['type'=>$data['type'],'cell_code'=>$data['cell_code']])->find();

        $field='type,cell_code,scale,start_time,end_time,note,sys_uid';
        if($has){
            $where=['type'=>$data['type'],'cell_code'=>$data['cell_code']];
            $result=M('integral_rule','wa_')->where($where)->field($field)->save($data);
        }else{
            $result=M('integral_rule','wa_')->field($field)->add($data);
        }
        if($result!==false){
            return ['error'=>0,'msg'=>'success'];
        }else{
            return ['error'=>1,'msg'=>'failed'];
        }
    }

    /**
     * @desc 定单积分规则添加
     *
     */
    public function integralRuleOrder($data){
        $m=M('integral_rule','wa_');
        $field='min_amount,type,max,scale,scale_step,start_time,end_time,sys_uid,note';
        $has=$m->where(['type'=>$data['type']])->find();
        if($has){
            $result=$m->field($field)->where(['type'=>$data['type']])->save($data);
        }else{
            $result=$m->field($field)->add($data);
        }
        if($result!==false){
            return ['error'=>0,'msg'=>'success'];
        }else{
            return ['error'=>1,'msg'=>'failed'];
        }
    }

    /**
     * @desc 特殊积分规则添加
     *
     */
    public function integralRuleSpecial($data){
        $m=M('integral_rule','wa_');
        $field='num,cell_code,type,start_time,end_time,sys_uid,note';

        list($model,$controller,$method)=explode('/',$data['cell_code']);
        $objectName="$model\\Controller\\$controller".'Controller';
        $object=new $objectName();
        if(!method_exists($object,$method)) return ['error'=>1,'msg'=>'项目不存在'];

        $has=$m->field('id')->where(['type'=>$data['type'],'cell_code'=>$data['cell_code']])->find();
        if($has){
            $result=$m->field($field)->where(['id'=>$has['id']])->save($data);
        }else{
            $result=$m->field($field)->add($data);
        }
        if($result!==false){
            return ['error'=>0,'msg'=>'success'];
        }else{
            return ['error'=>1,'msg'=>'failed'];
        }
    }

    /**
     * @desc 积分规则列表
     *
     */
    public function integralRuleList($where='',$page='',$pageSize='',$order=''){
        $list=$this->baseList(M('integral_rule','wa_'),$where,$page,$pageSize,$order,$this->fields);
        return $list;
    }









}