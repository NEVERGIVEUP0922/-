<?php

// +----------------------------------------------------------------------
// | FileName:   ProductController.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/28 11:00
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace  Admin\Controller;

use \Common\Controller\SmsController;

class WalletController extends AdminController
{


    /**
     * @desc 钱包列表
     *
     */
    public function walletList(){
        $request=I('get.');
        $page=$request['page']?$request['page']:1;
        $pageSize=$request['pageSize']?$request['pageSize']:C('PAGE_PAGESIZE');
        $where='';

        $wallet=D('Wallet/Wallet');
        $list=$wallet->walletList($where,$page,$pageSize);

        $list['page'] = $page;
        $list['pageSize'] = $pageSize;
        $this->assign('request',$request);
        $this->assign('list',$list);
//        $this->display();
    }

    /**
     * @desc 钱包帐目列表
     *
     */
    public function walletAccountList(){
        $request=I('get.');
        $page=$request['page']?$request['page']:1;
        $pageSize=$request['pageSize']?$request['pageSize']:C('PAGE_PAGESIZE');
        $where='';

        $wallet=D('Wallet/Walletaccount');
        $list=$wallet->walletAccountList($where,$page,$pageSize);

        $list['page'] = $page;
        $list['pageSize'] = $pageSize;
        $this->assign('request',$request);
        $this->assign('list',$list);
//        $this->display();
    }

    /**
     * @desc 钱包理财规则列表
     *
     */
    public function walletRuleList(){
        $request=I('get.');
        $page=$request['page']?$request['page']:1;
        $pageSize=$request['pageSize']?$request['pageSize']:C('PAGE_PAGESIZE');
        $where='';

        $wallet=D('Wallet/Wallet');
        $list=$wallet->walletRuleList($where,$page,$pageSize);

        $list['page'] = $page;
        $list['pageSize'] = $pageSize;
        $this->assign('request',$request);
        $this->assign('list',$list);
//        $this->display();
    }

    /**
     * @desc 钱包支付图片审核
     *
    $data=[
    'id'=>'1',
    'status'=>8,
    'amount'=>123456,
    'wallet_type'=>20,
    ];
     */
    public function walletAccountImgCheck(){
        if(IS_AJAX){
            $wallet=D('Wallet/Wallet');
            $data=I('post.');
            $data['action']='check';
            $result=$wallet->addWalletAccountImg($data);
            if($result['error']==0&&$result['sendSms']){//新生成的钱包，给用户发送初始密码
                $mobile=$result['sendSms'][0];
                $content= '【玖隆芯城】亲爱的用户,您的钱包初始支付密码：'.$result['sendSms'][1].'，请妥善保管';
                $active='companySon';
                $this->sendSms($mobile,$content,$active);
            }
            die(json_encode($result));
        }
    }


}