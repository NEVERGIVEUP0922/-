<?php

// +----------------------------------------------------------------------
// | FileName:   LoginEvent.class.php
// +----------------------------------------------------------------------
// | Dscription:客户订单表格下载
// +----------------------------------------------------------------------
// | Date:  2018/05/18 22:16
// +----------------------------------------------------------------------
// | Author: kelly  <466395102@qq.com>
// +----------------------------------------------------------------------

namespace Home\Design;


class UserProductDesign {

    /**
     * @desc 样品申请
     *
     */
    public function userProductSample($request,$where=''){
        $table='userProductSample';
        $mongo= new MongoDesign($table);

        if($request['action']){
            $edit_where=['_id'=>(int)$request['_id'],'user_id'=>(int)$request['user_id'],'status'=>['in',[1,10]]];
            $created_at=date('Y-m-d H:i:s',time());
            if($request['action']=='edit'||$request['action']=='check'){
                if($request['action']=='check') $edit_where=['_id'=>(int)$request['_id']];

                $one=$mongo->where($edit_where)->find();
                if(!$one) return ['error'=>-1,'msg'=>'参数错误'];
                $created_at=$one['created_at'];
            }

            if($request['action']=='check'){//管理员样品审核
                if(!in_array((int)$request['status'],[5,10,40])) return ['error'=>-1,'msg'=>'参数错误'];
                $one['status']=(int)$request['status'];
                $items_arr=[];
                foreach($request['items'] as $k=>$v){
                    $items_arr[$v['p_sign']]=$v['check_num'];
                }
                foreach($one['items'] as $k=>$v){
                    $one['items'][$k]['check_num']=$items_arr[$v['p_sign']]?:20;
                }

                $result=$mongo->where($edit_where)->save(['status'=>$one]);
                if(!$result) return ['error'=>-1,'msg'=>'field'];
                return ['error'=>0,'msg'=>'success','data'=>['one'=>$one]];
            }

            $check=$this->userProductSampleCheck($request);
            if($check['error']<0) return $check;

            $add=[
                'user_id'=>$request['user_id'],
                'prototypeDate'=>$request['prototypeDate'],
                'massProductDate'=>$request['massProductDate'],
                'project'=>$request['project'],
                'yield'=>$request['yield'],
                'field'=>$request['field'],
                'check_num'=>0,
                'created_at'=>$created_at,
                'updated_at'=>date('Y-m-d H:i:s',time()),
                'status'=>1,//1新提交未处理,5通过,10全部未通过,40已删除
                'project_status'=>$request['project_status'],
                'items'=>$request['items'],
            ];
        }

        if($request['action']=='add'){
            $result=$mongo->add($add);
            if(!$result) return ['error'=>-1,'msg'=>'field'];
            return ['error'=>0,'msg'=>'success'];
        }else if($request['action']=='edit'){
            $result=$mongo->where($edit_where)->save($add);
            if(!$result) return ['error'=>-1,'msg'=>'field'];
            return ['error'=>0,'msg'=>'success'];
        }

        $list=$mongo->where($where)->select();
        $count=$mongo->where($where)->count();
        return ['error'=>0,'data'=>['list'=>$list,'count'=>$count]];
    }

    /**
     * @desc 样品申请数据检查
     *
     */
    public function userProductSampleCheck($request){
        if(!isset($request['user_id'])||!$request['user_id']) return ['error'=>-1,'msg'=>'没有登陆'];
        if(!isset($request['prototypeDate'])||!$request['prototypeDate']||!strtotime($request['prototypeDate'])) return ['error'=>-1,'msg'=>'原型日期错误'];
        if(!isset($request['massProductDate'])||!$request['massProductDate']||!strtotime($request['massProductDate'])) return ['error'=>-1,'msg'=>'量产日期错误'];
        if(!isset($request['project'])||!$request['project']) return ['error'=>-1,'msg'=>'项目名称错误'];
        if(!isset($request['yield'])||!$request['yield']) return ['error'=>-1,'msg'=>'产量错误'];
        if(!isset($request['field'])||!$request['field']) return ['error'=>-1,'msg'=>'应用领域错误'];
        if(!isset($request['project_status'])||!in_array($request['project_status'],[1,2,3,4])) return ['error'=>-1,'msg'=>'项目状态错误'];//1概念,2原型,3完成设计,4量产
        if(!isset($request['items'])||!$request['items']||!is_array($request['items'])||empty($request['items'])) return ['error'=>-1,'msg'=>'items错误'];

        //检查型号
        $pSign_arr=[];
        foreach($request['items'] as $k=>$v){
            if(!isset($v['num'])||$v['num']>20) return ['error'=>-1,'msg'=>'数量错误'];
            $pSign_arr[]=$v['p_sign'];
        }
        $pSign_arr=array_unique($pSign_arr);
        $pSign_sql=M('product')->field('p_sign')->where(['p_sign'=>['in',$pSign_arr]])->select();
        if(!$pSign_sql) return ['error'=>-1,'msg'=>'型号全都不对'];
        $pSign_sql_arr=[];
        foreach($pSign_sql as $k=>$v){ $pSign_sql_arr[]=$v['p_sign']; }
        $diff=array_diff($pSign_arr,$pSign_sql_arr);
        if($diff) return ['error'=>-1,'msg'=>implode(',',$diff).'型号不对'];
    }

}
