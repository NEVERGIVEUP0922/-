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


class WalletModel extends EntryModel
{
    protected function _initialize(){
        parent::_initialize();
    }

    protected $trueTableName='wa_wallet';
    protected $fields='id,user_id,type,amount_entry,amount_has_pay,amount_forzen,amount_allow_pay,update_at,create_at';
    protected $rule_fields='id,interest,type,start_time,end_time,status,min';


    /**
     * @desc 客户钱包列表
     *
     */
    public function walletList($where='',$page='',$pageSize='',$order=''){
        $list=$this->baseList(M('wallet','wa_'),$where,$page,$pageSize,$order,$this->fields);
        return $list;
    }

    /**
     * @desc 钱包理财规则列表
     *
     */
    public function walletRuleList($where='',$page='',$pageSize='',$order=''){
        $list=$this->baseList(M('wallet_rule','wa_'),$where,$page,$pageSize,$order,$this->rule_fields);
        return $list;
    }

    /**
     * @desc 生成新walletId
     *
     */
    protected function newWalletId($userId,$type){
        return md5(uniqid().$userId.$type);
    }

    /**
     * @desc 生成钱包初始密码
     *
     */
    protected function newPassworkInit(){
        $origin=substr(mt_rand(1000000,9999999),-6);
        $passwork=hash_string($origin);
        return [$origin,$passwork];
    }

    /**
     * @desc 对私的银行转帐申请确认
     *
     */
    public function addWalletAccountImg($data){
        $session=session();
        $data['user_id']=$session['userId'];
        $action=$data['action'];
        if(!in_array($action,['add','check'])) return ['error'=>1,'msg'=>'参数错误5'];
        $m=M('wallet_account_img','wa_');
        if($action=='add'){
            if(!isset($data['img'])) return ['error'=>1,'msg'=>'参数错误'];
            $result=$m->field('user_id,img')->add($data);
            if($result!==false){
                return ['error'=>0,'msg'=>'success'];
            }else{
                return ['error'=>1,'msg'=>'failed'];
            }
        }else if($action=='check'){
            if(!isset($data['id'])) return ['error'=>1,'msg'=>'参数错误2'];
            if(!in_array($data['status'],[8,9])) return ['error'=>1,'msg'=>'参数错误3'];
            if(!in_array($data['wallet_type'],[1,20])) return ['error'=>1,'msg'=>'参数错误4'];
            if((int)$data['amount']==0||!is_int($data['amount'])) return ['error'=>1,'msg'=>'参数错误5'];
            $img=$m->field('user_id')->where(['id'=>$data['id']])->find();
            if(!$img) return ['error'=>1,'msg'=>'参数错误6'];
            $data['user_id']=$img['user_id'];
            $user_id=M('user')->field('user_mobile')->where(['id'=>$data['user_id']])->find();
            if(!$user_id) return ['error'=>1,'msg'=>'用户信息错误'];

            $data['check_time']=date('Y-m-d H:i:s',time());
            $data['sys_uid_name']=$session['adminInfo']['user_name'];
            $data['sys_uid']=$session['adminInfo']['uid'];
            $this->startTrans();
            $result=$m->where(['id'=>$data['id'],'status'=>5])->field('sys_uid,sys_uid_name,status,check_time')->save($data);
            if(!$result){
                $this->rollback();
                return ['error'=>1,'msg'=>'failed2'];
            }else if($data['status']==9){
                $this->commit();
                return ['error'=>0,'msg'=>'success2'];
            }else if($data['status']==8){
                $data['wallet_account_type']=15;
                $walletResult=$this->walletAccountAdd($data,$user_id);
                if($walletResult['error']==0){
                    $m->commit();
                }else{
                    $m->rollback();
                }
                return $walletResult;
            }
        }
    }

    /**
     * @desc 钱包添加流水记录
     *
     */
    public function walletAccountAdd($data,$userInfo=''){
        $result_financial=$this->currentInterestToAccount($data['user_id']);
        if($result_financial['error']!=0){
            return ['error'=>1,'msg'=>'活期结算失败'];
        }

        $model=M('wallet','wa_');
        $walletInfo=$model->where(['user_id'=>$data['user_id'],'type'=>$data['wallet_type']])->find();
        if(!$walletInfo) return ['error'=>1,'msg'=>'没有开通钱包功能'];
        if($walletInfo['status']==200) return ['error'=>1,'msg'=>'钱包被禁用'];
        $walletId=$walletInfo['id']?$walletInfo['id']:$this->newWalletId($data['user_id'],$data['type']);
        $walletData=[
            'user_id'=>$data['user_id'],
            'type'=>$data['wallet_type'],
            'status'=>10,
            'amount_entry'=>(int)$walletInfo['amount_entry']+(int)$data['amount'],
            'amount_allow_pay'=>(int)$walletInfo['amount_allow_pay']+(int)$data['amount'],
            'id'=>$walletId,
        ];
        $wallet_account=[
            'type'=>$data['wallet_account_type'],
            'amount'=>$data['amount'],
            'amount_start'=>(int)$walletInfo['amount_allow_pay'],
            'amount_end'=>(int)$walletInfo['amount_allow_pay']+(int)$data['amount'],
            'note'=>isset($data['note'])?$data['note']:'',
            'project'=>$data['id'],
            'wallet_id'=>$walletId,
            'detail'=>$data['detail']?json_encode($data['detail']):'',
        ];
        $model->startTrans();
        if($walletInfo){
            $result2=$this->where(['user_id'=>$data['user_id'],'type'=>$data['type']])->save($walletData);
        }
        $wallet_account['wallet_id']=$walletId;
        $result3=M('wallet_account','wa_')->add($wallet_account);

        if(!$result2||!$result3){
            $this->rollback();
            return ['error'=>1,'msg'=>'failed'];
        }else{
            $this->commit();
            return ['error'=>0,'msg'=>'success'];
        }
    }

    /**
     * @desc 客户申请理财
     *
    $request=[
    'amount'=>1000,
    'wallet_type'=>20,
    'wallet_rule_type'=>33,
    ];
     *
     */
    public function customerRequestFinancial($request){
        $currentTime=date('Y-m-d H:i:s');
        $session=session();

        $result_financial=$this->currentInterestToAccount($session['userId']);
        if($result_financial['error']!=0){
            return ['error'=>1,'msg'=>'活期结算失败'];
        }

        $request['amount']=(isset($request['amount'])&&$request['amount'])?(int)($request['amount']*100):0;
        if(!$request['amount']) return ['error'=>1,'msg'=>'金额不对'];

        $request['start_time']=$currentTime;
        $request['end_time']=$this->regularEndTime($request['wallet_rule_type']);
        if(!$request['end_time']) return ['error'=>1,'msg'=>'结算时间不对'];
        $where=[
            'user_id'=>$session['userId'],
            'status'=>10,
            'type'=>$request['wallet_type'],
        ];
        $m=M('wallet','wa_');
        $one=$m->field('id,amount_allow_pay,amount_forzen')->where($where)->find();
        if(!$one) return ['error'=>1,'msg'=>'没有可理财钱包'];
        if($request['amount']>$one['amount_allow_pay']) return ['error'=>1,'msg'=>'理财金额不对'];

        $walletRuleWhere=[
            'status'=>1,
            'type'=>$request['wallet_rule_type'],
            'start_time'=>['elt',$currentTime],
            'end_time'=>['egt',$currentTime],
            'min'=>['elt',$request['amount']],
        ];
        $walletRule=M('wallet_rule','wa_')->field('interest,min,type,start_time,end_time')->where($walletRuleWhere)->find();
        if(!$walletRule) return ['error'=>1,'msg'=>'没有制定利率'];
        $interest_amount=$this->regularInterest($request['wallet_rule_type'],$request['amount'],$walletRule['interest']/10000);
        if(!$interest_amount)  return ['error'=>1,'msg'=>'利息计算错误'];

        $financialData=[
            'user_id'=>$session['userId'],
            'wallet_type'=>$request['wallet_type'],
            'amount'=>$request['amount'],
            'start_time'=>$request['start_time'],
            'end_time'=>$request['end_time'],
            'financial_type'=>$request['wallet_rule_type'],
            'financial_interest'=>$walletRule['interest'],
            'status'=>10,
            'rule_data'=>json_encode($walletRule),
            'interest_amount'=>$interest_amount,
        ];
        $walletData=[
            'amount_allow_pay'=>$one['amount_allow_pay']-$request['amount'],
            'amount_forzen'=>$one['amount_forzen']+$request['amount'],
        ];
        $whereWallet=[
            'user_id'=>$session['userId'],
            'type'=>$request['wallet_type'],
        ];

        $m->startTrans();
        $result=M('financial_request','wa_')->add($financialData);
        if(!$result){
            $m->rollback();
            return ['error'=>1,'msg'=>'提交失败1'];
        }
        $result2=$m->where($whereWallet)->save($walletData);
        $walletAccount=[
            'wallet_id'=>$one['id'],
            'type'=>$request['wallet_rule_type'],
            'amount_start'=>$one['amount_allow_pay'],
            'amount_end'=>$one['amount_allow_pay']-$request['amount'],
            'amount'=>$request['amount'],
            'project'=>$result,
            'detail'=>json_encode($walletRule)
        ];
        $result3=M('wallet_account','wa_')->add($walletAccount);
        if(!$result2||!$result3){
            $m->rollback();
            return ['error'=>1,'msg'=>'提交失败'];
        }else{
            $m->commit();
            return ['error'=>0,'msg'=>'提交成功'];
        }
    }

    /**
     * @desc 结算所有客户理财
     *
     */
    public function allCustomerfinancialToAccount(){
        $currentTime=date('Y-m-d H:i:s');
        $where=[
            'financial_type'=>['in',[33,36,42]],
            'status'=>10,
            'end_time'=>['lt',$currentTime]
        ];
        $financial_request=M('financial_request','wa_')->field('id,financial_type,start_time,end_time,user_id,wallet_type,amount,interest_amount')->where($where)->select();
        if(!$financial_request) return ['error'=>1,'msg'=>'没有需要到帐的理财信息'];

        $return=[];
        $error_code=0;
        $financialRequestM=M('financial_request','wa_');
        foreach($financial_request as $k=>$v){
            $financialRequestM->startTrans();
            $oneResult=$this->customerFinancialToAccount($v);
            $result2=$financialRequestM->where(['id'=>$v['id']])->save(['status'=>20]);
            if($oneResult['error']!=0||!$result2){
                $return['failed'][]=$oneResult;
                $error_code=1;
                $financialRequestM->rollback();
            }else{
                $financialRequestM->commit();
                $return['success'][]=$oneResult;
            }
        }
        $return['error']=$error_code;
        return $return;
    }

    /**
     * @desc 客户理财到帐
     *
     */
    public function customerFinancialToAccount($financial){
        $m=M('wallet', 'wa_');
        $where=[
            'user_id'=>$financial['user_id'],
            'type'=>$financial['wallet_type'],
            'status'=>10
        ];
        $wallet=$m->where($where)->find();
        if(!$wallet) return ['error'=>1,'msg'=>$financial['user_id'].':'.$financial['wallet_type'].':客户钱包不符合到帐条件'];
        $data=[
            'user_id'=>$financial['user_id'],
            'wallet_type'=>$financial['wallet_type'],
            'amount'=>$financial['amount']+$financial['interest_amount'],
            'id'=>$financial['id'],
            'wallet_account_type'=>$financial['financial_type'],
            'detail'=>$financial,
        ];
        $result=$this->walletAccountAdd($data);
        return $result;
    }

    /**
     * @desc 客户活期利息结算到帐
     *
     */
    public function currentInterestToAccount($userId){
        $m=M('wallet','wa_');
        $m->startTrans();
        $result=$this->currentInterestEnd($userId,1);
        $result2=$this->currentInterestEnd($userId,20);
        if($result['error']==0&&$result2['error']==0){
            $m->commit();
            return ['error'=>0,'msg'=>'success'];
        }else{
            $m->rollback();
            return ['error'=>1,'msg'=>'failed'];
        }
    }

    /**
     * @desc 客户活期利息
    $request=[
        'wallet_type'=>20,
    ];
     *
     */
    public function currentInterestEnd($userId,$wallet_type){
        if(!in_array($wallet_type,[1,20])) return ['error'=>0,'msg'=>'参数不对'];
        $where=[
            'user_id'=>$userId,
            'status'=>10,
            'type'=>$wallet_type,
            'amount_allow_pay'=>['gt',0]
        ];
        $currentTime=date('Y-m-d H:i:s');
        $wallet=M('wallet','wa_')->field('id,update_at,amount_allow_pay')->where($where)->find();
        if(!$wallet) return ['error'=>0,'msg'=>'没有可用金额'];
        $walletRuleWhere=[
            'type'=>30,
            'status'=>1,
            'start_time'=>['elt',$currentTime],
            'end_time'=>['egt',$currentTime],
            'min'=>['elt',$wallet['amount_allow_pay']]
        ];
        $walletRule=M('wallet_rule','wa_')->field('start_time,end_time,min,interest')->where($walletRuleWhere)->find();
        if(!$walletRule) return ['error'=>0,'msg'=>'没有利率设置'];
        if(substr($currentTime,0,10)==substr($wallet['update_at'],0,10)){
            return ['error'=>0,'msg'=>'结算天数为0'];
        }
        $walletRule['interest']=$walletRule['interest']/10000;

        $n=ceil((strtotime(date('Y-m-d 00:00:00'))-strtotime($wallet['update_at']))/86400);
        $interestAmount=(int)($walletRule['interest']*(int)($wallet['amount_allow_pay']*$n/360));
        $walletData=[
            'amount_allow_pay'=>$wallet['amount_allow_pay']+$interestAmount,
        ];
        $wallet_account=[
            'type'=>30,
            'amount'=>$interestAmount,
            'amount_start'=>$wallet['amount_allow_pay'],
            'amount_end'=>$wallet['amount_allow_pay']+$interestAmount,
            'note'=>'',
            'project'=>$n,
            'wallet_id'=>$wallet['id'],
            'detail'=>json_encode($walletRule),
        ];
        $m=M('wallet','wa_');
        $m->startTrans();
        $result=$m->where(['user_id'=>$userId,'type'=>$wallet_type])->save($walletData);
        $result2=M('wallet_account','wa_')->add($wallet_account);
        if(!$result||!$result2){
            $m->rollback();
            return ['error'=>1,'msg'=>'failed'];
        }else{
            $m->commit();
            return ['error'=>0,'msg'=>'success'];
        }
    }

    /**
     * @desc 个人钱包
     *
     */
    public function customerWallet($user_id){
        $m=M('wallet','wa_');
        $wallet=$m->field('type,amount_allow_pay,status')->where(['user_id'=>$user_id,'status'=>10])->select();
        $wallet_arr=[];
        foreach($wallet as $k=>$v){
            $wallet_arr[$v['type']]['wallet']=$v['amount_allow_pay']/100;
        }
        return ['error'=>0,'data'=>$wallet_arr];
    }

    /**
     * @desc 个人钱包开通
     *
     */
    public function customerRegisterWallet($user_id){
        if(!$user_id) return ['error'=>1,'msg'=>'参数错误'];
        $walletId=$this->newWalletId($user_id,1);//对私
        $walletId_tax=$this->newWalletId($user_id,20);//对公

        $passwordInit=$this->newPassworkInit();

        $data=[
            [
                'id'=>$walletId,
                'user_id'=>$user_id,
                'type'=>1,
                'status'=>10,
                'amount_entry'=>0,
                'amount_allow_pay'=>0,
                'amount_has_pay'=>0,
                'amount_forzen'=>0,
                'pay_password'=>$passwordInit[1],
            ],
            [
                'id'=>$walletId_tax,
                'user_id'=>$user_id,
                'type'=>20,
                'status'=>10,
                'amount_entry'=>0,
                'amount_allow_pay'=>0,
                'amount_has_pay'=>0,
                'amount_forzen'=>0,
                'pay_password'=>$passwordInit[1],
            ]
        ];

        $result=M('wallet','wa_')->addAll($data);

        if(!$result){
            return ['error'=>1,'msg'=>'开通失败'];
        }else{
            return ['error'=>0,'msg'=>'开通成功','data'=>$passwordInit[0]];
        }
    }







}