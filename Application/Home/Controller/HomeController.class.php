<?php

// +----------------------------------------------------------------------
// | FileName:   HomeController.class.php
// +----------------------------------------------------------------------
// | Dscription:   前台基类控制器
// +----------------------------------------------------------------------
// | Date:  2017/7/31 13:32
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------

namespace  Home\Controller;

use Common\Controller\BaseController;
use EES\System\Redis;
use THink\Controller;
use Common\Controller\Category;
use Common\Controller\Address;

class HomeController extends BaseController
{
	use Category;
	use Address;

	public $ssid;
    public $searchHistory;

	protected function _initialize()
	{
		parent::_initialize();
		//前端页面管理
		if (C('WEB_SITE_CLOSE')) {
			exit('站点已经关闭，请稍后访问~');
		}

		if(session('userType')==20){//子帐号
            $login=M('user_son')->where(['user_id'=>session('userId'),'is_delete'=>0])->find();
            if(!$login){//子帐号被禁用
                $id = session('userId');
                $key = 'ssid_'.$id;
                S($key,null);
                session('userId', null);
                session('userInfo', null);
                session('userType', null);
                session('userIp', null);
                session('lastAccountInfo', null);
                session('user_account_status', null);
                if($_COOKIE['userId']) unset($_COOKIE['userId']);
                if($_COOKIE['userInfo']) unset($_COOKIE['userInfo']);
                if($_COOKIE['userType']) unset($_COOKIE['userType']);
                cookie('_uba_create_id','1');
            }
        }

        $method_name=MODULE_NAME;
        $action_name=CONTROLLER_NAME;
        $model_name=ACTION_NAME;

        if(!(strtolower($method_name)=='home'&&strtolower($action_name)=='account'&&strtolower($model_name)=='login')){
            $id=session('userId');
            $key=md5(session_id().'life'.$id);
            if(S($key)){
                S($key,1,C('ON_LINE_LIVE_TIME'));
            }else{
                session('userId', null);
                session('userInfo', null);
                session('userType',null);
                session('lastAccountInfo',null);
                session('user_account_status',null);
                session('userIp',null);
            }
        }

        //热门搜素
        $this->searchHistory=D('Home/SearchIndex','Design');
        $this->searchHistory->setSearch(D('Home/SearchProduct','Design'))->indexSearch('');
        $searchHistory=$this->searchHistory->result;
        $key_to_key2=[
            'dx_searchhot_brand'=>'hot_brand',
            'dx_searchhot_pSign'=>'hot_pSign',
            'dx_searchhot_function'=>'hot_function',
        ];
        $hotList=[];
        foreach($searchHistory as $k=>$v){
            foreach($v as $k2=>$v2){
                $hotList[$key_to_key2[$k]][$k2]['show_name']=$v2['sql_index'];
            }
        }

//		$hotList=$this->hotBrand();//热门搜素

//		dump(session());
//		dump(S($this->ssid.'open'));
        $basket_goods_num=$this->basketGoodsNum();//购物车商品数量
        $this->assign('basket_goods_num', $basket_goods_num);

		$this->assign('hotList', $hotList);
		$cate = $this->categoryNav();
		$cate_num = count($cate);
		$this->assign('cate', $cate);
		$this->assign('cate_num', $cate_num);
        $qqList=(new \Admin\Model\UserModel())->qqList(session('userId'),19);
        $this->assign('qqList', $qqList['data']);
        $this->assign('version_num', C('VERSION_NUM'));
	}

	/*
	 * 添加用户信息到session 执行登录操作
	 */
	protected function addUserToSession($id)
	{
		//登录用户 将用户session写入缓存
		$id = intval($id);
        session('userId', null);

		$user = M('user');
		session('userId', null);
		session('userInfo', null);
        session('userType',null);
        session('lastAccountInfo',null);
        session('user_account_status',null);
        session('userIp',null);
		$data['last_ip'] = get_client_ip();
		$user->where('id=' . $id)->setField($data);
		$res = $user->field('user_type')->where(['id=' . $id])->find();
		$type = intval($res['user_type']);
		
		$key = 'ssid_'.$id;
		//防止同时登录
		if( $type == 1 ){
			if(!session('adminId')) S($key,null);
			S($key,$this->ssid);
		}
		if ($type == 0) {
			$field = 'u.sys_uid,u.fcustno,u.user_mobile, u.user_name, u.user_type, u.user_email, u.email_status, u.last_time, 
			u.last_ip , u.create_time, u.nick_name,u.avator';
			$where = ['u.id' => $id];
			$userInfo = $user->alias('u')->field($field)->where($where)
				->find();
		} elseif ($type == 1) {
			$field = 'u.sys_uid,u.fcustno,u.nick_name,u.user_mobile, u.user_name, u.user_type, u.user_email, u.email_status, u.last_time, 
			u.last_ip , u.create_time,u.avator, n.sex';
			$where = ['u.id' => $id, 'u.user_type' => 1];
			$userInfo = $user->alias('u')->join('__USER_NORMAL__ as n On u.id=n.user_id', 'LEFT')->field($field)->where($where)
				->find();
		} elseif ($type == 2 || $type==20) {
			$field = 'u.sys_uid,u.fcustno, u.id,u.nick_name ,u.user_mobile, u.user_name, u.user_type,u.isedit_user_name, u.user_email, u.email_status, u.last_time,
			 u.last_ip, u.create_time, u.avator, c.company_name, c.company_area, c.company_address, c.company_people_num, 
				c.company_user_name, c.company_user_sector, c.company_phone_num, c.check_status';
			$where = ['u.id' => $id, 'u.user_type' => $type];
			$userInfo = $user->alias('u')->join('__USER_COMPANY__ as c On u.id=c.user_id', 'LEFT')->field($field)->where($where)
				->find();
		}
		//查询用户open信息
		$open = M('user_oauth');
		$openInfo = $open->field('type,open_id,status')->where(['user_id' => $id])->select();
		foreach ($openInfo as $k => $v) {
			$openInfo[$v['type']] = [
				'open_id' => $v['open_id'],
				'status' => $v['status'],
			];
			unset($openInfo[$k]);
		}
		$userInfo['open'] = $openInfo;
		session('userInfo', $userInfo);
		session('userId', $id);
		//S(md5(session_id().'life'.$id),1,3600);//登录状态的生存时间
        S(md5(session_id().'life'.$id),1,86400);//登录状态的生存时间
		session('userType', $type);
		//用户ip
		$ip = get_client_ip();
		session('userIp',$ip);
		R('Home/Basket/loginBasket', [$id]);//同步购物车
        $lastAccountInfo = R('Home/Order/lastAccountInfo');
		//session('userAuthSign', data_auth_sign($id)); //用户签名
        session('lastAccountInfo',$lastAccountInfo);

        if($userInfo['user_type']==20){
            $user_son=D('user_son')->where(['user_id'=>$id])->find();
            $id=$user_son['p_id'];
        }
        R('Home/Order/userAccount',[$id]);
        $user_account=D('user_account')->where(['user_id'=>$id])->find();
        $user_account_status=isset($user_account['status'])?$user_account['status']:'';
        session('user_account_status',$user_account_status);
		unset($_SESSION['userInfo']['id']);
		unset($_SESSION['userInfo']['user_pass']);
		unset($_SESSION['userInfo']['user_id']);
		//清除缓存中的错误次数
		$key = 'loginFalse:' . $userInfo['user_name'];
		S($key, 0);
		if($type==20){
			$r=M()->query("select du.fcustno from dx_user du,dx_user_son dus where du.id=dus.p_id and dus.user_id={$userInfo['id']}");
			if($r && $r[0]['fcustno']){
				$userInfo['fcustno']=$r[0]['fcustno'];
			}
		}
		//主动更新客户账期信息
        if( !empty( $userInfo['fcustno'] ) ){
            $key = 'syncCustCreditList';
            $redis = Redis::getInstance();
           $order= $redis->sAdd( $key, $userInfo['fcustno'] );
        
        }
	}


	protected function isLogin()
	{
		if (empty($_SESSION['userId']) && !(IS_AJAX)) {
			$this->redirect('Account/login');
		} elseif (empty($_SESSION['user_id']) && IS_AJAX) {
			$this->ajaxReturnStatus(1000, ' 您还未登录!请登录');
		}
	}

	/*
	 * 分类导航
	 *
	 */
	public function categoryNav()
	{
        $m=new \Admin\Model\CategoryModel;
        $list=$m->productCategoryInfinite();
        if($list['error']!=0) die(json_encode($list));
        return $list['data']['category'];
	}
	/*
	 * 获取前台展示所有分类
	 *
	 * return
	 */
	public function all()
	{
		$treeArray = $this->getTreeArray(2,345);
		return $treeArray;
	}
	
	/*
	 * 用户浏览商品加入足迹
	 *
	 * @param int $id [商品id]
	 * @param int $user_id  [用户id]
	 */
	protected function addHistory($id,$user_id)
	{
		//$key = 'history_'.$user_id;
		//$hisArr = json_decode(session($key),true);
		$hisArr = M('user_history')->where(['user_id'=>$user_id])->find();
		$max = 50; //只保留最近的30条记录
		if( empty($hisArr) ){
			$add['user_id'] = $user_id;
			$add['his'] = json_encode([$id]);
			D('user_history')->add($add);
		}else{
			$hisArr = json_decode( $hisArr['his'], true );
			$count = count($hisArr);
			//数量超过 那么弹出最后一个商品id;
			if( $count >= $max ){
				array_pop($hisArr);
			}
			//若已存在该商品id
			$k = array_search($id,$hisArr);
			if( $k >= 0 && $k !== false  ){
				unset($hisArr[$k]);
			}
			//入栈
			array_unshift($hisArr, $id);
			//写入session
			$hisArr = json_encode($hisArr);
			//session($key, json_encode($hisArr));
			M('user_history')->where(['user_id'=>$user_id])->setField('his',$hisArr);
		}

	}

    /*
     * 商品列表
     *
     */
    public function productList($where='',$page='',$pageSize='',$level=true,$order=''){
        $where['is_online']=1;
        $product=new \Admin\Model\ProductModel;
        $field='unit,pack_unit,min,min_open';
        $field.=',parameter,package,batch,is_earnest,earnest_scale,is_delivery';
        $field.=',delivery,cover_image,fitemno_access,is_tax,discount_num,describe_image';
        $field.=',note,note_isShow,describe,is_inquiry_table,id,p_sign';
        $field.=',cate_id,brand_id,unit,tax,fitemno,sell_num,show_site';

        $productResult=$product->productList($where,$page,$pageSize,$level,$order,$field,'front');
        return $productResult;
    }
	/*
	  * 功能搜索商品列表
	  *
	  */
	public function productHotList($where='',$page='',$pageSize='',$level=true,$order=''){
		$where['is_online']=1;
		$product=new \Admin\Model\ProductModel;
		$field='unit,pack_unit,min,min_open';
		$field.=',parameter,package,batch,is_earnest,earnest_scale,is_delivery';
		$field.=',delivery,cover_image,fitemno_access,is_tax,discount_num,describe_image';
		$field.=',note,note_isShow,describe,is_inquiry_table,dp.id,p_sign';
		$field.=',cate_id,brand_id,unit,tax,fitemno,sell_num,show_site';

		$productResult=$product->productHotList($where,$page,$pageSize,$level,$order,$field,'front');
		return $productResult;
	}

    /*
     * 商品价格
     *
     */
    public function productPrice($productId_arr){
        $product=new \Admin\Model\ProductModel;
        $price=$product->productPrice($productId_arr);
        return $price;
    }

    /*
     * erp商品库存
     *
     */
    public function erpProduct($FItemNo){
        $product=new \Admin\Model\ErpproductModel();
        $productResult=$product->erpProduct($FItemNo);
        return $productResult;
    }

    /*
     * erp商品库存表信息
     *
     */
    public function erpProductList($where='',$page='',$pageSize='',$order=''){
        $product=new \Admin\Model\ErpproductModel();
        $productResult=$product->erpProductList($where,$page,$pageSize,$order);
        return $productResult;
    }

    /*
     * 顾客商品优惠价格
     *
     */
    public function customerProductPrice($cusId,$productList){
        $product=new \Admin\Model\ProductModel;
        $price=$product->customerProductPrice($cusId,$productList);
        return $price;
    }

    /*
     *订单列表
     *
     */
    public function orderList_admin($where='',$page='',$pageSize='',$order='',$slave=true){
        $m=new \Admin\Model\OrderModel;
        $orderList=$m->orderList($where,$page,$pageSize,$order,$slave);
        return $orderList;
    }

    /*
     *获取订单编号
     *
     */
    public function orderSn(){
        $m=new \Admin\Model\OrderModel;
        $list=$m->orderSn();
        return $list;
//        return ['error'=>0,'data'=>['one'=>uniqid()]];
    }

	/*
	 * 搜索下面的热门品牌
	 *
	 */
	public function hotBrand(){
        $hotList=S('hotList');
        if($hotList)return $hotList;
		$hot_brand=M('brand')->field('brand_name as show_name')->order('rand()')->limit(0,10)->select();
        $hot_pSign=M('product')->field('p_sign as show_name')->order('rand()')->limit(0,10)->select();
        $hot_function=M('product')->field('p_sign as show_name')->order('rand()')->limit(0,10)->select();
        $return_data=['hot_brand'=>$hot_brand,'hot_pSign'=>$hot_pSign,'hot_function'=>$hot_function];
		S('hotList',$return_data,86400);
		return $return_data;
	}
	
	/*
	 * 最新商品列表
	 *
	 */
	protected function newGoods()
	{
		$pro = D('product');
		$field = 'p.id,p.p_sign,p.name,p.sell_num,p.price,c.cate_name, b.brand_name, d.img, d.describe,d.replace_desc,d.use_area';
		$join = '__CATEGORY__ as c On c.id = p.cate_id';
		$join2 = '__BRAND__ as b On b.id = p.brand_id';
		$join3 = '__PRODUCT_DETAIL__ as d On d.p_id = p.id';
		$where = 'p.is_del = 0';
		$data = $pro->alias('p')->field($field)
			->join($join, 'LEFT')->join($join2, 'LEFT')->join($join3, 'LEFT')
			->where($where)
			->order('p.id DESC')->limit(4)->select();
		return $data;
	}
	
	/*
	 * 随机品牌
	 *
	 */
	protected function brandRand()
	{
		$brand=D('brand')->order('rand()')->limit(6)->select();
		return $brand;
	}
	
	/*
         * 购物车数目
         */
	public function basketGoodsNum(){
		$user_id=session('userId')?session('userId'):'';//是否有user_id为判断是否登录

		if($user_id) $basket_id=$this->loginBasketId($user_id);//本次购物车id
		else $basket_id=$this->notLoginBasketId();//未登录购物车id
		$goods_num=D('basket_detail')->field('count(id) as num')->where(['basket_id'=>$basket_id])->find();
		return $goods_num['num'];
	}

	/*
         * 用户的basket_id
         */
	public function loginBasketId($user_id=''){
		$loginBasketId='';
		$where=['user_id'=>$user_id];
		$basket=D('basket')->where($where)->find();
		if($basket){
			$loginBasketId=$basket['basket_id'];
		}else{
			$newBasketId=$this->newBasketId();
			D('basket')->data(['user_id'=>$user_id,'basket_id'=>$newBasketId])->add();
			$loginBasketId=$newBasketId;
		}
		return $loginBasketId;
	}
	/*
	 * 未登录notlogin_basket_id
	 */
	public function notLoginBasketId(){
		$notlogin_basket_id = cookie('basket')?cookie('basket'):session_id();
		cookie('basket',$notlogin_basket_id,5184000);
		return $notlogin_basket_id;
	}
	
	/*
         * 用户新basket_id
         */
	public function newBasketId($key='daxin')
	{
		return md5(session_id() . uniqid().$key);
	}
	
	/*
	 * 根据id获取品牌名称
	 *
	 */
	public function getBrandName()
	{
		$brand_id = I('post.brand_id');
		empty($brand_id) && $this->ajaxReturnStatus(1000,'缺少参数');
		$res = D('brand')->where(['id'=>$brand_id])->find();
		if( $res ){
			$this->ajaxReturnStatus(0,$res['brand_name']);
		}else{
			$this->ajaxReturnStatus(1001,'品牌不存在');
		}
	}
}