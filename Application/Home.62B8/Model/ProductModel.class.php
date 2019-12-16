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

namespace  Home\Model;

use Think\Model;

class ProductModel extends Model
{
	public function getInfo($p_id)
	{
		$field = 'p.*,d.img,d.pdf,d.path';
		$where = ['p.id'=>$p_id];
		$info = $this->alias('p')->join('__PRODUCT_DETAIL__ as d ON p.id=d.p_id', 'LEFT')->field($field)->where($where)->find();
		//商品价格
		//查询商品默认价格模式id
		$pm_id = $info['default_pm'];
		$result = D('product_price')->where(['id'=>$pm_id, 'p_id'=>$p_id])->find();
		if( empty($result) ){
			$info['price'] = null;
		}else{
			$info['pm_unit'] = $result['pm_unit'];
			$price = [
				[
					'start'=>0,
					'end'=>$result['pm_price_1'],
					'price'=>number_format(($info['price']/0.9)*1.2,4)
				],[
					'start'=>$result['pm_price_1'],
					'end'=>$result['pm_price_2'],
					'price'=>number_format(($info['price']/0.9)*1.1,4)
				],[
					'start'=>$result['pm_price_2'],
					'end'=>$result['pm_price_3'],
					'price'=>number_format($info['price']/0.9,4)
				],[
				'start'=>$result['pm_price_3'],
				'end'=>0,
				'price'=>number_format($info['price'],4)
			]
			];
			$info['price'] = $price;
		}

		$info['attr'] = M('product_attr')->field('id,attr_name,attr_value')->where(['p_id'=>$p_id])->select();
		$info['brand'] = M('brand')->field('brand_name')->where(['id'=>$info['brand_id']])->find();
		$info['pdf'] = '/'.C('PDF_ROOT').$info['path'].'/'.$info['pdf'];
		$info['category'] = M('category')->field('cate_name')->where(['id'=>$info['cate_id']])->find();
		return  $info;
	}

    /**
     *
     * @desc 议价的报备信息添加
     */
    public function productReport($request){
        $session=session();

        if(isset($request['action'])&&$request['action']=='check') {
            if(!$session['adminId']) return ['error'=>1,'msg'=>'登录信息错误'];
            $checkData = [
                'id' => $request['id'],
                'check_status' => $request['check_status'],
                'check_man' =>$session['adminId'],
                'check_note' => $request['check_note'],
            ];
            if($request['sys_uid']) $checkData['sys_uid'] = $request['sys_uid'];
            $result = M('report')->save($checkData);
            if ($result===false) return ['error' => 1, 'msg' => 'false'];
            else return ['error' => 0, 'msg' => 'success'];
        }

        $addData['is_registered']=0;

        if($session['userId']){
            $addData['user_id']=$session['userId'];
            $addData['name']=$session['userInfo']['user_name'];
            $addData['sys_uid']=$session['userInfo']['sys_uid'];
            $addData['is_registered']=1;
        }

        $addData=array_merge($request,$addData);

        $m=M('report');
        $m->startTrans();
        $result=$m->add($addData);
        foreach($request['item'] as $k=>$v){
            $request['item'][$k]['report_id']=$result;
            $request['item'][$k]['compate_price']=$v['compate_price']?:0;
        }
        $result2=M('report_item')->addAll($request['item']);
        if($result&&$result2){
            $m->commit();
            return ['error'=>0,'msg'=>'', 'data'=>$result];
        }else{
            $m->rollback();
            return ['error'=>1,'msg'=>'false'];
        }
    }

    /**
     * @desc 产品搜索统计
     *
     */
    public function productAttrCount($where='',$limit=50){
        $data_return=[];
        //品牌
        $field_brand='count(p.id) as count,br.brand_name as name';
        $brandCount=M('product')->alias('p')
            ->join('left join dx_brand as br on br.id=p.brand_id')
            ->field($field_brand)->where($where)->group('brand_id')
            ->order('count desc')
//            ->limit(0,$limit)
            ->select();
        $data_return['brand']=$brandCount;
        //封装,电压,电流,体积
//        $field_package='p.package,p.current,p.voltage,p.volume,count(p.id) as count';
        $field_package='p.package,count(p.id) as count';
        $packageCount=M('product')->alias('p')
//            ->field($field_package)->where($where)->group('package,current,voltage,volume')
            ->field($field_package)->where($where)->group('package')
            ->order('count desc')
//            ->limit(0,$limit)
            ->select();
//        $key_index=['package','current','volume','voltage'];
        $key_index=['package'];
        $callback=function($val) use(&$data_return,$key_index){
            $callback3=function($val3) use(&$data_return,$val){
                $data_return[$val3.'__'][$val[$val3]]+=$val['count'];
            };
            array_walk($key_index,$callback3);
        };
        array_walk($packageCount,$callback);

        $callback2=function($index2) use(&$data_return){
            $callbackpackage=function($val,$key) use(&$data_return,$index2){
                $data_return[$index2][]=[
                    'name'=>$key,
                    'count'=>$val,
                ];
            };
            array_walk($data_return[$index2.'__'],$callbackpackage);
            unset($data_return[$index2.'__']);
        };
        array_walk($key_index,$callback2);

        //分类
        $field_category='count(p.id) as count,ca.cate_name as name';
        $categoryCount=M('product')->alias('p')
            ->join('left join dx_category as ca on ca.id=p.cate_id')
            ->field($field_category)->where($where)->group('cate_id')
            ->order('count desc')
//            ->limit(0,$limit)
            ->select();
        $data_return['category']=$categoryCount;

        return ['error'=>0,'data'=>$data_return];
    }

    /**
     * @desc 样品列表
     *
     */
    public function customerProductExampleList($where='',$page=0,$pageSize=100){
        $m=new \Admin\Model\ProductbargainModel;
        $field='id,uid,pid,step,max_num,fitemno';
        $where['step']=['gt',0];
        $list=$m->customerProductSampleList($where,$page,$pageSize,'',$field);
        if($list['error']!==0) return ['error'=>1,'msg'=>$list];
        $returnList=[];
        foreach($list['data']['list'] as $k=>$v){
            $returnList[$v['pid']]=$v;
        }
        return ['error'=>0,'data'=>['list'=>$returnList,'count'=>$list['data']['count']]];
    }


    /**
     * @desc 样品结算
     *
     */
    public function customerProductExampleAction($uid,$parentId=0){
        $basket_id=M('basket')->field('basket_id')->where(['user_id'=>$uid])->find();
        if(!$basket_id) return ['error'=>1,'msg'=>'购物车信息错误'];
        $basket_id=$basket_id['basket_id'];
        $sample=M('basket_detail_sample')->field('pid,num')->where(['basket_id'=>$basket_id,'status'=>1])->select();
        if(!$sample) return ['error'=>1,'msg'=>'没有结算的样品'];

        $pid_arr=$basket_info=[];
        foreach($sample as $k=>$v){
            $pid_arr[]=$v['pid'];
            $basket_info[$v['pid']]=$v;
        }

        $whereExample=[
            'uid'=>$parentId?:$uid,
            'pid'=>['in',$pid_arr],
        ];
        $productExample=$this->customerProductExampleList($whereExample);
        if($productExample['error'] !== 0){
            M('basket_detail_sample')->where(['basket_id'=>$basket_id,'pid'=>['in',$pid_arr]])->delete();
            return ['error'=>1,'msg'=>'没有结算的样品'];
        }

        $sample_true=$sample_false=[];
        foreach ($sample as $k => $v) {
            $productExample['data']['list'][$v['pid']]['buy_num']=$basket_info[$v['pid']]['num'];
            if ( isset($productExample['data']['list'][$v['pid']]) && (int)$productExample['data']['list'][$v['pid']]['max_num'] >= (int)$v['num']) {//按样品结算
                $sample_true[]=$v['pid'];
            }else{
                $sample_false[]=$v['pid'];
            }
        }

        if(!empty($sample_false)){
            M('basket_detail_sample')->where(['basket_id'=>$basket_id,'pid'=>['in',$sample_false]])->delete();
            return ['error'=>1,'msg'=>'有失效的样品'];
        }

        return ['error'=>0,'data'=>$sample_true,'listId_arr'=>$productExample['data']['list']];
    }







}