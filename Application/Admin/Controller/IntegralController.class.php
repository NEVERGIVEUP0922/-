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

class IntegralController extends AdminController
{
    public function  integralallList(){
        $request=I('get.');
        $page=$request['page']?$request['page']:1;
        $pageSize=$request['pageSize']?$request['pageSize']:C('PAGE_PAGESIZE');
        if(isset($request['user_id'])&&$request['user_id']){
            $where['user_id']=$request['user_id'];
        }
        $intergralAll=M("intergral_all","wa_")->alias("wia")->join("dx_user as du on du.id=wia.user_id")->where($where);
        $this->assign('intergralAll',$intergralAll);
        $this->assign('request',$request);
        $this->display();
    }
    /**
     * @desc
     * type 基础规则--只针对下单：1单笔,21月度，41年度，特殊规则--101商品，固定积分(111登录,（注册(暂时没有)，112供应方案，113论坛(被'发布需求'占用)），203订单退款，205积分提取去个人钱包,其它211
     * 商品（type 1 21 41 101）参数 type cate_id p_id min_amount,max_amount,scale,start_time，end_time,note,integral_name
     * 项目 参数  type,start_time.end_time,note,item_name，num
     */
    public  function  storeIntegral($again_request=[]){
        $request=$again_request?:I('post.');
        if(!(isset($request['id']))&&!($request['id'])){
        //if(!(isset($request[0]['id']))&&!($request[0]['id'])){
            $request['sys_uid']=session()['adminId'];
            //检查积分名字是否可用
            $return_name=$this->checkIntegral_name($request['integral_name']);
            if($return_name['error'] !=0){
                die(json_encode($return_name));
            }
            //新增规则
            if(in_array($request['type'],[1,21,41,101])){
                //普通项目 特殊项目（商品类目）
                if(isset($request['p_id'])&&$request['p_id']){
                    $caId=explode(',',$request['p_id']);
                    $caId=array_filter($caId);
                    $request['p_id']=implode(',',$caId);
                    if($request['p_id']){
                        $where[]=[
                            'id IN ('.$request['p_id'].')'
                        ];
                    }
                }
                if(isset($request['cate_id'])&&$request['cate_id']){
                    if(isset($request['p_id'])&&$request['p_id']){
                        $where['_logic'] = 'OR';
                    }
                    $caId=explode(',',$request['cate_id']);
                    $caId=array_filter($caId);
                    $request['cate_id']=implode(',',$caId);
                    if($request['cate_id']){
                        $where[]=[
                            'cate_id in (select id from dx_category left join (select * from (select lft as lft2,rht as rht2 from dx_category where id IN ('.$request['cate_id'].')) as lef_rht) as lef_rht2 on 1=1 where dx_category.lft>=lef_rht2.lft2 and dx_category.rht<=lef_rht2.rht2)'
                        ];
                    }
                }
                //查询积分项目是否存在
                if(isset($request['cell_code'])&&$request['cell_code']){

                    $cellId=explode(',',$request['cell_code']);
                    $cellId=array_filter($cellId);
                    $request['cell_code']=implode(',',$cellId);
                    $return_cellCode=M('integral_item','wa_')->where(['id'=>['IN',$cellId]])->find();
                    if(!$return_cellCode){
                        die(json_encode(['error'=>1,'msg'=>'积分项目不存']));
                    };
                }else{
                    $request['cell_code']='';
                }
                //查询商品ID
                $product_id=M('product')->field("id")->where($where)->select();


                if($product_id){
                    if($where){
                        $request['p_signs']=implode(',',array_column($product_id,'id'));
                    }else{
                        $request['p_signs']='';
                    }
                    //判断规则是否重复
                    $intergral_where['type']=$request['type'];
                    $intergral_where[]=[
                        'start_time'=>["between",[$request['start_time'],$request['end_time']]],
                        'end_time'=>["between",[$request['start_time'],$request['end_time']]],
                        '_logic'=>'or'
                    ];
                    $intergral_all=M('integral_rule','wa_')->where($intergral_where)->select();
                    //echo M()->getLastSql();die();
                    foreach ($intergral_all as $v){
                        unset($all_where);
                        if(in_array($request['type'],[1,21,41])){
                            if($v['cate_all']){
                                $all_where[]=[
                                    'cate_id not IN ('.$v['cate_all'].')'
                                ];
                            }
                            if($v['cell_code']){
                                $all_where[]=[
                                    'id not IN ('.$v['cell_code'].')'
                                ];
                            }
                            if(count($all_where)>1){
                                $all_where["_logic"]="OR";
                            }

                        }else{
                            if($v['cate_all']){
                                $all_where[]=[
                                    'cate_id  IN ('.$v['cate_all'].')'
                                ];
                            }
                            if($v['cell_code']){
                                $all_where[]=[
                                    'id  IN ('.$v['cell_code'].')'
                                ];
                            }

                        }
                        $res_product=M('product')->field("id")->where($all_where)->select();
//                        echo M()->getLastSql();die();
                        //if($v["id"]==39) {echo M()->getLastSql();die();}
                        if($res_product){
                            $res_product=array_column($res_product,'id');
                            $type_integral=explode(',',$v['p_signs']);
                            $type_integral=array_merge($type_integral,$res_product);
                        }else{
                            $type_integral=explode(',',$v['p_signs']);
                        }

                        $sect_res=array_intersect(array_column($product_id,'id'),$type_integral);
                        if($sect_res){
                            if(!$v["max_amount"]){
                                if(!$request["max_amount"]){
                                    die(json_encode(['error'=>1,'msg'=>'积分商品与规则ID='.$v['id'].'重复']));
                                }else{
                                    if($request["max_amount"]<=$v["max_amount"]){
                                        die(json_encode(['error'=>1,'msg'=>'积分商品与规则ID='.$v['id'].'重复']));
                                    }
                                }
                            }else{

                                if(($request["min_amount"]>=$v["min_amount"])&&($request["min_amount"]<=$v["max_amount"])){
                                    die(json_encode(['error'=>1,'msg'=>'积分商品与规则ID='.$v['id'].'重复']));
                                }

                                if($request["max_amount"]>=$v["min_amount"]&&$request["max_amount"]<=$v["max_amount"]){
                                    die(json_encode(['error'=>1,'msg'=>'积分商品与规则ID='.$v['id'].'重复']));
                                }
                            }

                        }
                    };
                    $field="type,cell_code,p_signs,min_amount,max_amount,scale,start_time,end_time,note,integral_name,cate_all,sys_uid";
                    $request["p_signs"]=$request['p_id'];
                    $request["cell_code"]=$request['p_signs'];
                    $integral_add=M('integral_rule','wa_')->field($field)->add($request);
                    if($integral_add){
                        if($request['status']==='0'){
                            die(json_encode(['error'=>0,'msg'=>'提交成功，需要重新待审核']));
                        }else{
                            die(json_encode(['error'=>0,'msg'=>'提交成功，待审核']));
                        }
                    }else{
                        die(json_encode(['error'=>1,'msg'=>'执行失败1']));
                    }
                }else{
                    die(json_encode(['error'=>1,'msg'=>'提交的商品id不存在1']));
                }
            }else{
                //项目积分
                $intergral_where['type']=$request['type'];
                $intergral_where[]=[
                    'start_time'=>["between",[$request['start_time'],$request['end_time']]],
                    'end_time'=>["between",[$request['start_time'],$request['end_time']]],
                    '_logic'=>'or'
                ];
                $intergral_all=M('integral_rule','wa_')->where($intergral_where)->select();

                if($intergral_all){
                    die(json_encode(['error'=>1,'msg'=>'生效日期与存在的日期叠加']));
                }
                $field="type,start_time,end_time,note,integral_name,num,sys_uid";
                $intergral_add=M("integral_rule",'wa_')->field($field)->add($request);
                if($intergral_add){
                    die(json_encode(['error'=>0,'msg'=>'提交成功，待审核']));
                }else{
                    die(json_encode(['error'=>1,'msg'=>'提交失败，请检查原因']));
                }
            }
        }else{
            $interOne=M("integral_rule",'wa_')->where(['id'=>$request['id']])->find();
            if($interOne['status']!=0&&$interOne['status']!=31) {
                //die(json_encode(['error'=>1,'msg'=>'该规则已经审核过，请选择新增规则']))
                //检查积分名字是否可用
                $return_name=$this->checkIntegral_name($request['integral_name']);
                if($return_name['error'] !=0){
                    die(json_encode($return_name));
                }
                unset($request['id']);
                $request['status']='0';
                $this->storeIntegral($request);
                die();
            };
            //修改规则
            if(in_array($request['type'],[1,21,41,101])){
                //普通项目 特殊项目（商品类目）
                if(isset($request['cate_id'])&&$request['cate_id']){
                    $caId=explode(',',$request['cate_id']);
                    $caId=array_filter($caId);
                    $request['cate_id']=implode(',',$caId);
                    if($request['cate_id']){
                   
                        $where[]=[
                            'cate_id in (select id from dx_category left join (select * from (select lft as lft2,rht as rht2 from dx_category where id IN ('.$request['cate_id'].')) as lef_rht) as lef_rht2 on 1=1 where dx_category.lft>=lef_rht2.lft2 and dx_category.rht<=lef_rht2.rht2)'
                        ];
                    }
                }
                if(isset($request['p_id'])&&$request['p_id']){
                    if(isset($request['cate_id'])&&$request['cate_id']){
                        $where['_logic'] = 'or';
                    }
                    $caId=explode(',',$request['p_id']);
                    $caId=array_filter($caId);
                    $request['p_id']=implode(',',$caId);
                    if($request['p_id']){
                    
                        $where[]=[
                            'id IN ('.$request['p_id'].')'
                        ];
                    }
                }
             
                //查询商品ID
                $product_id=M('product')->field("id")->where($where)->select();
                if($product_id){
                    $request['p_signs']=implode(',',array_column($product_id,'id'));
                    //判断规则是否重复
                    $intergral_where['type']=$request['type'];
                    $intergral_where[]=[
                        'start_time'=>["between",[$request['start_time'],$request['end_time']]],
                        'end_time'=>["between",[$request['start_time'],$request['end_time']]],
                        '_logic'=>'or'
                    ];
                    $intergral_where['id']=['neq',$request['id']];
                    $intergral_all=M('integral_rule','wa_')->where($intergral_where)->select();
                    foreach ($intergral_all as $v){
                        unset($all_where);
                        if(in_array($request['type'],[1,21,41])){
                            if($v['cate_all']){
                                $all_where[]=[
                                    'cate_id not IN ('.$v['cate_all'].')'
                                ];
                            }
                            if($v['cell_code']){
                                //$all_where["id"]=[  	//阿木木
                                $all_where[]=[			//kk
                                    'id not IN ('.$v['cell_code'].')'
                                ];
                            }
                            if(count($all_where)>1){
                                $all_where["_logic"]="OR";
                            }

                        }else{
                            if($v['cate_all']){
                                $all_where[]=[
                                    'cate_id  IN ('.$v['cate_all'].')'
                                ];
                            }
                            if($v['cell_code']){
                                //$all_where['id']=[  //阿木木
                                $all_where[]=[		  //kk
                                    'id  IN ('.$v['cell_code'].')'
                                ];
                            }
                        }
                        $res_product=M('product')->field("id")->where($all_where)->select();
                        if($res_product){
                            $res_product=array_column($res_product,'id');
                            $type_integral=explode(',',$v['p_signs']);
                            $type_integral=array_merge($type_integral,$res_product);
                        }else{
                            $type_integral=explode(',',$v['p_signs']);
                        }
                        $sect_res=array_intersect(array_column($product_id,'id'),$type_integral);
                        if($sect_res){
                            die(json_encode(['error'=>1,'msg'=>'积分商品与规则ID='.$v['id'].'重复']));
                        }
                    }
                    $request['status']=0;
                    $field="type,cell_code,p_signs,min_amount,max_amount,scale,start_time,end_time,note,integral_name,cate_all,status";
                    $request["p_signs"]=$request['p_id'];
                    $request["cell_code"]=$request['p_signs'];
                    $integral_add=M('integral_rule','wa_')->field($field)->where(['id'=>$request['id']])->save($request);
                    //echo M()->getLastSql();die();
                    if($integral_add){
                        die(json_encode(['error'=>0,'msg'=>'提交成功，待审核']));
                    }else{
                        die(json_encode(['error'=>1,'msg'=>'提交失败或没数据更新']));
                    }
                }else{
                    die(json_encode(['error'=>1,'msg'=>'提交的商品id不存在']));
                }
            }else{
                //项目积分
                $intergral_where['type']=$request['type'];
                $intergral_where['id']=['neq',$request['id']];
                $intergral_where[]=[
                    'start_time'=>["between",[$request['start_time'],$request['end_time']]],
                    'end_time'=>["between",[$request['start_time'],$request['end_time']]],
                    '_logic'=>'or'
                ];
                $intergral_all=M('integral_rule','wa_')->where($intergral_where)->select();

                if($intergral_all){
                    die(json_encode(['error'=>1,'msg'=>'生效日期与存在的日期叠加']));
                }
                $request['status']=0;
                $field="type,start_time.end_time,note,integral_name，num,status";
                $intergral_add=M("integral_rule",'wa_')->field($field)->where(['id'=>$request['id']])->save($request);
                if($intergral_add){
                    die(json_encode(['error'=>0,'msg'=>'执行成功']));
                }else{
                    die(json_encode(['error'=>1,'msg'=>'执行失败，请检查原因']));
                }
            }
        }
    }
    /*public  function  storeIntegral($again_request=[]){
        $request=$again_request?:I('post.');
        if(!(isset($request[0]['id']))&&!($request[0]['id'])){
            $request['sys_uid']=session()['adminId'];
            //检查积分名字是否可用
            $return_name=$this->checkIntegral_name($request['integral_name']);
            if($return_name['error'] !=0){
                die(json_encode($return_name));
            }
            //新增规则
            if(in_array($request['type'],[1,21,41,101])){
                //普通项目 特殊项目（商品类目）
                if(isset($request['p_id'])&&$request['p_id']){
                    $caId=explode(',',$request['p_id']);
                    $caId=array_filter($caId);
                    $request['p_id']=implode(',',$caId);
                    if($request['p_id']){
                        $where[]=[
                            'id IN ('.$request['p_id'].')'
                        ];
                    }
                }
                if(isset($request['cate_id'])&&$request['cate_id']){
                    if(isset($request['p_id'])&&$request['p_id']){
                        $where['_logic'] = 'OR';
                    }
                    $caId=explode(',',$request['cate_id']);
                    $caId=array_filter($caId);
                    $request['cate_id']=implode(',',$caId);
                    if($request['cate_id']){
                        $where[]=[
                            'cate_id in (select id from dx_category left join (select * from (select lft as lft2,rht as rht2 from dx_category where id IN ('.$request['cate_id'].')) as lef_rht) as lef_rht2 on 1=1 where dx_category.lft>=lef_rht2.lft2 and dx_category.rht<=lef_rht2.rht2)'
                        ];
                    }
                }
                //查询积分项目是否存在
                if(isset($request['cell_code'])&&$request['cell_code']){

                    $cellId=explode(',',$request['cell_code']);
                    $cellId=array_filter($cellId);
                    $request['cell_code']=implode(',',$cellId);
                    $return_cellCode=M('integral_item','wa_')->where(['id'=>['IN',$cellId]])->find();
                    if(!$return_cellCode){
                        die(json_encode(['error'=>1,'msg'=>'积分项目不存']));
                    };
                }else{
                    $request['cell_code']='';
                }
                //查询商品ID
                $product_id=M('product')->field("id")->where($where)->select();

                //echo 	M()->getLastSql();die();
                if($product_id){
                    if($where){
                        $request['p_signs']=implode(',',array_column($product_id,'id'));
                    }else{
                        $request['p_signs']='';
                    }
                    //判断规则是否重复
                    $intergral_where['type']=$request['type'];
                    $intergral_where[]=[
                        'start_time'=>["between",[$request['start_time'],$request['end_time']]],
                        'end_time'=>["between",[$request['start_time'],$request['end_time']]],
                        '_logic'=>'or'
                    ];
                    $intergral_all=M('integral_rule','wa_')->where($intergral_where)->select();
                    //echo M()->getLastSql();die();
                    foreach ($intergral_all as $v){
                        unset($all_where);
                        $all_where[]=[
                            'cate_id IN ('.$v['cate_all'].')'
                        ];
                        $res_product=M('product')->field("id")->where($all_where)->select();
                        //if($v["id"]==39) {echo M()->getLastSql();die();}
                        if($res_product){
                            $res_product=array_column($res_product,'id');
                            $type_integral=explode(',',$v['p_signs']);
                            $type_integral=array_merge($type_integral,$res_product);
                        }else{
                            $type_integral=explode(',',$v['p_signs']);
                        }

                        $sect_res=array_intersect(array_column($product_id,'id'),$type_integral);
                        //print_r($sect_res);die();
                        if($sect_res){
                            die(json_encode(['error'=>1,'msg'=>'积分商品与规则ID='.$v['id'].'重复']));
                        }
                    };
                    $field="type,cell_code,p_signs,min_amount,max_amount,scale,start_time,end_time,note,integral_name,cate_all,sys_uid";
                    $request["p_signs"]=$request['p_id'];
                    $integral_add=M('integral_rule','wa_')->field($field)->add($request);
                    if($integral_add){
                        if($request['status']==='0'){
                            die(json_encode(['error'=>0,'msg'=>'提交成功，需要重新待审核']));
                        }else{
                            die(json_encode(['error'=>0,'msg'=>'提交成功，待审核']));
                        }
                    }else{
                        die(json_encode(['error'=>1,'msg'=>'执行失败1']));
                    }
                }else{
                    die(json_encode(['error'=>1,'msg'=>'提交的商品id不存在1']));
                }
            }else{
                //项目积分
                $intergral_where['type']=$request['type'];
                $intergral_where[]=[
                    'start_time'=>["between",[$request['start_time'],$request['end_time']]],
                    'end_time'=>["between",[$request['start_time'],$request['end_time']]],
                    '_logic'=>'or'
                ];
                $intergral_all=M('integral_rule','wa_')->where($intergral_where)->select();

                if($intergral_all){
                    die(json_encode(['error'=>1,'msg'=>'生效日期与存在的日期叠加']));
                }
                $field="type,start_time,end_time,note,integral_name,num,sys_uid";
                $intergral_add=M("integral_rule",'wa_')->field($field)->add($request);
                if($intergral_add){
                    die(json_encode(['error'=>0,'msg'=>'提交成功，待审核']));
                }else{
                    die(json_encode(['error'=>1,'msg'=>'提交失败，请检查原因']));
                }
            }
        }else{
            $interOne=M("integral_rule",'wa_')->where(['id'=>$request['id']])->find();
            if($interOne['status']!=0&&$interOne['status']!=31) {
                //die(json_encode(['error'=>1,'msg'=>'该规则已经审核过，请选择新增规则']))
                //检查积分名字是否可用
                $return_name=$this->checkIntegral_name($request['integral_name']);
                if($return_name['error'] !=0){
                    die(json_encode($return_name));
                }
                unset($request['id']);
                $request['status']='0';
                $this->storeIntegral($request);
                die();
            };
            //修改规则
            if(in_array($request['type'],[1,21,41,101])){
                //普通项目 特殊项目（商品类目）
                if(isset($request['cate_id'])&&$request['cate_id']){
                    $caId=explode(',',$request['cate_id']);
                    $caId=array_filter($caId);
                    $request['cate_id']=implode(',',$caId);
                    if($request['cate_id']){
                        $where[]=[
                            'cate_id in (select id from dx_category left join (select * from (select lft as lft2,rht as rht2 from dx_category where id IN ('.$request['cate_id'].')) as lef_rht) as lef_rht2 on 1=1 where dx_category.lft>=lef_rht2.lft2 and dx_category.rht<=lef_rht2.rht2)'
                        ];
                    }
                }
                if(isset($request['p_id'])&&$request['p_id']){
                    if(isset($request['cate_id'])&&$request['cate_id']){
                        $where['_logic'] = 'or';
                    }
                    $caId=explode(',',$request['p_id']);
                    $caId=array_filter($caId);
                    $request['p_id']=implode(',',$caId);
                    if($request['p_id']){
                        $where[]=[
                            'id IN ('.$request['p_id'].')'
                        ];
                    }
                }
                //查询商品ID
                $product_id=M('product')->field("id")->where($where)->select();
                if($product_id){
                    $request['p_signs']=implode(',',array_column($product_id,'id'));
                    //判断规则是否重复
                    $intergral_where['type']=$request['type'];
                    $intergral_where[]=[
                        'start_time'=>["between",[$request['start_time'],$request['end_time']]],
                        'end_time'=>["between",[$request['start_time'],$request['end_time']]],
                        '_logic'=>'or'
                    ];
                    $intergral_where['id']=['neq',$request['id']];
                    $intergral_all=M('integral_rule','wa_')->where($intergral_where)->select();
                    foreach ($intergral_all as $v){
                        $all_where[]=[
                            'cate_id IN ('.$intergral_all['cate_all'].')'
                        ];
                        $res_product=M('product')->field("id")->where($all_where)->select();
                        if($res_product){
                            $res_product=array_column($res_product,'id');
                            $type_integral=explode(',',$v['p_signs']);
                            $type_integral=array_merge($type_integral,$res_product);
                        }else{
                            $type_integral=explode(',',$v['p_signs']);
                        }
                        $sect_res=array_intersect(array_column($product_id,'id'),$type_integral);
                        if($sect_res){
                            die(json_encode(['error'=>1,'msg'=>'积分商品与规则ID='.$v['id'].'重复']));
                        }
                    }
                    $request['status']=0;
                    $field="type,cell_code,p_signs,min_amount,max_amount,scale,start_time,end_time,note,integral_name,cate_all,status";
                    $request["p_signs"]=$request['p_id'];
                    $integral_add=M('integral_rule','wa_')->field($field)->where(['id'=>$request['id']])->save($request);
                    //echo M()->getLastSql();die();
                    if($integral_add){
                        die(json_encode(['error'=>0,'msg'=>'提交成功，待审核']));
                    }else{
                        die(json_encode(['error'=>1,'msg'=>'提交失败或没数据更新']));
                    }
                }else{
                    die(json_encode(['error'=>1,'msg'=>'提交的商品id不存在']));
                }
            }else{
                //项目积分
                $intergral_where['type']=$request['type'];
                $intergral_where['id']=['neq',$request['id']];
                $intergral_where[]=[
                    'start_time'=>["between",[$request['start_time'],$request['end_time']]],
                    'end_time'=>["between",[$request['start_time'],$request['end_time']]],
                    '_logic'=>'or'
                ];
                $intergral_all=M('integral_rule','wa_')->where($intergral_where)->select();

                if($intergral_all){
                    die(json_encode(['error'=>1,'msg'=>'生效日期与存在的日期叠加']));
                }
                $request['status']=0;
                $field="type,start_time.end_time,note,integral_name，num,status";
                $intergral_add=M("integral_rule",'wa_')->field($field)->where(['id'=>$request['id']])->save($request);
                if($intergral_add){
                    die(json_encode(['error'=>0,'msg'=>'执行成功']));
                }else{
                    die(json_encode(['error'=>1,'msg'=>'执行失败，请检查原因']));
                }
            }
        }
    }*/
    //检查规则名字名字是否重复
    public function checkIntegral_name($check_name=''){
        //暂时关闭积分规则名字重复检查
        return ['error'=>0,'msg'=>'规则名字可以用'];
        $name_data=$check_name?['integral_name'=>$check_name]:I('post.');
        if(isset($name_data['integral_name']) && $name_data['integral_name']){
            $return_check=M('integral_rule','wa_')->where(['integral_name'=>$name_data['integral_name']])->find();
            if($check_name){
                if($return_check){
                    return ['error'=>1,'msg'=>'规则名字已被占用'];
                }else{
                    return ['error'=>0,'msg'=>'规则名字可以用'];
                }
            }
            if($return_check){
                die(json_encode(['error'=>1,'msg'=>'规则名字已被占用']));
            }else{
                die(json_encode(['error'=>0,'msg'=>'规则名字可以用']));
            }
        }else{
            if($check_name){
                return ['error'=>1,'msg'=>'参数错误1'];
            }
            die(json_encode(['error'=>1,'msg'=>'参数错误']));
        }

    }
    /*新增--编辑*/
    public function add_edit_points(){
        $request=I('get.');
        $request['page']=$page=$request['page']?$request['page']:1;
        $request['pageSize']=$pageSize=$request['pageSize']?$request['pageSize']:20;
        $integralLists=M("integral_rule",'wa_')->where(['id'=>$request['id']])->select();
        foreach ($integralLists as $k=>&$v){
            if($v['cate_all']){
                $v['cateList']=M('category')->where(['id'=>['IN',$v['cate_all']]])->select();
            }else{
                $v['cateList']=[];
            }
            if($v['p_signs']){
                $v['productList']=M('product')->where(['id'=>['IN',$v['p_signs']]])->select();
            }else{
                $v['productList']=[];
            }
        }
        $categoryTree=D('category')->productCategoryInfinite(); //分类树
        if($categoryTree['error']!=0) die(json_encode($categoryTree));
        $integralItem=M('integral_item','wa_')->where(['item_status'=>['IN',['0','1']],'integral_rule_id'=>0])->select();
        ;
        $list['page'] = $page;
        $list['pageSize'] = $pageSize;
        $this->assign('request',$request);
        $this->assign('categoryTree',$categoryTree);
        $this->assign('integralList',$integralLists);
        $this->assign('integralItem',$integralItem);
        $this->display('points/add_edit_points');
    }
    /**
     * @param int id 规则id
     * @param int status 规则状态 0申请 1启用  11停用 21审核通过 31审核拒绝
     * @param return  json
     *
     *
     * 删除功能  多传一个delete=1
     *
     */
    public function checkIntegral(){
        $request=I('post.');
        $integral_rule=M("integral_rule",'wa_')->where(['id'=>$request['id']])->find();
        if(!$integral_rule) die(json_encode(['error'=>1,'msg'=>'规则不存在']));
        if(isset($request['delete'])&&$request['delete']==1){
            if($request['status']!=31&&$integral_rule['status']!=0) die(json_encode(['error'=>1,'msg'=>'执行有误']));
            $res=M("integral_rule",'wa_')->where(['id'=>$request['id']])->delete();
            if($res)  die(json_encode(['error'=>0,'msg'=>'删除成功']));
            else    die(json_encode(['error'=>1,'msg'=>'删除失败']));
        }
        if($request['status']==21&&$integral_rule['status']!=0) die(json_encode(['error'=>1,'msg'=>'执行有误']));
        if($request['status']==11&&$integral_rule['status']!=1) die(json_encode(['error'=>1,'msg'=>'执行有误']));
        if($request['status']==31&&$integral_rule['status']!=0) die(json_encode(['error'=>1,'msg'=>'执行有误']));
        if($request['status']==1&&($integral_rule['status']!=0||$integral_rule['status']!=31)) die(json_encode(['error'=>1,'msg'=>'执行有误']));

        if($request['status']==11)  $res=M("integral_rule",'wa_')->where(['id'=>$request['id']])->save(['status'=>$request['status'],'end_time'=>date("Y-m-d H:i:s")]);
        if($request['status']==1)  $res=M("integral_rule",'wa_')->where(['id'=>$request['id']])->save(['status'=>$request['status'],'start_time'=>date("Y-m-d H:i:s")]);
        else $res=M("integral_rule",'wa_')->where(['id'=>$request['id']])->save(['status'=>$request['status']]);
        if($res){
            die(json_encode(['error'=>0,'msg'=>'操作成功']));
        }else{
            die(json_encode(['error'=>1,'msg'=>'操作失败']));
        }
    }

    /**积分规则列表*/
    public  function integralRuleList(){
        $request=I('get.');
        //分页  每页20条
        $request['page']=$page=$request['page']?$request['page']:1;
        $request['pageSize']=$pageSize=$request['pageSize']?$request['pageSize']:20;
        $where=[];
        $request['status']>=0&&isset( $request['status'])&&$where['status']=$request['status'];
        $request['type']&&$where['type']=$request['type'];
        $integrallist=M("integral_rule",'wa_')->order("id desc")->where($where)->page($page,$pageSize)->select();
        foreach ($integrallist as $k=>&$v){
            if($v['cate_all']){
                $v['cateList']=M('category')->where(['id'=>['IN',$v['cate_all']]])->select();
            }else{
                $v['cateList']=[];
            }

            if($v['p_signs']){
                $v['productList']=M('product')->where(['id'=>['IN',$v['p_signs']]])->select();
            }else{
                $v['productList']=[];
            }
            if($v['cell_code']){
                $v['integral_item']=M('integral_item','wa_')->where(['id'=>['IN',$v['cell_code']]])->select();
            }else{
                $v['integral_item']=[];
            }
        }
        $count=M('integral_rule','wa_')->field("count(id) as count")->where($where)->find();
        $request['count']=$count['count'];

        $this->assign('integralList',$integrallist);
        $this->assign('request',$request);
        $this->display('points/integralRuleList');
    }
    public  function integralOne(){
        $request=I('get.');
        //分页  每页20条
        $request['page']=$page=$request['page']?$request['page']:1;
        $request['pageSize']=$pageSize=$request['pageSize']?$request['pageSize']:20;
        $integrallist=M("integral_rule",'wa_')->where(['id'=>$request['id']])->find();
        foreach ($integrallist as $k=>&$v){
            if($v['cate_all']){
                $v['cateList']=M('category')->where(['id'=>['IN',$v['cate_all']]])->select();
            }else{
                $v['cateList']=[];
            }

            if($v['p_signs']){
                $v['productList']=M('product')->where(['id'=>['IN',$v['p_signs']]])->select();
            }else{
                $v['productList']=[];
            }
        }
        $this->assign('integrallist',$integrallist);
        //$this->display();
    }


    /**
     * @desc 对私的银行转帐申请确认
     * type 基础规则--只针对下单：1单笔,21月度，41年度，特殊规则--101商品，固定积分(111登录,（注册(暂时没有)，112供应方案，113论坛(被'发布需求'占用)），203订单退款，205积分提取去个人钱包,其它211
     * 商品（type 1 21 41 101）参数 type cate_id或者p_id min_amount,max_amount,scale,start_time，end_time,note,integral_name
     * 项目 参数  type,start_time.end_time,note,integral_name，num
     * 组长创建申请  总监审核（状态status 0申请  1启用 11停用  21审核通过 31审核拒绝(可以修改)）
     */

    public  function  storeIntegral1(){
        $session=session();
        $department_id=$session['adminInfo']['department_id'];
        $level=M('user_department','sys_')->where(['id'=>$department_id])->find();
        if($level['department_level']==1){

            die(json_encode(['error'=>1,'msg'=>'请使用总监 组长账号创建']));
        }
        $request=I('post.');
        if(isset($request['id'])&&$request['id']){
            //修改
            if(in_array($request['type'],[1,21,41,101])){
                //普通项目 特殊项目（商品类目）
                if(isset($request['cate_id'])&&$request['cate_id']){
                    $caId=explode(',',$request['cate_id']);
                    $caId=array_filter($caId);
                    $request['cate_id']=implode(',',$caId);
                    $where[]=[
                        'cate_id in (select id from dx_category left join (select * from (select lft as lft2,rht as rht2 from dx_category where id IN ('.$request['cate_id'].')) as lef_rht) as lef_rht2 on 1=1 where dx_category.lft>=lef_rht2.lft2 and dx_category.rht<=lef_rht2.rht2)'
                    ];
                }
                if(isset($request['p_id'])&&$request['p_id']){
                    $caId=explode(',',$request['p_id']);
                    $caId=array_filter($caId);
                    $request['p_id']=implode(',',$caId);
                    $where[]=[
                        'id IN ('.$request['p_id'].')'
                    ];
                }
                $where[] = 'or';
                //查询商品ID
                $product_id=M('product')->field("id")->where($where)->select();
                if($product_id){
                    $request['p_signs']=explode(',',array_column($product_id,'id'));


                    //判断规则是否重复
                    $intergral_where['type']=$request['type'];
                    $intergral_where[]=[
                        'start_time'=>["between",[$request['start_time'],$request['end_time']]],
                        'end_time'=>["between",[$request['start_time'],$request['end_time']]],
                        'or'
                    ];
                    $intergral_where['id']=['neq',$request['id']];
                    $intergral_all=M('integral_rule','wa_')->where($intergral_where)->select();
                    foreach ($intergral_all as $v){
                        $type_integral=implode(',',$v['p_signs']);
                        $sect_res=array_intersect(array_column($product_id,'id'),$type_integral);
                        if($sect_res){
                            die(json_encode(['error'=>1,'msg'=>'积分商品与规则ID='.$v['id'].'重复']));
                        }
                    }


                    $field="type,cell_code,p_signs,min_amount,max_amount,scale,start_time，end_time,note,integral_name";
                    $integral_add=M('integral_rule','wa_')->field($field)->where(['id'=>$request['id']])->save($request);
                    if($integral_add){
                        die(json_encode(['error'=>0,'msg'=>'提交成功，待审核']));
                    }else{
                        die(json_encode(['error'=>1,'msg'=>'执行失败']));
                    }
                }else{
                    die(json_encode(['error'=>1,'msg'=>'提交的商品id不存在']));
                }

            }else{
                //项目积分
                $intergral_where['type']=$request['type'];
                $intergral_where[]=[
                    'start_time'=>["between",[$request['start_time'],$request['end_time']]],
                    'end_time'=>["between",[$request['start_time'],$request['end_time']]],
                    'or'
                ];
                $intergral_where['id']=['neq',$request['id']];
                $intergral_all=M('integral_rule','wa_')->where($intergral_where)->select();

                if($intergral_all){
                    die(json_encode(['error'=>1,'msg'=>'生效日期与存在的日期叠加']));
                }
                $field="type,start_time.end_time,note,integral_name，num";
                $intergral_add=M("integral_rule",'wa_')->field($field)->where(['id'=>$request['id']])->save($request);
                if($intergral_add){
                    die(json_encode(['error'=>0,'msg'=>'执行成功']));
                }else{
                    die(json_encode(['error'=>1,'msg'=>'执行失败，请检查原因']));
                }
            }

        }

        //新增
        if(in_array($request['type'],[1,21,41,101])){
            //普通项目 特殊项目（商品类目）
            if(isset($request['cate_id'])&&$request['cate_id']){
                $caId=explode(',',$request['cate_id']);
                $caId=array_filter($caId);
                $request['cate_id']=implode(',',$caId);
                $where[]=[
                    'cate_id in (select id from dx_category left join (select * from (select lft as lft2,rht as rht2 from dx_category where id IN ('.$request['cate_id'].')) as lef_rht) as lef_rht2 on 1=1 where dx_category.lft>=lef_rht2.lft2 and dx_category.rht<=lef_rht2.rht2)'
                ];
            }
            if(isset($request['p_id'])&&$request['p_id']){
                $caId=explode(',',$request['p_id']);
                $caId=array_filter($caId);
                $request['p_id']=implode(',',$caId);
                $where[]=[
                    'id IN ('.$request['p_id'].')'
                ];
            }
            $where[] = 'or';
            //查询商品ID
            $product_id=M('product')->field("id")->where($where)->select();
            if($product_id){
                $request['p_signs']=explode(',',array_column($product_id,'id'));


                //判断规则是否重复
                $intergral_where['type']=$request['type'];
                $intergral_where[]=[
                    'start_time'=>["between",[$request['start_time'],$request['end_time']]],
                    'end_time'=>["between",[$request['start_time'],$request['end_time']]],
                    'or'
                ];
                $intergral_all=M('integral_rule','wa_')->where($intergral_where)->select();
                foreach ($intergral_all as $v){
                    $type_integral=implode(',',$v['p_signs']);
                    $sect_res=array_intersect(array_column($product_id,'id'),$type_integral);
                    if($sect_res){
                        die(json_encode(['error'=>1,'msg'=>'积分商品与规则ID='.$v['id'].'重复']));
                    }
                }


                $field="type,cell_code,p_signs,min_amount,max_amount,scale,start_time，end_time,note,integral_name";
                $integral_add=M('integral_rule','wa_')->field($field)->add($request);
                if($integral_add){
                    die(json_encode(['error'=>0,'msg'=>'提交成功，待审核']));
                }else{
                    die(json_encode(['error'=>1,'msg'=>'执行失败']));
                }
            }else{
                die(json_encode(['error'=>1,'msg'=>'提交的商品id不存在']));
            }

        }else{
            //项目积分
            $intergral_where['type']=$request['type'];
            $intergral_where[]=[
                'start_time'=>["between",[$request['start_time'],$request['end_time']]],
                'end_time'=>["between",[$request['start_time'],$request['end_time']]],
                'or'
            ];
            $intergral_all=M('integral_rule','wa_')->where($intergral_where)->select();

            if($intergral_all){
                die(json_encode(['error'=>1,'msg'=>'生效日期与存在的日期叠加']));
            }
            $field="type,start_time.end_time,note,integral_name，num";
            $intergral_add=M("integral_rule",'wa_')->field($field)->add($request);
            if($intergral_add){
                die(json_encode(['error'=>0,'msg'=>'执行成功']));
            }else{
                die(json_encode(['error'=>1,'msg'=>'执行失败，请检查原因']));
            }
        }
    }

    /**
     * @desc 添加积分规则
     * @param status  0申请  21审核通过（）   31审核拒绝（可以修改）  1审核通过后启用（启用后用过有停用按钮）  2停用(停用后可以开启)
     * @param  id   积分规则ID
     */
    public  function  integralRuleAction(){
        $request=I('post.');
        $where['id']=$request['id'];
        $save['status']=$request['status'];
        $saveRes=M('integral_rule','wa_')->where($where)->save($save);
        if($saveRes){
            die(json_encode(['error'=>0,'msg'=>'状态修改成功']));
        }else{
            die(json_encode(['error'=>1,'msg'=>'状态修改失败']));
        }
    }
    /**
     * @desc 积分列表
     *
     */
    public function integralList02(){
        $request=I('get.');
        $page=$request['page']?$request['page']:1;
        $pageSize=$request['pageSize']?$request['pageSize']:C('PAGE_PAGESIZE');
        $where='';
        $where['integral_amount']=['neq',0];
        $wallet=D('Wallet/Integral');
        $list=$wallet->integralList($other=['where'=>['user_id'=>136]],$page,$pageSize);

        $list['page'] = $page;
        $list['pageSize'] = $pageSize;
        $this->assign('request',$request);
        $this->assign('list',$list);
        $this->display('points/integralList');
    }

    /**
     * @desc 积分列表 新方法
     *
     */
    public function integralList(){
        $request=I('get.');
        $page=$request['page']?$request['page']:1;
        $pageSize=$request['pageSize']?$request['pageSize']:C('PAGE_PAGESIZE');
        $where='';
        if(isset($request['d_status'])){
            $where['d_status']=$request['d_status'];
        }
        if(isset($request['user_id'])&&!empty($request['user_id'])){
            $where['user_id']=$request['user_id'];
        }
        $field="ia.*,du.user_name,du.nick_name,du.fcustjc";
        $subQuery02=M("intergral_all","wa_")->alias("ia")->field($field)->join("dx_user as du on du.id=ia.user_id")->where(['order_sn'=>''])->buildSql();
        $subQuery=M("intergral_all","wa_")->alias("ia")->field($field)->join("dx_user as du on du.id=ia.user_id")->where(['order_sn'=>['neq','']])->group('order_sn')->union($subQuery02)->buildSql();
        $rang_1=M("intergral_all","wa_")->alias("ia")->field($field)->join("dx_user as du on du.id=ia.user_id")->where(['order_sn'=>['neq','']])->group('order_sn')->where()->select();
        $rang = array_column($rang_1, 'id');
        //$final=M()->table($rang_1.' AS new_table')->where(['user_id'=>['like','%'.'50'.'%']])->select();
       // $where0['id']=['IN',$rang];
        //$where0['_logic']='or';
        //$where0['order_sn']='';
        //$where[]=$where0;
        $where['integral_amount']=['neq',0];
        $list=M("intergral_all","wa_")->alias("ia")->field($field)->join("dx_user as du on du.id=ia.user_id")->where($where)->order('create_time desc')->page($page,$pageSize)->select();
       // $list=M()->table($subQuery.' AS new_table')->where($where)->order('create_time desc')->page($page,$pageSize)->select();
       /* print_r($list);
        print_r(M()->getLastSql());die();*/
        foreach ($list as $k=>$v){
            $list[$k]['integral']=M("integral_rule","wa_")->where(['id'=>$v['integral_id']])->find();
            $list[$k]['reward']=M("integral_reward","wa_")->where(['id'=>$v['use_reward_id']])->find();
            //$list[$k]['address']=M("user_order_address")->where(['id'=>$v['address_id']])->find();
        }
        //$wallet=D('Wallet/Integral');
        //$list=$wallet->integralList($other=['where'=>['user_id'=>136]],$page,$pageSize);
        $count=M()->table($subQuery.' AS new_table')->field("count(id) as count")->where($where)->find();
        $data['page'] = $page;
        $data['pageSize'] = $pageSize;
        $data['list']=$list;
        $data['count']=$count['count'];
        $this->assign('request',$request);
        $this->assign('list',$data);
        $this->display('points/integralList');
    }
    /**
     * @desc 同类积分列表
     *
     */
    public function sameIntegralList(){
        $request=I('get.');
        $page=$request['page']?$request['page']:1;
        $pageSize=$request['pageSize']?$request['pageSize']:C('PAGE_PAGESIZE');
        $where='';
        if(isset($request['order_sn'])&&$request['order_sn']>0){
            $where['order_sn']=$request['order_sn'];
        }
        $where['logic']='and';
        if(isset($request['user_id'])&&!empty($request['user_id'])){
            $where['user_id']=$request['user_id'];
        }
        $list=M("intergral_all","wa_")->where($where)->order('create_time desc')->page($page,$pageSize)->select();
        foreach ($list as $k=>$v){
            $list[$k]['integral']=M("integral_rule","wa_")->where(['id'=>$v['integral_id']])->find();
            $nick_name=M("user")->field('nick_name')->where(['id'=>$v['user_id']])->find();
            $list[$k]['nick_name']=$nick_name['nick_name'];
        }
        $count=M('intergral_all','wa_')->field("count(id) as count")->where($where)->find();
        $data['page'] = $page;
        $data['pageSize'] = $pageSize;
        $data['list']=$list;
        $data['count']=$count['count'];
        $this->assign('request',$request);
        $this->assign('list',$data);
        $this->display('points/sameIntegralList');
    }
    /**积分奖品列表*/
    public  function integralRewardList(){
        $request=I('get.');
        //分页  每页20条
        $request['page']=$page=$request['page']?$request['page']:1;
        $request['pageSize']=$pageSize=$request['pageSize']?$request['pageSize']:20;
        $integralRewardList=M("integral_reward",'wa_')->page($page,$pageSize)->select();
        $count=M('integral_reward','wa_')->field("count(id) as count")->find();
        $request['count']=$count['count'];

        $this->assign('integralRewardList',$integralRewardList);
        $this->assign('request',$request);
        $this->display('points/integralRewardList');
    }
    /**新增--编辑--奖品**/
    public function add_edit_reward(){
        $request=I('get.');
        $request['page']=$page=$request['page']?$request['page']:1;
        $request['pageSize']=$pageSize=$request['pageSize']?$request['pageSize']:20;
        if((isset($request['id']))&&$request['id']){
            $arrId=explode(';',$request['id']);
            $integralRewardLists=M("integral_reward",'wa_')->where(['id'=>['IN',$arrId]])->select();
        }else{
            $integralRewardLists=[];
        }
        $this->assign('request',$request);
        $this->assign('integralRewardLists',$integralRewardLists);
        $this->display('points/add_edit_reward');
    }
    /**
     * @desc 添加积分奖品
     * @param  id   积分奖品ID
     */
    public  function  integralRewardAction(){
        $request=I('post.')['data'];
        $sesstion=session();
        if((isset($request))&&!($request[0]['id'])){
            foreach ($request as $v){
                $v['create_time']=date("Y-m-d H:i:s");
                $v['update_time']=$request['create_time'];
                $v['sys_uid']=$sesstion['adminId'];
                $v['goods_left_num']=$v['goods_num'];
            }
            $field="goods_name,goods_describe,goods_num,goods_left_num,exchange_integral,reward_img,update_time,create_time,note";
            $integral_reward_add=M('integral_reward','wa_')->field($field)->addAll($request);
            if($integral_reward_add){
                die(json_encode(['error'=>0,'msg'=>'提交成功']));
            }else{
                die(json_encode(['error'=>1,'msg'=>'提交失败']));
            }
        }else{
            $field="goods_name,goods_describe,goods_left_num,goods_num,exchange_integral,reward_img,update_time,note";
            foreach ($request as $k=>$v){
                $v['create_time']=date("Y-m-d H:i:s");
                $v['goods_left_num']=$v['goods_num'] - $v['goods_used_num'];
                if($v['goods_left_num']<0){
                    die(json_encode(['error'=>1,'msg'=>'修改奖品数量不能少于已兑换奖品数量']));
                };
                if(empty($arrId)){
                    $arrId=$v['id'];
                }else{
                    $arrId=';'.$v['id'];
                };
                $integral_reward_save=M('integral_reward','wa_')->field($field)->where(['id'=>$v['id']])->save($v);
                if(!$integral_reward_save){
                    die(json_encode(['error'=>1,'msg'=>'状态修改失败']));
                }
                if( count($request) - 1 === $k){
                    die(json_encode(['error'=>0,'msg'=>'状态修改成功','id'=>$arrId]));
                };
            }
        }
    }
    /**
     * 积分项目列表
     *  @param int id 项目id
     * @param int item_status 规则状态 0默认 1启用  11停用
     * @param return  json
     *
     *
     * 删除功能  多传一个delete=1
     */
    public  function integralItemList(){
        if(IS_AJAX){
            $data=I("post.");
            //删去项目
            if(isset($data['delete'])&&$data['delete']){
                $return_delete=M('integral_item','wa_')->where(['id'=>$data['id']])->delete();
                if($return_delete){
                    die(json_encode(['error'=>0,'msg'=>'删去成功']));
                }else{
                    die(json_encode(['error'=>1,'msg'=>'删去失败']));
                }
            }
            //开启-停用项目
            if($data['id']&&$data['item_status']){
                $return_delete=M('integral_item','wa_')->field('item_status')->where(['id'=>$data['id']])->save($data);
                if($return_delete){
                    die(json_encode(['error'=>0,'msg'=>'操作成功']));
                }else{
                    die(json_encode(['error'=>1,'msg'=>'操作失败']));
                }
            }else{
                die(json_encode(['error'=>1,'msg'=>'参数错误']));
            }
        }
        //页面渲染
        $request=I('get.');
        //分页  每页20条
        $request['page']=$page=$request['page']?$request['page']:1;
        $request['pageSize']=$pageSize=$request['pageSize']?$request['pageSize']:20;
        $integralItemList=M('integral_item','wa_')->page($page,$pageSize)->select();
        foreach($integralItemList as $k=>$v){
            $integral_rule=M('integral_rule','wa_')->where(['id'=>$v['integral_rule_id']])->find();
            if($integral_rule){
                $integralItemList[$k]['integral_rule_list']=$integral_rule;
            }else{
                $integralItemList[$k]['integral_rule_list']=[];
            }
        }
        $count=M('integral_item','wa_')->field("count(id) as count")->find();
        $request['count']=$count['count'];

        $this->assign('integralItemList',$integralItemList);
        $this->assign('request',$request);
        $this->display('points/integralItemList');
    }
    /**新增--编辑--项目**/
    public function add_edit_item(){
        $sesstion=session();
        if(IS_AJAX){
            $data=I('post.');
            if(isset($data['id'])&&$data['id']){
                $return_rule=M('integral_rule','wa_')->where(['id'=>$data['integral_rule_id']])->find();
                if($return_rule){
                    $data['update_time']=date('Y-m-d H:i:s');
                    $return_item=M('integral_item','wa_')->field("item_name,item_describe,create_time,update_time,integral_rule_id,item_status")->where(['id'=>$data['id']])->save($data);
                    if($return_item){
                        die(json_encode(['error'=>0,'msg'=>'修改成功']));
                    }else{
                        die(json_encode(['error'=>1,'msg'=>'修改失败']));
                    }
                }else{
                    die(json_encode(['error'=>1,'msg'=>'积分规则不存在']));
                }
            }else{
                $return_rule=true/*M('integral_rule','wa_')->where(['id'=>$data['integral_rule_id']])->find()*/;
                $check_name=$this->checkItem_name($data['item_name']);
                if($check_name['error']>0){
                    die(json_encode(['error'=>1,'msg'=>'项目名称被占用']));
                }
                if($return_rule){
                    $data['sys_uid']=$sesstion['adminId'];
                    $return_item=M('integral_item','wa_')->field("item_name,item_describe,create_time,update_time,integral_rule_id,sys_uid")->add($data);
                    if($return_item){
                        die(json_encode(['error'=>0,'msg'=>'提交成功']));
                    }else{
                        die(json_encode(['error'=>1,'msg'=>'提交失败']));
                    }
                }else{
                    die(json_encode(['error'=>1,'msg'=>'积分规则不存在']));
                }
            }
        };
        //页面渲染
        $request=I('get.');
        $request['page']=$page=$request['page']?$request['page']:1;
        $request['pageSize']=$pageSize=$request['pageSize']?$request['pageSize']:20;
        if((isset($request['id']))&&$request['id']){
            $arrId=explode(';',$request['id']);
            $add_edit_item=M("integral_item",'wa_')->where(['id'=>['IN',$arrId]])->find();
        }else{
            $add_edit_item=[];
        }
        $integral_rule=M('integral_rule','wa_')->where(['type'=>['IN',['111']]])->select();
        $this->assign('request',$request);
        $this->assign('add_edit_item',$add_edit_item);
        $this->assign('integral_rule',$integral_rule);
        $this->display('points/add_edit_item');
    }
    /**启用积分规则，商品更新**/
    public function checkGoods(){

    }
    /**检查积分项目名称是否重复**/
    public function checkItem_name($check_name=''){
        $name_data=$check_name?['item_name'=>$check_name]:I('post.');
        if(isset($name_data['item_name']) && $name_data['item_name']){
            $return_check=M('integral_item','wa_')->where(['item_name'=>$name_data['item_name']])->find();
            if($check_name){
                if($return_check){
                    return ['error'=>1,'msg'=>'项目名称已被占用'];
                }else{
                    return ['error'=>0,'msg'=>'项目名称可以用'];
                }
            }
            if($return_check){
                die(json_encode(['error'=>1,'msg'=>'项目名称已被占用']));
            }else{
                die(json_encode(['error'=>0,'msg'=>'项目名称可以用']));
            }
        }else{
            if($check_name){
                return ['error'=>1,'msg'=>'参数错误1'];
            }
            die(json_encode(['error'=>1,'msg'=>'参数错误']));
        }

    }
    //积分奖品发货
    public function integralProductTransfer(){
        $data=I('post.');
        if(isset($data['reward_status'])&&$data['reward_status']>0){
            $data['update_time']=date('Y-m-d H:i:s');
            $integralTransfer=M('intergral_all','wa_')->field('reward_status,update_time')->where(['id'=>$data['id']])->save($data);
            if($integralTransfer){
                $this->ajaxReturn(['error'=>0,'msg'=>'操作成功']);
            }else{
                $this->ajaxReturn(['error'=>1,'msg'=>'操作失败']);
            };
        }else{
            $this->ajaxReturn(['error'=>1,'msg'=>'参数错误']);
        }

    }
    public function test(){
        $wallet=D('Wallet/Integral');

        $orderSn='1803091182407';
        $list=$wallet->integralOrderThaw($orderSn,40);

        print_r($list);
    }
    //规则自动停用
    public function stopRule(){
        $endtime=date("Y-m-d H:i:s");
        $sql="update wa_integral_rule set status=11 WHERE end_time<$endtime and status=1";
        M()->query($sql);
    }

}