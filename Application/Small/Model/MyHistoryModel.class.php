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


class MyHistoryModel extends LinkModel
{
    protected $history_max=10;//最大保存浏览历史量

    /**
     * @desc 我的数据保存：我的浏览历史
     *
     */
    public function myHistorySave($check,$request){
        $mAction_field='user_id,his';
        $action_data=[];
        foreach($request as $k=>$v){
            if(strpos($mAction_field,$k)!==false){
                $action_data[$k]=$v;
            }
        }

        $mAction=M('user_history');
        $one=$mAction->where([['user_id'=>$request['user_id']]])->find();
        $collect=[];
        $result='';
        $msg='';
        if($one){
            $collect=json_decode($one['his'],true);
            if($request['action']=='delete') {//删除我的浏览记录
                $msg='delete';
                if ($request['ids']) {
                    foreach ($collect as $k => $v) {
                        if(in_array($v, $request['ids'])) {
                            unset($collect[$k]);
                        }
                    }
                }
            }else{//编辑
                $msg='edit';
                $collect[]=$request['p_id'];
                $collect=array_unique($collect);
                if(count($collect)>$this->history_max) unset($collect[0]);
            }
            $action_data['his']=json_encode($collect);
            $result=$mAction->where([['user_id'=>$action_data['user_id']]])->field($mAction_field)->save($action_data);
        }else{
            $msg='add';
            $action_data['his']=json_encode([$request['p_id']]);
            $result=$mAction->field($mAction_field)->add($action_data);
        }

        if($result===false){
            return ['error'=>-1,'msg'=>$msg.'_failed'];
        }else{
            return ['error'=>0,'msg'=>$msg.'_success'];
        }
    }

}