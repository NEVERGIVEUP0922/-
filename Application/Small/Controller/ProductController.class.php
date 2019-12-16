<?php
namespace Small\Controller;
use Think\Controller;
class ProductController extends BaseController {
    /*
     *搜索条件范围
     * @param string search 搜索基本条件
     * @param string current 电流,用逗号拼接
     * @param string input 输入电压,用逗号拼接
     * @param string output 输出电压,用逗号拼接
     * @param string brand_id 品牌ID,用逗号拼接
     * @param string cate_id 分类ID,用逗号拼接
     * @param string package 封装,用逗号拼接
     * @return object json
      */
	public function requireSearch(){
		$request=$this->post;
		$where=[];
		//功能条件
		if(isset($request['p_sign'])&&$request['p_sign']){
			$where_p_sign['p_sign']=['like',"%$request[p_sign]%"];
			$where_p_sign['fitemno_access']=['like',"%$request[p_sign]%"];
			$where_p_sign['describe']=['like',"%$request[p_sign]%"];
			$where_p_sign['_logic']='or';
			$where[]=$where_p_sign;
		}
		//        //电流范围
		//
		//        if($request['current']!=''&&$request['current']!=','){
		//            $request['current']=explode(',',$request['current']);
		//            if($request['current'][1]){
		//                if(!$request['current'][0]){
		//                    $request['current'][0]=0;
		//                }
		//                sort($request['current']);
		//                $where['current_start']=['elt',$request['current'][0]*1000];
		//                $where['current_end']=[
		//                    ['egt',$request['current'][1]*1000],
		//                    ['eq',0],
		//                    'or'
		//                ];
		//            }else{
		//                $where['current_start']=[
		//                    ['eq',$request['current'][0]*1000]
		//                ];
		//
		//            }
		//        }
		//        //输入电压范围
		//
		//        if($request['input']!=''&&$request['input']!=','){
		//            $request['input']=explode(',',$request['input']);
		//            if($request['input'][1]){
		//                if(!$request['input'][0]){
		//                    $request['input'][0]=0;
		//                }
		//                sort($request['input']);
		//                $where['voltage_input_start']=['elt',$request['input'][0]*1000];
		//                $where['voltage_input_end']=[
		//                    ['egt',$request['input'][1]*1000],
		//                    ['eq',0],
		//                    'or'
		//                ];
		//            }else{
		//                $where['voltage_input_end']=[
		//                    ['eq',$request['input'][0]*1000]
		//                ];
		//
		//            }
		//        }
		//        //输入电压范围
		//        if($request['output']!=''&&$request['output']!=','){
		//            $request['output']=explode(',',$request['output']);
		//            if($request['output'][1]){
		//                if(!$request['output'][0]){
		//                    $request['output'][0]=0;
		//                }
		//                sort($request['output']);
		//                $where['voltage_output_start']=['elt',$request['output'][0]*1000];
		//                $where['voltage_output_end']=[
		//                    ['egt',$request['output'][1]*1000],
		//                    ['eq',0],
		//                    'or'
		//                ];
		//            }else{
		//                $where['voltage_output_start']=[
		//                    ['eq',$request['output'][0]*1000]
		//                ];
		//
		//            }
		//        }
		if(isset($request['attr_id'])&&$request['attr_id']){
			$typeRes=M("product_attribute_type")->where(['id'=>$request['attr_id']])->find();
			if($typeRes){
				if($typeRes['current']){
					if($typeRes['current_type']==1){
						//体积
						if($request['current']){
							$where[]=[
								'current_start'=>['IN',$request['current']],
							];
						}
					}else{
						//范围值
						if($request['current']!=''&&$request['current']!=','){
							$request['current']=explode(',',$request['current']);
							if($request['current'][1]){
								if(!$request['current'][0]){
									$request['current'][0]=0;
								}
								sort($request['current']);
								$where1['current_start']=['elt',$request['current'][0]*1000];
								$where1['current_end']=[
									['egt',$request['current'][1]*1000],
								];


								$where2['current_start']=['between',[$request['current'][0]*1000,$request['current'][1]*1000]];
								$where2['current_end']=['eq',0];
								$where[]=[$where1,$where2,'_logic'=>'or'];
							}else{
								$where['current_start']=[
									['eq',$request['current'][0]*1000]
								];

							}
						}
					}
				}

				if($typeRes['voltage_input']){
					if($typeRes['voltage_input_type']==1){
						//体积
						if($request['voltage_input']){
							$where[]=[
								'voltage_input_start'=>['IN',$request['voltage_input']],
							];
						}
					}else{
						//输入电压范围
						if($request['voltage_input']!=''&&$request['voltage_input']!=','){
							$request['voltage_input']=explode(',',$request['voltage_input']);
							if($request['voltage_input'][1]){
								if(!$request['voltage_input'][0]){
									$request['voltage_input'][0]=0;
								}
								sort($request['voltage_input']);
								$where1['voltage_input_start']=['elt',$request['voltage_input'][0]*1000];
								$where1['voltage_input_end']=[
									['egt',$request['voltage_input'][1]*1000],
								];
								$where2['voltage_input_start']=['between',[$request['voltage_input'][0]*1000,$request['voltage_input'][1]*1000]];
								$where2['voltage_input_end']=['eq',0];
								$where[]=[$where1,$where2,'_logic'=>'or'];
							}else{
								$where['voltage_input_end']=[
									['eq',$request['voltage_input'][0]*1000]
								];

							}
						}


					}
				}

				if($typeRes['voltage_output']){
					if($typeRes['voltage_output_type']==1){
						//体积
						if($request['voltage_output']){
							$where[]=[
								'voltage_output_start'=>['IN',$request['voltage_output']],
							];
						}
					}else{
						//输入电压范围
						if($request['voltage_output']!=''&&$request['voltage_output']!=','){
							$request['voltage_output']=explode(',',$request['voltage_output']);
							if($request['voltage_output'][1]){
								if(!$request['voltage_output'][0]){
									$request['voltage_output'][0]=0;
								}
								sort($request['voltage_output']);
								$where1['voltage_output_start']=['elt',$request['voltage_output'][0]*1000];
								$where1['voltage_output_end']=[
									['egt',$request['voltage_output'][1]*1000],
								];
								$where2['voltage_output_start']=['between',[$request['voltage_output'][0]*1000,$request['voltage_output'][1]*1000]];
								$where2['voltage_output_end']=['eq',0];
								$where[]=[$where1,$where2,'_logic'=>'or'];
							}else{
								$where['voltage_output_start']=[
									['eq',$request['voltage_output'][0]*1000]
								];

							}
						}


					}
				}

				if($typeRes['volume']){
					if($typeRes['volume_type']==1){
						//体积
						if($request['volume']){
							$where[]=[
								'volume_length'=>['IN',$request['volume']],
							];
						}
					}else{
						//输入电压范围
						if($request['volume']!=''&&$request['volume']!=','){
							$request['output']=explode(',',$request['volume']);
							if($request['volume'][1]){
								if(!$request['volume'][0]){
									$request['volume'][0]=0;
								}
								sort($request['volume']);
								$where1['volume_length']=['elt',$request['volume'][0]*1000];
								$where1['volume_width']=[
									['egt',$request['volume'][1]*1000],
								];
								$where2['volume_length']=['between',[$request['volume'][0]*1000,$request['volume'][1]*1000]];
								$where2['volume_width']=['eq',0];
								$where[]=[$where1,$where2,'_logic'=>'or'];
							}else{
								$where['volume_length']=[
									['eq',$request['volume'][0]*1000]
								];

							}
						}


					}
				}
				if($typeRes['custom']){
					if($typeRes['custom_type']==1){
						//体积
						if($request['custom']){
							$where[]=[
								'custom_start'=>['IN',$request['custom']],
							];
						}
					}else{
						//输入电压范围
						if($request['custom']!=''&&$request['custom']!=','){
							$request['custom']=explode(',',$request['custom']);
							if($request['custom'][1]){
								if(!$request['custom'][0]){
									$request['custom'][0]=0;
								}
								sort($request['custom']);
								$where1['custom_start']=['elt',$request['custom'][0]*1000];
								$where1['custom_end']=[
									['egt',$request['custom'][1]*1000],
								];

								$where2['custom_start']=['between',[$request['custom'][0]*1000,$request['custom'][1]*1000]];
								$where2['custom_end']=['eq',0];
								$where[]=[$where1,$where2,'_logic'=>'or'];
							}else{
								$where['custom_start']=[
									['eq',$request['custom'][0]*1000]
								];

							}
						}


					}
				}
				if($typeRes['custom1']){
					if($typeRes['custom1_type']==1){
						//体积
						if($request['custom1']){
							$where[]=[
								'custom1_start'=>['IN',$request['custom1']],
							];
						}
					}else{
						//输入电压范围
						if($request['custom1']!=''&&$request['custom1']!=','){
							$request['custom1']=explode(',',$request['custom1']);
							if($request['custom1'][1]){
								if(!$request['custom1'][0]){
									$request['custom1'][0]=0;
								}
								sort($request['custom1']);
								$where1['custom1_start']=['elt',$request['custom1'][0]*1000];
								$where1['custom1_end']=[
									['egt',$request['custom1'][1]*1000],
								];

								$where2['custom1_start']=['between',[$request['custom1'][0]*1000,$request['custom1'][1]*1000]];
								$where2['custom1_end']=['eq',0];
								$where[]=[$where1,$where2,'_logic'=>'or'];
							}else{
								$where['custom1_start']=[
									['eq',$request['custom1'][0]*1000]
								];

							}
						}


					}
				}
				if($typeRes['custom2']){
					if($typeRes['custom2_type']==1){
						//体积
						if($request['custom2']){
							$where[]=[
								'custom2_start'=>['IN',$request['custom2']],
							];
						}
					}else{
						//输入电压范围
						if($request['custom2']!=''&&$request['custom2']!=','){
							$request['custom2']=explode(',',$request['custom2']);
							if($request['custom2'][1]){
								if(!$request['custom2'][0]){
									$request['custom2'][0]=0;
								}
								sort($request['custom2']);
								$where1['custom2_start']=['elt',$request['custom2'][0]*1000];
								$where1['custom2_end']=[
									['egt',$request['custom2'][1]*1000],
								];

								$where2['custom2_start']=['between',[$request['custom2'][0]*1000,$request['custom2'][1]*1000]];
								$where2['custom2_end']=['eq',0];
								$where[]=[$where1,$where2,'_logic'=>'or'];
							}else{
								$where['custom2_start']=[
									['eq',$request['custom2'][0]*1000]
								];

							}
						}


					}
				}
			}
		}

		//封装
		if($request['package']){
			$where[]=[
				'dp.package'=>['IN',$request['package']],
			];
		}
		//品牌
		if($request['brand_id']){
			$where[]=[
				'dp.brand_id'=>['IN',$request['brand_id']],
			];
		}
		//分类
		if(isset($request['cate_id'])&&$request['cate_id']){
			$caId=explode(',',$request['cate_id']);
			$caId=array_filter($caId);
			$request['cate_id']=implode(',',$caId);
			$where[]=[
				'cate_id in (select id from dx_category left join (select * from (select lft as lft2,rht as rht2 from dx_category where id IN ('.$request['cate_id'].')) as lef_rht) as lef_rht2 on 1=1 where dx_category.lft>=lef_rht2.lft2 and dx_category.rht<=lef_rht2.rht2)'
			];
		}

		$screen=M('product')->alias("dp")->field("dp.id,dp.brand_id,dxb.brand_name,dp.cate_id,dc.cate_name,dp.package,dpa.current_start,dpa.current_end,dpa.voltage_input_start,dpa.voltage_input_end,dpa.voltage_output_start,dpa.voltage_output_end,dpa.volume_length,dpa.volume_width,dpa.custom_start,dpa.custom_end,dpa.custom1_start,dpa.custom1_end,dpa.custom2_start,dpa.custom2_end,attr_id")->join("dx_brand as dxb on dxb.id=dp.brand_id")->join("dx_category as dc on dc.id=dp.cate_id")->join("dx_product_attribute as dpa on dpa.id=dp.id")->where($where)->select();
		$res['brand']=array_column($screen,'brand_name','brand_id');
		$res['cate']=array_column($screen,'cate_name','cate_id');
		$res['package']=array_column($screen,'package','package');
		$cate_id=array_column($screen,'cate_id','cate_id');
		if(count($cate_id)==1){
			foreach ($cate_id as $v){
				$where2= 'find_in_set('.$v.',cate_id) ';
				$res_attr=M("product_attribute_type")->where($where2)->find();
			}
			if($res_attr)  $res['cid']=$res_attr;
			if(isset($res_attr['current'])&&$res_attr['current']){
				if($res_attr['current_type']==1){
					$res['current']=array_column($screen,'current_start','current_start');
					foreach ($res['current'] as &$v){
						$v=$v/1000;
					}
				}else{
					$cArr=array_merge(array_column($screen,'current_start'),array_column($screen,'current_end'));
					$res['current']=(min($cArr)/1000).','.(max($cArr)/1000);
				}
			}
			if(isset($res_attr['voltage_input'])&&$res_attr['voltage_input']){
				if($res_attr['voltage_input_type']==1){
					$res['voltage_input']=array_column($screen,'voltage_input_start','voltage_input_start');
					foreach ($res['voltage_input'] as &$v){
						$v=$v/1000;
					}
				}else{
					$iArr=array_merge(array_column($screen,'voltage_input_start'),array_column($screen,'voltage_input_end'));
					$res['voltage_input']=(min($iArr)/1000).','.(max($iArr)/1000);
				}
			}
			if(isset($res_attr['voltage_output'])&&$res_attr['voltage_output']){
				if($res_attr['voltage_output_type']==1){
					$res['voltage_output']=array_column($screen,'voltage_output_start','voltage_output_start');
					foreach ($res['voltage_output'] as &$v){
						$v=$v/1000;
					}
				}else{
					$oArr=array_merge(array_column($screen,'voltage_output_start'),array_column($screen,'voltage_output_end'));
					$res['voltage_output']=(min($oArr)/1000).','.(max($oArr)/1000);
				}
			}
			if(isset($res_attr['volume'])&&$res_attr['volume']){
				if($res_attr['volume_type']==1){
					$res['volume']=array_column($screen,'volume_length','volume_length');
					foreach ($res['volume'] as &$v){
						$v=$v/1000;
					}
				}else{
					$vArr=array_merge(array_column($screen,'volume_length'),array_column($screen,'volume_width'));
					$res['volume']=(min($oArr)/1000).','.(max($vArr)/1000);
				}
			}
			if(isset($res_attr['custom'])&&$res_attr['custom']){
				if($res_attr['custom_type']==1){
					$res['custom']=array_column($screen,'custom_start','custom_start');
					foreach ($res['custom'] as &$v){
						$v=$v/1000;
					}
				}else{
					$cuArr=array_merge(array_column($screen,'custom_start'),array_column($screen,'custom_end'));
					$res['custom']=(min($cuArr)/1000).','.(max($cuArr)/1000);
				}
			}
			if(isset($res_attr['custom1'])&&$res_attr['custom1']){
				if($res_attr['custom1_type']==1){
					$res['custom1']=array_column($screen,'custom1_start','custom1_start');
					foreach ($res['custom1'] as &$v){
						$v=$v/1000;
					}
				}else{
					$cuArr=array_merge(array_column($screen,'custom1_start'),array_column($screen,'custom1_end'));
					$res['custom1']=(min($cuArr)/1000).','.(max($cuArr)/1000);
				}
			}
			if(isset($res_attr['custom2'])&&$res_attr['custom2']){
				if($res_attr['custom2_type']==1){
					$res['custom2']=array_column($screen,'custom2_start','custom2_start');
					foreach ($res['custom2'] as &$v){
						$v=$v/1000;
					}
				}else{
					$cuArr=array_merge(array_column($screen,'custom2_start'),array_column($screen,'custom2_end'));
					$res['custom2']=(min($cuArr)/1000).','.(max($cuArr)/1000);
				}
			}
			//第二个0代表无穷大

		}
		return $res;
		//die(json_encode($res));
	}
    /**
     * @desc 商品列表
     *
     */
    public function productList(){
        $request=$this->post;
        $where['is_online']=1;
        $order='';
        $field='';
        $show_data=in_array($request['show_data'],['productDetail','productList'])?$request['show_data']:'productList';//显示的数据类型
        if(isset($request['sell_num'])&&(int)$request['sell_num']===21) $order='sell_num desc';
        if(isset($request['brand_id'])&&$request['brand_id']) $where['brand_id']=['IN',$request['brand_id']];
        if(isset($request['pid'])&&$request['pid']) $where['id']=$request['pid'];
        if(isset($request['is_search_top'])&&$request['is_search_top']) $where['is_search_top']=$request['is_search_top'];//推荐商品
        if(isset($request['show_site'])&&$request['show_site']) $where['show_site']=$request['show_site'];//新品，热销，特卖
        if(isset($request['cate_id'])&&$request['cate_id']){
            $caId=explode(',',$request['cate_id']);
            $caId=array_filter($caId);
            $request['cate_id']=implode(',',$caId);
            $where[]=[
                'cate_id in (select id from dx_category left join (select * from (select lft as lft2,rht as rht2 from dx_category where id IN ('.$request['cate_id'].')) as lef_rht) as lef_rht2 on 1=1 where dx_category.lft>=lef_rht2.lft2 and dx_category.rht<=lef_rht2.rht2)'
            ];
        }
        if (isset($request['package'])&&$request['package']){
            $where['package']=['IN',$request['package']];
        }
        if(isset($request['p_sign'])&&$request['p_sign']){
            $where_p_sign['p_sign']=['like',"%$request[p_sign]%"];
            $where_p_sign['fitemno_access']=['like',"%$request[p_sign]%"];
            $where_p_sign['describe']=['like',"%$request[p_sign]%"];
            $where_p_sign['_logic']='or';
            $where[]=$where_p_sign;
        }

	
		$whereAttr=[];
//      -----------------------------------------------
		if(isset($request['attr_id'])&&$request['attr_id']){
			$typeRes=M("product_attribute_type")->where(['id'=>$request['attr_id']])->find();
			if($typeRes){
				if($typeRes['current']){
					if($typeRes['current_type']==1){
						//体积
						if($request['current']){
							$whereAttr[]=[
								'current_start'=>['IN',$request['current']],
							];
						}
					}else{
						//范围值
						if($request['current']!=''&&$request['current']!=','){
							$request['current']=explode(',',$request['current']);
							if($request['current'][1]){
								if(!$request['current'][0]){
									$request['current'][0]=0;
								}
								sort($request['current']);
								$whereAttr1['current_start']=['elt',$request['current'][0]*1000];
								$whereAttr1['current_end']=[
									['egt',$request['current'][1]*1000],
								];

								$whereAttr2['current_start']=['between',[$request['current'][0]*1000,$request['current'][1]*1000]];
								$whereAttr2['current_end']=['eq',0];
								$whereAttr[]=[$whereAttr1,$whereAttr2,'_logic'=>'or'];
							}else{
								$whereAttr['current_start']=[
									['eq',$request['current'][0]*1000]
								];

							}
						}
					}
				}

				if($typeRes['voltage_input']){
					if($typeRes['voltage_input_type']==1){
						//体积
						if($request['voltage_input']){
							$whereAttr[]=[
								'voltage_input_start'=>['IN',$request['voltage_input']],
							];
						}
					}else{
						//输入电压范围
						if($request['voltage_input']!=''&&$request['voltage_input']!=','){
							$request['voltage_input']=explode(',',$request['voltage_input']);
							if($request['voltage_input'][1]){
								if(!$request['voltage_input'][0]){
									$request['voltage_input'][0]=0;
								}
								sort($request['voltage_input']);
								$whereAttr1['voltage_input_start']=['elt',$request['voltage_input'][0]*1000];
								$whereAttr1['voltage_input_end']=[
									['egt',$request['voltage_input'][1]*1000],
								];
								$whereAttr2['voltage_input_start']=['between',[$request['voltage_input'][0]*1000,$request['voltage_input'][1]*1000]];
								$whereAttr2['voltage_input_end']=['eq',0];
								$whereAttr[]=[$whereAttr1,$whereAttr2,'_logic'=>'or'];
							}else{
								$whereAttr['voltage_input_end']=[
									['eq',$request['voltage_input'][0]*1000]
								];

							}
						}


					}
				}

				if($typeRes['voltage_output']){
					if($typeRes['voltage_output_type']==1){
						//体积
						if($request['voltage_output']){
							$whereAttr[]=[
								'voltage_output_start'=>['IN',$request['voltage_output']],
							];
						}
					}else{
						//输入电压范围
						if($request['voltage_output']!=''&&$request['voltage_output']!=','){
							$request['voltage_output']=explode(',',$request['voltage_output']);
							if($request['voltage_output'][1]){
								if(!$request['voltage_output'][0]){
									$request['voltage_output'][0]=0;
								}
								sort($request['voltage_output']);
								$whereAttr1['voltage_output_start']=['elt',$request['voltage_output'][0]*1000];
								$whereAttr1['voltage_output_end']=[
									['egt',$request['voltage_output'][1]*1000],
								];
								$whereAttr2['voltage_output_start']=['between',[$request['voltage_output'][0]*1000,$request['voltage_output'][1]*1000]];
								$whereAttr2['voltage_output_end']=['eq',0];
								$whereAttr[]=[$whereAttr1,$whereAttr2,'_logic'=>'or'];
							}else{
								$whereAttr['voltage_output_start']=[
									['eq',$request['voltage_output'][0]*1000]
								];

							}
						}


					}
				}

				if($typeRes['volume']){
					if($typeRes['volume_type']==1){
						//体积
						if($request['volume']){
							$whereAttr[]=[
								'volume_length'=>['IN',$request['volume']],
							];
						}
					}else{
						//输入电压范围
						if($request['volume']!=''&&$request['volume']!=','){
							$request['output']=explode(',',$request['volume']);
							if($request['volume'][1]){
								if(!$request['volume'][0]){
									$request['volume'][0]=0;
								}
								sort($request['volume']);
								$whereAttr1['volume_length']=['elt',$request['volume'][0]*1000];
								$whereAttr1['volume_width']=[
									['egt',$request['volume'][1]*1000],
								];
								$whereAttr2['volume_length']=['between',[$request['volume'][0]*1000,$request['volume'][1]*1000]];
								$whereAttr2['volume_width']=['eq',0];
								$whereAttr[]=[$whereAttr1,$whereAttr2,'_logic'=>'or'];
							}else{
								$whereAttr['volume_length']=[
									['eq',$request['volume'][0]*1000]
								];

							}
						}


					}
				}

				if($typeRes['custom']){
					if($typeRes['custom_type']==1){
						//体积
						if($request['custom']){
							$whereAttr[]=[
								'custom_start'=>['IN',$request['custom']],
							];
						}
					}else{
						//输入电压范围
						if($request['custom']!=''&&$request['custom']!=','){
							$request['custom']=explode(',',$request['custom']);
							if($request['custom'][1]){
								if(!$request['custom'][0]){
									$request['custom'][0]=0;
								}
								sort($request['custom']);
								$whereAttr1['custom_start']=['elt',$request['custom'][0]*1000];
								$whereAttr1['custom_end']=[
									['egt',$request['custom'][1]*1000],
								];

								$whereAttr2['custom_start']=['between',[$request['custom'][0]*1000,$request['custom'][1]*1000]];
								$whereAttr2['custom_end']=['eq',0];
								$whereAttr[]=[$whereAttr1,$whereAttr2,'_logic'=>'or'];
							}else{
								$whereAttr['custom_start']=[
									['eq',$request['custom'][0]*1000]
								];

							}
						}


					}
				}

				if($typeRes['custom1']){
					if($typeRes['custom1_type']==1){
						//体积
						if($request['custom1']){
							$whereAttr[]=[
								'custom1_start'=>['IN',$request['custom1']],
							];
						}
					}else{
						//输入电压范围
						if($request['custom1']!=''&&$request['custom1']!=','){
							$request['custom1']=explode(',',$request['custom1']);
							if($request['custom1'][1]){
								if(!$request['custom1'][0]){
									$request['custom1'][0]=0;
								}
								sort($request['custom1']);
								$whereAttr1['custom1_start']=['elt',$request['custom1'][0]*1000];
								$whereAttr1['custom1_end']=[
									['egt',$request['custom1'][1]*1000],
								];

								$whereAttr2['custom1_start']=['between',[$request['custom1'][0]*1000,$request['custom1'][1]*1000]];
								$whereAttr2['custom1_end']=['eq',0];
								$whereAttr[]=[$whereAttr1,$whereAttr2,'_logic'=>'or'];
							}else{
								$whereAttr['custom1_start']=[
									['eq',$request['custom1'][0]*1000]
								];

							}
						}


					}
				}

				if($typeRes['custom2']){
					if($typeRes['custom2_type']==1){
						//体积
						if($request['custom2']){
							$whereAttr[]=[
								'custom2_start'=>['IN',$request['custom2']],
							];
						}
					}else{
						//输入电压范围
						if($request['custom2']!=''&&$request['custom2']!=','){
							$request['custom2']=explode(',',$request['custom2']);
							if($request['custom2'][1]){
								if(!$request['custom2'][0]){
									$request['custom2'][0]=0;
								}
								sort($request['custom2']);
								$whereAttr1['custom2_start']=['elt',$request['custom2'][0]*1000];
								$whereAttr1['custom2_end']=[
									['egt',$request['custom2'][1]*1000],
								];

								$whereAttr2['custom2_start']=['between',[$request['custom2'][0]*1000,$request['custom2'][1]*1000]];
								$whereAttr2['custom2_end']=['eq',0];
								$whereAttr[]=[$whereAttr1,$whereAttr2,'_logic'=>'or'];
							}else{
								$whereAttr['custom2_start']=[
									['eq',$request['custom2'][0]*1000]
								];

							}
						}


					}
				}
			}
		}
//		-----------------------------------------------
		if($whereAttr){
		    $pda=M('product_attribute')->field('id')->where($whereAttr)->select();
		    if($pda){
                $pda=implode(',',array_column($pda,'id'));
            }
			$where[]=[
				"id in (".$pda.")"
			];
		}
        //排序
        if(isset($request['sort'])&&$request['sort']){
            $field='brand_id,p_sign,pack_unit,parameter,sell_num,fitemno,id,package,describe_image,describe';
            $price_field=',(select unit_price from (select p_id,unit_price from dx_product_price where line = 0 group by p_id order by unit_price asc) as p_price where p_price.p_id=dx_product.id) as min_price';
            $store_field=',(select store from (select store,ftem from erp_product) as ep  where ep.ftem = dx_product.fitemno) as store';
            $order='';
            if(strpos($request['sort'],',')){//综合排序
                $request['sort']=str_replace('12','asc',$request['sort']);
                $request['sort']=str_replace('21','desc',$request['sort']);
                if(strpos($request['sort'],'price')!==false){
                    $field.=$price_field;
                }
                if(strpos($request['sort'],'store')!==false){
                    $field.=$store_field;
                }
                $order=$request['sort'];
            }else{
                switch ($request['sort']){
                    case 'sell_num 21'://销量排序
                        $order='sell_num desc';
                        break;
                    case 'sell_num 12'://销量排序
                        $order='sell_num asc';
                        break;
                    case 'price 12'://价格排序
                        $field.=$price_field;
                        $order='min_price asc';
                        break;
                    case 'price 21'://价格排序
                        $field.=$price_field;
                        $order='min_price desc';
                        break;
                    case 'store 12'://库存排序
                        $field.=$store_field;
                        $order='store asc';
                        break;
                    case 'store 21'://库存排序
                        $field.=$store_field;
                        $order='store desc';
                        break;
                }
            }
        }

        $userInfo=[];
        $userInfo=$this->getUserInfo('',false);

        $page=$request['page']?:1;
        $pageSize=$request['pageSize']?:C('PAGE_PAGESIZE');
        $limit=($page-1)*$pageSize.','.$pageSize;
        //详情页-推荐
        if(isset($request['cate_id'])&&!isset($request['page'])){
                $order="RAND()";
        }

        $list=D('Product','Logic')->productList($where,$limit,$field,$order,$show_data,$userInfo['id']?:0);
		$rsearch=$this->requireSearch();
		if($rsearch['cid']){
			$xcx_cid = $rsearch['cid'];
			unset($rsearch['cid']);
		}
		$list['data']['rsearch']=$rsearch;
        $list['data']['page']=$page;
        $list['data']['pageSize']=$pageSize;
        //随机排序
        if((isset($request['show_site'])&&$request['show_site']&&!isset($request['sort']))||(isset($request['sell_num'])&&$request['sell_num']&&!isset($request['sort']))) {
            shuffle($list['data']['list']);
        }

        //详情页-推荐
        if(isset($request['cate_id'])&&!isset($request['page'])){

            $listNum=count($list['data']['list']);
            if($remain=10-$listNum){
                if(isset($request['cate_id'])&&$request['cate_id']){
                    $remainWhere[]=[
                        'cate_id in (select id from dx_category left join (select * from (select lft as lft2,rht as rht2 from dx_category where id NOT IN ('.$request['cate_id'].')) as lef_rht) as lef_rht2 on 1=1 where dx_category.lft>=lef_rht2.lft2 and dx_category.rht<=lef_rht2.rht2)'
                    ];
                }
                $remainLimit=$remain;
                $remainList=D('Product','Logic')->productList($remainWhere,$remainLimit,$field,$order,$show_data,$userInfo['id']?:0);
                $list['data']['list']=array_merge($list['data']['list'],$remainList['data']['list']);
            }else{
                $list['data']['list'];
            }
        }

        $this->return_data['cid'] = $xcx_cid;
        $this->return_data['data']=$list['data'];
        $this->return_data['statusCode']=$list['error'];
        $this->return_data['msg']=$list['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 搜索历史
     *
     */
    public function searchHistory(){
        $request=$this->post;

        $userInfo=$this->getUserInfo();

        $list=D('search','Logic')->searchHistory($request);

        $this->return_data['data']=$list['data'];
        $this->return_data['statusCode']=$list['error'];
        $this->return_data['msg']=$list['msg'];
        $this->ajaxReturn($this->return_data);
    }

}