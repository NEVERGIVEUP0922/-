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


class WalletaccountModel extends EntryModel
{
    protected $tableName='wallet_account';
    protected $fields='wallet_id,type,amount,project,create_at';

    /**
     * @desc 客户钱包账目列表
     *
     */
    public function walletAccountList($where='',$page='',$pageSize='',$order=''){
        $list=$this->baseList(M('wallet_account','wa_'),$where,$page,$pageSize,$order,$this->fields);
        return $list;
    }




}