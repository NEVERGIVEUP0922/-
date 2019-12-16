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


class MyCollectModel extends LinkModel
{
    protected $collect_max=10;//最大收藏量

    /**
     * @desc 我的数据保存：我的收藏
     *
     */
    public function myCollectSave($check,$request){
        $mAction_field='user_id,collect';
        $action_data=[];
        foreach($request as $k=>$v){
            if(strpos($mAction_field,$k)!==false){
                $action_data[$k]=$v;
            }
        }

        $mAction=M('user_collect');
        $one=$mAction->where([['user_id'=>$request['user_id']]])->find();
        $collect=[];
        $result='';
        $msg='';
        if($one){
            $collect=json_decode($one['collect'],true);
            if($request['action']=='delete'){//删除我的收藏
                $msg='delete';
                if($request['p_id']){
                    foreach($collect as $k=>$v){
                        if(in_array($v,$request['p_id'])){
                            unset($collect[$k]);
                        }
                    }
                }
            }else{
                $msg='edit';
                $collect=array_merge($collect,$request['p_id']);
                $collect=array_unique($collect);
                $collect_num=count($collect);
                if($collect_num>$this->collect_max){
                    $unset_num=(int)($collect_num-$this->collect_max);
                    for($i=0;$i<$unset_num;$i++){
                        unset($collect[$i]);
                    }
                }
            }

            $action_data['collect']=json_encode($collect);
            $result=$mAction->where([['user_id'=>$action_data['user_id']]])->field($mAction_field)->save($action_data);
        }else{
            $msg='add';
            $action_data['collect']=json_encode($request['p_id']);
            $result=$mAction->field($mAction_field)->add($action_data);
        }

        if($result===false){
            return ['error'=>-1,'msg'=>$msg.'_failed'];
        }else{
            return ['error'=>0,'msg'=>$msg.'_success'];
        }
    }

}