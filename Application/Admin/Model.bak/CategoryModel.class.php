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

namespace  Admin\Model;

use Think\Model;
use Common\Controller\Category;

class CategoryModel extends BaseModel
{
    protected $categoryTonId=1;//顶级分类id

    use Category;

    /*
     * 产品的分类数据
     *
     */
    public function productCategory(){
        $category0=$this->categoryTop();
        if($category0['error']!=0)return $category0;
        $lft=$category0['data']['one']['lft'];
        $rht=$category0['data']['one']['rht'];

        $tree=new \Common\Controller\BaseController();
        $treeArray = $tree->getTreeArray($lft,$rht, true);
        $cate = [];
        foreach($treeArray as $item){
            if( $item['level'] == 1 || $item['level'] == 0 ){
                $item['children'] = $tree->getTreeArray($item['lft'],$item['rht'],true);
                $cate[] = $item;
            }
        }
        return ['error'=>0,'data'=>['category'=>$cate]];
    }

    /*
     * 产品的无限分类数据
     *
     */
    public function productCategoryInfinite($model=''){
        $category0=$this->categoryTop($model);
        if($category0['error']!=0)return $category0;
        $lft=$category0['data']['one']['lft'];
        $rht=$category0['data']['one']['rht'];

        $tree=new \Common\Controller\BaseController();
        $data = $tree->getTreeArray($lft,$rht, true,0,$model);
        if(!$model){
            $product_num=$this->bottomCategoryProductCountList();//计算商品数量
            $num=$product_num['data']['list'];
            $total=$product_num['data']['total'];
        }

        $data_level=[];
        foreach($data as $k=>$v){
            if(!$model) $v['num']=isset($num[$v['id']])?$num[$v['id']]:0;
            $father=array_pop($v['pathRht']);
            $v['pathRht'][]=$father;
            if($v['level']==1){
                $data_level[1][$father][]=$v;
            }else if($v['level']==2){
                $data_level[2][$father][]=$v;
            }else if($v['level']==3){
                $data_level[3][$father][]=$v;
            }else if($v['level']==4){
                $data_level[4][$father][]=$v;
            }
        }

        if($data_level[4]){
            foreach($data_level[3] as $k=>$v){
                foreach($v as $k2=>$v2){
                    $data_level[3][$k][$k2]['children']=$data_level[4][$v2['rht']];
                    if(!$model){
                        foreach($data_level[4][$v2['rht']] as $k3=>$v3){//计算商品数量
                            $data_level[3][$k][$k2]['num']+=$v3['num'];
                        }
                    }
                }
            }
        }
        if($data_level[3]){
            foreach($data_level[2] as $k=>$v){
                foreach($v as $k2=>$v2){
                    $data_level[2][$k][$k2]['children']=array_multisort__($data_level[3][$v2['rht']],'sort');
                    if(!$model){
                        foreach($data_level[3][$v2['rht']] as $k3=>$v3){//计算商品数量
                            $data_level[2][$k][$k2]['num']+=$v3['num'];
                        }
                    }
                }
            }
        }
        if($data_level[2]){
            foreach($data_level[1][$rht] as $k=>$v){
                $data_level[1][$rht][$k]['children']=array_multisort__($data_level[2][$v['rht']],'sort');
                if(!$model) {
                    foreach ($data_level[2][$v['rht']] as $k2 => $v2) {//计算商品数量
                        $data_level[1][$rht][$k]['num'] += $v2['num'];
                    }
                }
            }
        }
        return ['error'=>0,'data'=>['category'=>array_multisort__($data_level[1][$rht],'sort'),'total'=>$total]];
    }

    /*
     *分类顶类
     *
     */
    public function categoryTop($model=''){
        $model=$model?$model:M('category');
        $categoryTop=$model->where(['id'=>$this->categoryTonId])->find();
        if(!$categoryTop) return ['error'=>1,'msg'=>'faild'];
        return ['error'=>0,'data'=>['one'=>$categoryTop]];
    }

    /*
     * 分类添加节点
     * type{
     *          son  添加子类
     *          borther   向右边添加兄弟类
     *      }
     *
     */
    public function addCategory($id,$cate_name,$type='brother',$table='category',$profix='dx_',$cate_name_key='cate_name',$department_level=''){
//        if($type=='son'){
//            $isBottom=$this->categoryIsBottom($id);
//            if($isBottom['error']!=0) return $isBottom;
//        }
        $category= M($table,$profix);
        $table=$profix.$table;
        $lft_rht=$category->field('lft,rht,rht-lft+1 as width')->where(['id'=>$id])->find();
        if(!$lft_rht) return ['error'=>1,'msg'=>'id信息错误'];
        if($type=='son'){
            $sql_lft="update $table set lft=lft+2 where lft>".$lft_rht['rht'];
            $sql_rht="update $table set rht=rht+2 where rht>=".$lft_rht['rht'];
            if(!$department_level) $sql_insert="insert into $table ($cate_name_key,lft,rht) values('".$cate_name."',$lft_rht[rht],$lft_rht[rht]+1)";
            else $sql_insert="insert into $table ($cate_name_key,lft,rht,department_level) values('".$cate_name."',$lft_rht[rht],$lft_rht[rht]+1,$department_level)";
        }else{
            $sql_lft="update $table set lft=lft+2 where lft>".$lft_rht['rht'];
            $sql_rht="update $table set rht=rht+2 where rht>".$lft_rht['rht'];
            if(!$department_level) $sql_insert="insert into $table ($cate_name_key,lft,rht) values('".$cate_name."',$lft_rht[rht]+1,$lft_rht[rht]+2)";
            else $sql_insert="insert into $table ($cate_name_key,lft,rht,department_level) values('".$cate_name."',$lft_rht[rht]+1,$lft_rht[rht]+2,$department_level)";
        }
        $category->startTrans();
        $result_lft=$category->execute($sql_lft);
        $result_rht=$category->execute($sql_rht);
        $result_insert=$category->execute($sql_insert);
        if($result_lft!==false&&$result_rht!==false&&$result_insert){
            $category->commit();
            return ['error'=>0,'msg'=>'success','id'=>D()->getLastInsID()];
        } else {
            $category->rollback();
            return ['error'=>1,'msg'=>'添加失败'];
        }
    }

    /*
     * 分类删除分类
     *
     */
    public function deleteCategory($id,$table='category',$profix='dx_',$table_class='product',$table_class_profix='dx_',$table_class_relation_id='cate_id'){
        $category= M($table,$profix);
        $lft_rht=$category->field('lft,rht,rht-lft+1 as width')->where(['id'=>$id])->find();
        if(!$lft_rht) return ['error'=>1,'msg'=>'id信息错误'];
        if($lft_rht['lft']!=$lft_rht['rht']-1) return ['error'=>1,'msg'=>'有子类不能删除'];
        $tree=new \Common\Controller\BaseController();
        $treeArray = $tree->getTreeArray($lft_rht['lft'],$lft_rht['rht'],false,0,M($table,$profix));
        unset($treeArray['last_level']);
        $cateId_arr=[];
        foreach($treeArray as $k=>$v){ $cateId_arr[]=$v['id']; }
        $one=M($table_class,$table_class_profix)->where([$table_class_relation_id=>['in',$cateId_arr]])->find();
        if($one) return ['error'=>1,'msg'=>'类下面有实例'];

        $table=$profix.$table;

        $sql_delete="delete from $table where id=$id";
        $sql_lft="update $table set lft=lft-$lft_rht[width] where lft>".$lft_rht['rht'];
        $sql_rht="update $table set rht=rht-$lft_rht[width] where rht>".$lft_rht['rht'];

        $category->startTrans();
        $result_delete=$category->execute($sql_delete);
        $result_lft=$category->execute($sql_lft);
        $result_rht=$category->execute($sql_rht);
        if($result_lft!==false&&$result_rht!==false&&$result_delete){
            $category->commit();
            return ['error'=>0,'msg'=>'success'];
        } else {
            $category->rollback();
            return ['error'=>1,'msg'=>'failed'];
        }
    }

    /*
     * 分类id取所有子类id
     *
     */
    public function allLevelCategory($cate_id){
        $category0=$this->categoryTop();
        if($category0['error']!=0)return $category0;
        $lft=$category0['data']['one']['lft'];
        $rht=$category0['data']['one']['rht'];

        $lft_rht=D('category')->field('lft,rht')->where(['id'=>$cate_id])->find();
        $tree=new \Common\Controller\BaseController();
        $data = $tree->getTreeArray($lft_rht['lft'],$lft_rht['rht'], true);
        $data_arr = $tree->getTreeArray($lft,$rht, true);
        $path=[];
        foreach($data_arr as $k=>$v){
            if($v['id']==$cate_id){
                $path=$v;break;
            }
        }
        $return=[];
        foreach($data as $k=>$v){
            $return[]=$v['id'];
        }
        if($return) return ['error'=>0,'data'=>['list'=>$return,'path'=>$path]];
        else return ['error'=>1,'msg'=>'没有数据'];
    }

    /*
     * 分类路径
     *
     */
    public function categoryPath($cate_id=''){
        $category0=$this->categoryTop();
        if($category0['error']!=0)return $category0;
        $lft=$category0['data']['one']['lft'];
        $rht=$category0['data']['one']['rht'];

        $tree=new \Common\Controller\BaseController();
        $treeArray = $tree->getTreeArray($lft,$rht, true);
        $one=[];
        foreach($treeArray as $k=>$v){
           if($v['id']==$cate_id){
               $one=$v;
               break;
           }
        }
        if(!$one) return ['error'=>1,'msg'=>'分类路径错误'];
        else return ['error'=>0,'data'=>['list'=>$one]];
    }


    /*
     * 判断是否是最底级分类
     *
     */
    public function categoryIsBottom($cate_id){
        $category=M('category')->where(['id'=>$cate_id])->find();
        if(!$category) return ['error'=>1,'msg'=>'分类信息错误'];
        $oneCategory=M('category')->where(['lft'=>['gt',$category['lft']],'rht'=>['lt',$category['rht']]])->find();
        if($oneCategory) return ['error'=>1,'msg'=>'不是最底级分类'];
        else return ['error'=>0,'msg'=>'是最底级分类'];
    }


    /*
     * 单个分类信息
     *
     */
    public function oneCategoryInfo($cateId){
        $onecategory=M('category')->where(['id'=>$cateId])->find();
        if(!$onecategory) return ['error'=>1,'msg'=>'分类信息错误'];
        return ['error'=>0,'data'=>['one'=>$onecategory]];
    }

    /*
     * 最底层分类
     *
     */
    public function categoryBottomAll(){
        $list=M('category')->where('lft=rht-1')->select();
        if(!$list) return ['error'=>1,'msg'=>'底层信息错误'];
        return ['error'=>0,'data'=>['list'=>$list]];
    }

    /*
     * 分类名称修改
     *
     */
    public function categoryEdit($request){
        $has=M('category')->where(['id'=>$request['id']])->find();
        if(!$has) return ['error'=>1,'msg'=>'分类信息错误'];
        $data=['id'=>$request['id'],'cate_name'=>$request['name']];
        if(isset($request['sort']))$data['sort']=$request['sort'];
        $result=M('category')->save($data);
        if($result!==false) return ['error'=>0,'msg'=>'success','id'=>$request['id']];
        else return ['error'=>1,'msg'=>'faild'];
    }

    /*
     * 最底层分类下商品数量
     *
     */
    public function bottomCategoryProductCountList($where=''){
        $where=$where?$where:'cate_id in (select id from dx_category where lft=rht-1) and is_online<>100';
        $list=M('product')->field('cate_id,count(*) as num')->where('cate_id in (select id from dx_category where lft=rht-1) and is_online<>100')->group('cate_id')->select();
        $return_data=[];
        $total=0;
        foreach($list as $k=>$v){
            $return_data[$v['cate_id']]=$v['num'];
            $total+=$v['num'];
        }
        return ['error'=>0,'data'=>['list'=>$return_data,'total'=>$total]];
    }











}