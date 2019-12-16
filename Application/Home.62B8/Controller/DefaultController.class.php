<?php
// +----------------------------------------------------------------------
// | FileName:   IndexController.class.php
// +----------------------------------------------------------------------
// | Dscription:   前台首页控制器
// +----------------------------------------------------------------------
// | Date:  2017/7/31 13:32
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Home\Controller;

use EES\System\Redis;
use Home\Controller\HomeController;
use Common\Controller\Category;
use Admin\Controller\MsgController;
use Think\Log;
class DefaultController extends HomeController
{
    //use Category;
    /*
     * 初始化
     */


    public function lcs($str1, $str2)
    {
        // 存储生成的二维矩阵
        $dp = array();
        // 最大子串长度
        $max = 0;

        for ($i = 0; $i < strlen($str1); $i++) {
            for ($j = 0; $j < strlen($str2); $j++) {
                if ($str1[$i] == $str2[$j]) {
                    $dp[$i][$j] = isset($dp[$i-1][$j-1]) ? $dp[$i-1][$j-1] + 1 : 1;
                } else {
                    $dp[$i-1][$j] = isset($dp[$i-1][$j]) ? $dp[$i-1][$j] : 0;
                    $dp[$i][$j-1] = isset($dp[$i][$j-1]) ? $dp[$i][$j-1] : 0;

                    $dp[$i][$j] = $dp[$i-1][$j] > $dp[$i][$j-1] ? $dp[$i-1][$j] : $dp[$i][$j-1];
                }

                $max = $dp[$i][$j] > $max ? $dp[$i][$j] : $max;
            }
        }
//
//        for ($i = 0; $i < strlen($str1); $i++) {
//            for ($j = 0; $j < strlen($str2); $j++) {
//                echo $dp[$i][$j] . " ";
//            }
//            echo "<br />";
//        }

        return $max;
    }
    protected function _initialize()
    {

        parent::_initialize();
    }
    public $arr=[];
    public function strmatch($str,$model){
        $i=0;
        $j=0;
        $num = 0;
        $res =array(0);//空数组预先存入一个0，防止max()报错
        while(!max($res)&&(strlen($model)!=2||strlen($model)!=1||strlen($model)!=0)){
            while($i<strlen($str)){             //$model的第一个字符和$str从第一个字符挨个比对

                if($str[$i]!=$model[$j]){    //如果  不相等j依旧等于0，一直停留在当前位置 i++ 直至相等或者跳出循环
                    //如果不相等则$model 跳回第一个字符 j = 0 ,$str跳回 i+1-$num ，$num=0,继续比对
                    $j=0;
                    $i+=1-$num;
                    $num = 0;
                }else{                       //如果相等，(1)$num++,两个字符串都移向下一位i++ j++,$num 存入数组 如果相等继续（1）
                    $j++;
                    $i++;
                    $num++;
                    $res[]=$num;
                }
                if ($num==strlen($model)){  //如果$model已经100%相似了，则跳出循环
                    break;
                }

            }
            $model=substr($model,1);
            $i=0;
            $j=0;
            $num = 0;
        }

        return  max($res);
    }

    //拆分字符串
    public function mbStringToArray($string, $encoding = 'UTF-8') {
        $arrayResult = array();
        while ($iLen = mb_strlen($string, $encoding)) {
            array_push($arrayResult, mb_substr($string, 0, 1, $encoding));
            $string = mb_substr($string, 1, $iLen, $encoding);
        }
        return $arrayResult;
    }
    //编辑距离
    public  function levenshtein_cn($str1, $str2, $costReplace = 1, $encoding = 'UTF-8') {
        $count_same_letter = 0;
        $d = array();
        $mb_len1 = mb_strlen($str1, $encoding);
        $mb_len2 = mb_strlen($str2, $encoding);
        $mb_str1 = $this->mbStringToArray($str1, $encoding);
        $mb_str2 = $this->mbStringToArray($str2, $encoding);
        for ($i1 = 0; $i1 <= $mb_len1; $i1++) {
            $d[$i1] = array();
            $d[$i1][0] = $i1;
        }
        for ($i2 = 0; $i2 <= $mb_len2; $i2++) {
            $d[0][$i2] = $i2;
        }
        for ($i1 = 1; $i1 <= $mb_len1; $i1++) {
            for ($i2 = 1; $i2 <= $mb_len2; $i2++) {
                // $cost = ($str1[$i1 - 1] == $str2[$i2 - 1]) ? 0 : 1;
                if ($mb_str1[$i1 - 1] === $mb_str2[$i2 - 1]) {
                    $cost = 0;
                    $count_same_letter++;
                } else {
                    $cost = $costReplace; //替换
                }
                $d[$i1][$i2] = min($d[$i1 - 1][$i2] + 1, //插入
                    $d[$i1][$i2 - 1] + 1, //删除
                    $d[$i1 - 1][$i2 - 1] + $cost);
            }
        }
        return $d[$mb_len1][$mb_len2];
        //return array('distance' => $d[$mb_len1][$mb_len2], 'count_same_letter' => $count_same_letter);
    }





    protected function levensh($productList,$p_sign,$start_id=8,$end_id=11){
        $pid_arr=[];
        foreach ($productList as $k=>$v){
            foreach ($p_sign as $p_k=>$p_v){
                if(levenshtein($p_v['p_sign'],$v)<$start_id){//8-11
                    $pid_arr[]=$k;
                }
            }
        }
        if(!$pid_arr && $start_id!=$end_id) return $this->levensh($productList,$p_sign,$start_id+1,$end_id);
        else   return  $pid_arr;
    }

    public function closest_word($input, $words) {
        $shortest = -1;
        foreach ($words as $word) {
            $lev = levenshtein($input, $word);
            if ($lev == 0) {
                $closest = $word;
                $shortest = 0;
                break;
            }
            if ($lev <= $shortest || $shortest < 0) {
                $closest  = $word;
                $shortest = $lev;
            }
        }
        return $closest;
    }

    public function a($result){
        $p_k=-2;
        $num=0;
        $numarr=[];
        foreach ($result as $k=>$v){
            if(($p_k+1)==$k){
                $num=$num+1;
                $p_k=$k;
            }else{
                $numarr[]=$num;
                $num=0;
                $p_k=$k;
            }
        }
        return max($numarr);
    }

    public  function array_shun($a,$b){
        $return=[0];
        $return_str=[];
        foreach ($a as $ak=>$av){
            //请求键值    返回数组
            $arr_key=array_keys($b,$av,true);
            //存在
            if($arr_key){
                foreach ($arr_key as $arr_k=>$arr_v){
                    //次数
                    $arr_num=1;
                    //重叠字符数组
                    unset($arr_str);
                    $arr_str[]=$av;
                    $status=1;
                    while($status){
                        if($a[$ak+$arr_num]&&$b[$arr_v+$arr_num]&&$a[$ak+$arr_num]==$b[$arr_v+$arr_num]){
                            $arr_str[]=$b[$arr_v+$arr_num];
                            $status=1;
                            $arr_num+=1;
                        }else{
                            $status=0;
                        }
                    }
                    if($arr_num>=3&&$arr_num>max($return)){
                        $return=[$arr_num];
                        $return_str[$arr_num]=$arr_str;
                    }

                }
            }
            //结束
        }
        return ['arr_num'=>$return,
            'str'=>$return_str,
        ];
    }

    public  function  test(){


        $redis=Redis::getInstance();
        $product=D('Small/product');
        $field="id,p_sign,min,min_open,parameter,package,show_site,is_tax,tax,discount_num,cate_id,brand_id,fitemno";
        $productBlur=$product->where(['is_online'=>1])->field($field);
        $productBlur=$productBlur->bomList(['is_online'=>1])->relation(true);
        $blurOne=$productBlur->select();
        foreach ($blurOne as $k=>$v){
            $redis->hset('shop_product',$v['id'],json_encode($v));
        }
        echo "成功";
        die();


        $str1="abcdef";
        $str2="esdfdbcde1";

//暴力解法
        function longestCommonSubstring1($str1,$str2){
            $longest=0;
            $size1=strlen($str1);
            $size2=strlen($str2);
            for($i=0;$i<$size1;$i++){
                for($j=0;$j<$size2;$j++){
                    $m=$i;
                    $n=$j;
                    $length=0;
                    while($m<$size1 && $n<$size2){
                        if($str1[$m]!=$str2[$n]) break;
                        ++$length;
                        ++$m;
                        ++$n;
                    }
                    $longest=$longest < $length ? $length : $longest;
                }
            }
            return $longest;
        }
//矩阵动态规划法
        function longestCommonSubstring2($str1,$str2){
            $size1=strlen($str1);
            $size2=strlen($str2);
            $table=array();
            for($i=0;$i<$size1;$i++){
                $table[$i][0]=$str1[$i]==$str2[0] ? 1:0;
            }
            for($j=0;$j<$size2;$j++){
                $table[0][$j]=$str1[0]==$str2[$j] ? 1:0;
            }
            for($i=1;$i<$size1;$i++){
                for($j=1;$j<$size2;$j++){
                    if($str1[$i]==$str2[$j]){
                        $table[$i][$j]=$table[$i-1][$j-1]+1;
                    }else{
                        $table[$i][$j]=0;
                    }
                }
            }
            $longest=0;
            for($i=0;$i<$size1;$i++){
                for($j=0;$j<$size2;$j++){
                    $longest=$longest<$table[$i][$j] ? $table[$i][$j] : $longest;
                }}
            return $longest;
        }
        $len=longestCommonSubstring1($str1,$str2);
        $len=longestCommonSubstring2($str1,$str2);
        var_dump($len);die();
//        $str1 = "1212abbcbabbcbacbcbb2112";
//
//        $str2 = "abbcbabbcbacbcbb";
//
//
//        //-----开始定义变量----
//        $_FSTR_     = ""; //----父串----
//        $_SSTR_     = ""; //----字串-----
//        $_NOWSTR_    = ""; //----当前的字串----
//        $_LeftStr_    = ""; //----剩余的字串-----
//        $_LeftLen_    = ""; //----剩余的字符串长度----
//        $R            = ""; //----最终返回的字符串-----
//
//        //----以长串为父串 短串为子串---
//        if (strlen($str1) >= strlen($str2))
//        {
//            $_FSTR_ = $str1;
//            $_SSTR_ = $str2;
//        }
//        else
//        {
//            $_FSTR_ = $str2;
//            $_SSTR_ = $str1;
//        }
//
//        //-----遍历短串----
//        for ($i = 0; $i < strlen($_SSTR_); $i++)
//        {
//            $_NOWSTR_ = ""; //-----重置当前的字串----
//
//            $_LeftStr_ = substr($_SSTR_, $i);    //----剩余的字符串----
//
//            $_LeftLen_ = strlen($_LeftStr_);    //----剩余字串的长度----
//
//            for ($j = 0; $j < $_LeftLen_; $j++)
//            {
//                $_NOWSTR_ .= $_LeftStr_{$j};
//
//                //----在父串中查找字串---如果长度比保存的值长--就更新----
//                if (strpos($_FSTR_, $_NOWSTR_) && strlen($_NOWSTR_) > strlen($R))
//                {
//                    $R = $_NOWSTR_;
//                }
//            }
//        }
//
//
//
//        print $R ;die();







        $str0 = "功放IC(AB类) D2822N DIP8";
        $str1 = "TD2822N-(9-12V)-DIP8";
        echo $this->lcs($str0,$str1);die();
//        $str0 = "功放IC(AB类) D2822N DIP8";
//        $str1 = "TDA2822-(9-12V)-DIP8";
//        $a = str_split($str0);
//        $b = str_split($str1);
//        echo "<pre>";
//       print_r($this->array_shun($a,$b));
//       die();
//
//
//        echo "<pre>";
//        $str0 = "功放IC(AB类) D2822N DIP8";
//        $str1 = "TDA2822-(9-12V)-DIP8";
//        $a = str_split($str0);
//        $b = str_split($str1);
//        $result = array_intersect($a,$b);
//        print_r($this->a($result));die();
//
//
//
////        $redis=Redis::getInstance();
////        $productList=$redis->hGetAll('shop_product');
////       echo  $this->closest_word('2822',$productList);
////
////// Index between 0 and 1 : 0.80073740291681
////        //var_dump($comparator->compare($fp1, $fp2));
////
////die();
//////返回最长公共子序列
////        echo $add->getLCS("hello word", "hello china");
//////返回相似度
////        echo $add->getSimilar("功放IC(AB类) D2822N DIP8", "TDA2822-(9-12V)-DIP8");
////        die();
////        SimHashPHP();
//        echo $this->levenshtein_cn('ICAB D2822N DIP8','AP8022HNEC-DIP8');
//       // echo levenshtein.jaro_winkler('IC AB D2822N DIP8','TDA2822-(9-12V)-DIP8');
//        die();
//        echo "<pre>";
//        print_r(session());die();
        $redis=Redis::getInstance();
        $product=D('Small/product');
        $field="id,p_sign,min,min_open,parameter,package,show_site,is_tax,tax,discount_num,cate_id,brand_id,fitemno";
        $productBlur=$product->where(['is_online'=>1])->field($field);
        $productBlur=$productBlur->bomList(['is_online'=>1])->relation(true);
        $blurOne=$productBlur->select();
        foreach ($blurOne as $k=>$v){
           $redis->hset('shop_product',$v['id'],json_encode($v));
        }
        echo "成功";
        die();
//	    echo "<pre>";
//	    var_dump(M()->query("update wa_intergral_all set status=1,has_intergral_money=amount,intergral_amount=amount*100 WHERE intergral_id=7 and  user_id=1744"));die();
        $order=M('order_goods')->where(['order_sn'=>'1810311008012'])->find();
        var_dump((new WalletController())->erpIntergral(json_decode($order['wa_intergral'],true),$order['order_sn'],'2dwidfkdfgjkjd',1744,150));
    }
    public  function  test1(){
        $get="SOT23456789";
        $redis=Redis::getInstance();
        $productList=$redis->hGetAll('shop_product');
        foreach ($productList as $k=>$v){
            if($this->_levenshtein($get,$v)<9){//8-11
                echo  $k.'--'.$v;
                echo "<br>";
            }
        }
        die();

//        $p_sign=[['p_sign'=>'SOT23456']];
//
//        $redis=Redis::getInstance();
//        $productList=$redis->hGetAll('shop_product');
//        if($p_sign){
//            $pid_arr=$this->levensh($productList,$p_sign,8,11);
//            if($pid_arr){
//                print_r($pid_arr);
////                $productBlur=$product->where(['id'=>['IN',$pid_arr]]);
////                $productBlur=$productBlur->backetGoods(['id'=>['IN',$pid_arr]])->relation(true);
////                $blurOne=$productBlur->select();
//            }
//        }

    }

    public function _levenshtein($src, $dst){
        if (empty($src)) {
            return $dst;
        }
        if (empty($dst)) {
            return $src;
        }
        $temp = array();
        for($i = 0; $i <= strlen($src); $i++) {
            $temp[$i][0] = $i;
        }
        for($j = 0; $j <= strlen($dst); $j++) {
            $temp[0][$j] = $j;
        }
        for ($i = 1;$i <= strlen($src); $i++) {
            $src_i = $src{$i - 1};
            for ($j = 1; $j <= strlen($dst); $j++) {
                $dst_j = $dst{$j - 1};
                if ($src_i == $dst_j) {
                    $cost = 0;
                } else {
                    $cost = 1;
                }
                $temp[$i][$j] = min($temp[$i-1][$j]+1, $temp[$i][$j-1]+1, $temp[$i-1][$j-1] + $cost);
            }
        }
        return $temp[$i-1][$j-1];
    }
    /*
 * 详情页下载pdf
 *
 */
    public function downApk( $p_id = 1 )
    {


        if(stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false){
            $bool=false;
        }else{
            $bool=true;
        }



        //$path = SITE_PATH  . "Uploads/QzyTianTongPhone.apk";
        $path ="/Uploads/QzyTianTongPhone.apk";
        $this->assign('path',$path);
        $this->assign('bool',$bool);
        $this->display();
        die();

        $file_name = "QzyTianTongPhone.apk";     //下载文件名
        //中文需要转码
        $fileAdd = iconv('UTF-8', 'GB2312', $path . $file_name);
        //检查文件是否存在
        if (!file_exists($fileAdd) || !explode(".apks", $fileAdd) || !is_file($fileAdd)) {
            Config::set("default_return_type", "json");
            $rersout = ['Msg' => '文件不存在', 'code' => 100, 'Data' => ""];
            return $rersout;
        } else {
            //告诉浏览器这是一个文件流格式的文件(app)
            Header("Content-type: application/vnd.android.package-archive");
            //用来告诉浏览器，文件是可以当做附件被下载，下载后的文件名称为$file_name该变量的值。
            header('Content-disposition: attachment; filename=天地互联.apk'); //文件名
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
            //这里会告诉请求方,文件大小
            header('Content-Length: ' . filesize($fileAdd)); //告诉浏览器，文件大小
            //读取文件内容并直接输出到浏览器
            @readfile($fileAdd);
            exit ();
        }

    }
    /*
     * 首页滚动页面
     */
    public function infoOrder(){

        $str="QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
        $info=[];
        while(count($info)<8){
            $a['user_name']=substr(str_shuffle($str),5,3).'***';
            $a['order_id']=date('ymd',time()).rand(10,60).'****';
            $a['order_time']=rand(1,30)."分钟前";
            $a['order_money']='¥ '.round((50 + mt_rand() / mt_getrandmax() * (100000 - 50)),rand(0,2));
            $status=['待发货','部分发货','待收货','部分收货','全部收货'];
            $a['order_status']=$status[rand(0,4)];
            $info[]=$a;
        }
        $dxorder=M('order')->alias('dxo')->field("CONCAT(substr(dxu.user_name,1,3),'***') as user_name,CONCAT(substr(dxo.order_sn,1,8),'****') as order_id,if(TIMESTAMPDIFF(SECOND,dxo.create_at,NOW())>60,CONCAT(ROUND(TIMESTAMPDIFF(SECOND,dxo.create_at,NOW())/60),'小时前'),CONCAT(TIMESTAMPDIFF(SECOND,dxo.create_at,NOW()),'分钟前')) as order_time,CONCAT('¥ ',dxo.total) as order_money,CASE when dxo.ship_status=0 then '待发货' WHEN dxo.ship_status=1 THEN '部分发货' WHEN dxo.ship_status=2 THEN '待收货' WHEN dxo.ship_status=3 THEN '部分收货' WHEN dxo.ship_status THEN '全部收货' end as order_status")->order("create_at desc")->join("dx_user as dxu on dxu.id=dxo.user_id")->limit(8)->select();
        $info=array_merge($info,$dxorder);
        die(json_encode($info));
    }
    /*
     * 首页
     */
    public function infoMsg($user_id,$msg_title,$msg_content=''){
        MsgController::writeMsgToUserSale( $user_id, $msg_title, $msg_content);
    }
    public function index()
    {

        //热销
        $hot_num_result=$this->getSysValue(['code'=>'product_hot_num']);
        $hot_num=($hot_num_result['error']==0)?$hot_num_result['data']['list'][0]['value']:0;
        $hotresult=$this->productList('',1,$hot_num,true,'sell_num desc');
        $hot=($hotresult['error']==0)?$hotresult['data']['list']:'';

        //最新
        $hotresult=$this->productList(['show_site'=>1],1,$hot_num,true,'rand()');
        $new=($hotresult['error']==0)?$hotresult['data']['list']:'';

        //推荐
        $hotresult=$this->productList(['show_site'=>2],1,$hot_num,true,'rand()');
        $recommend=($hotresult['error']==0)?$hotresult['data']['list']:'';

        //特卖
        $hotresult=$this->productList(['show_site'=>3],1,$hot_num,true,'rand()');
        $speialSell=($hotresult['error']==0)?$hotresult['data']['list']:'';

        //广告图
        $advertPhoto=M("advert_photo")->where("status=1")->order("sort desc")->limit(10)->select();
        //公告信息
        $advertText=M("advert_text")->where("status=1")->order("sort desc")->limit(8)->select();

        foreach($advertPhoto as $k=>$v){
            if($v['position']==2){
                $top=$v;
                unset($advertPhoto[$k]);
            }
        }

        $brand1 = $this->brandRand();
        $brand2 = $this->brandRand();
        $brand3 = $this->brandRand();
        $this->assign( 'new', $new );
        $this->assign( 'hot', $hot );
        $this->assign( 'recommend', $recommend );
        $this->assign( 'speialSell', $speialSell );
        $this->assign( 'brand1', $brand1 );
        $this->assign( 'brand2', $brand2 );
        $this->assign( 'brand3', $brand3 );
        $this->assign( 'isIndex', 1 );
        $this->assign( 'advertPhoto', $advertPhoto );
        $this->assign( 'advertText', $advertText );
        $this->assign( 'top', $top );
        $this->display();
    }

    /*
     * 原厂专区
     *
     */
    protected function originGoods()
    {
        $pro = D( 'product' );
        $field = 'p.id,p.p_sign,p.name,p.sell_num,p.price,c.cate_name, b.brand_name, d.img, d.describe,d.replace_desc,d.use_area';
        $join = '__CATEGORY__ as c On c.id = p.cate_id';
        $join2 = '__BRAND__ as b On b.id = p.brand_id';
        $join3 = '__PRODUCT_DETAIL__ as d On d.p_id = p.id';
        $where = [ 'c.id' => 1 ];
        $data = $pro->alias( 'p' )->field( $field )->join( $join, 'LEFT' )->join( $join2, 'LEFT' )->join( $join3, 'LEFT' )->order( 'p.id DESC' )->limit( 4 )->select();

        return $data;
    }

    /*
     *
     * 品牌目录
     */
    public function brand()
    {
        //获取热门推荐品牌
        $hot = M( 'brand' )->field( 'id,brand_name,href,logo,first' )->where( [ 'is_hot' => 1 ] )->limit( 6 )->select();
        //获取所有品牌
        $data = M( 'brand' )->field( 'id,brand_name,href,logo,first' )->order( 'first DESC' )->select();
        $data = $this->getBrandSort( $data );
        $this->assign( 'hot', $hot );
        $this->assign( 'data', $data );
        $this->assign( 'isBrand', 1 );
        $this->display();
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
     * 获取前台所有2级分类
     *
     */
    public function two()
    {
        $treeArray = $this->getTreeArray(2,345);
        $result = [];
        foreach($treeArray as $item){
            if( $item['level'] == 1 ){
                $item['children'] = $this->getTreeArray($item['lft'],$item['rht'],true);
                $result[] = $item;
            }
        }
        return $result;
    }
    /*
     * 分类目录
     **/
    public function category()
    {
        //获取所有的category
        //$res = M('category')->field('id,cate_name,parent_id,order')->select();
        //$cate = Category::makeTree($res);
        $treeArray = $this->getTreeArray(2,345, true);
        $cate = [];
        foreach($treeArray as $item){
            if( $item['level'] == 1 || $item['level'] == 0 ){
                $item['children'] = $this->getTreeArray($item['lft'],$item['rht'],true);
                $cate[] = $item;
            }
        }
        $this->assign('cate' ,$cate);
        //echo json_encode($cate);
        $this->assign('isCategory',1);
        $this->display();
    }

    /*
     * 应用市场
     *
     */
    public function appMarket()
    {
        $this->assign('isAppMarket',1);
        $this->display();
    }

    /*
     * 方案中心
     *
     */
    public function planCenter()
    {
        $this->assign('isPlanCenter',1);
        $this->display();
    }
    /*
     * 免费样品申请
     *
     */
    public function sampleApply()
    {
        !is_login() && redirect(U('Home/Account/login'));
        $this->assign('isfreeSample',1);
        $this->assign('isLogin',is_login());
        $this->display();
    }
    /*
     * 免费样品bom导入
     *
     */
    public function sampleBom()
    {
        $this->assign('isfreeSample',1);
        $this->assign('isLogin',is_login());
        $this->display();
    }
    /*
     * BOM报价
     *
     */
    public function bomPrice()
    {
        $data=I('request.action','1');
        $is_relogin = I('get.isRelogin');
        //print_r($data);
        $this->assign('isBom',1);
        $this->assign('is_relogin',$is_relogin);
        $this->assign('isLogin',is_login());
        if($data=="search"){
            $this->display();
        }else{
            $this->display("BomMatch");
        }

    }
    /*
     * 积分商城
     *
     */
    public function pointsMall()
    {
        $requst=I('get.');
        $is_relogin = I('get.isRelogin');
        $hot_num_result=$this->getSysValue(['code'=>'product_hot_num']);
        $hot_num=($hot_num_result['error']==0)?$hot_num_result['data']['list'][0]['value']:0;
        $hotresult=$this->productList('',1,$hot_num,true,'sell_num desc');
        $pointProduct=($hotresult['error']==0)?$hotresult['data']['list']:'';
        //print_r($data);
        $this->assign('ispointsMall',1);
        $this->assign('pointProduct',$pointProduct);
        $this->assign('is_relogin',$is_relogin);
        $this->assign('isLogin',is_login());
        $this->assign('points','5000');
        if($requst["record"]){
            $this->display("points/pointsRecord");
        }else{
            $this->display("points/pointsMall");
        }
    }

    /*
     *
     *  发布需求
     */
    public function release()
    {
        $user_id = is_login();
        if( IS_POST){
            $data = I('post.form');
            $release = M('release');
            //接收表单数据
            //超过10条进行处理数据,最多只保留前10条数据
            $data = array_slice($data,0,10,true);
            $len = count($data);
            $link_name = I('post.link_name');
            $link_mobile = I('post.link_mobile');
            $end_time = I('post.end_time');
            $append = I('post.append');
            //使用事务
            $release->startTrans();
            $n = 0;
            foreach( $data as $key=>$value ){
                if(empty($data[$key]['price'])){
                    $data[$key]['price'] = 0;
                }
                if( $value['is_tax'] == '含税'  ){
                    $data[$key]['is_tax'] = 0;
                }elseif($value['is_tax'] == '不含税' ){
                    $data[$key]['is_tax'] = 1;
                }
                $data[$key]['user_id'] = $user_id;
                $data[$key]['link_mobile'] = $link_mobile;
                $data[$key]['link_name'] = $link_name;
                $data[$key]['end_time'] = $end_time;
                $data[$key]['append'] = $append;
                //写入数据库
                $re = $release->add($data[$key]);
                if( $re ){
                    $n++;
                }else{
                    $n--;
                }
            }

            if( $n == $len ){
                $release->commit();
                if( IS_AJAX ){
                    $this->ajaxReturnStatus(0000,'添加成功');
                }
                return true;
            }else{
                $release->rollback();
                if( IS_AJAX ){
                    $this->ajaxReturnStatus(1000,'添加失败');
                }
                return false;
            }
        }elseif(IS_GET){
            $this->display();
        }
    }


    /*
     *  检查商品名称是否存在
     */
    public function checkProductName()
    {
        $name= I('product_name');
        empty( $name ) && $this->ajaxReturnStatus(1001, '不能为空');
        $re = D('product')->field('id')->where(['name'=>$name])->find();
        if( $re ){
            $this->ajaxReturnStatus(0000, '存在');
        }else{
            $this->ajaxReturnStatus(1000, '不存在');
        }
    }

    /*
     *  检查品牌名称是否存在
     */
    public function checkBrandName()
    {
        $name = I('brand_name');
        empty( $name ) && $this->ajaxReturnStatus(1001, '不能为空');
        $re = D('brand')->field('id')->where(['brand_name'=>$name])->find();
        if( $re ){
            $this->ajaxReturnStatus(0000, '存在');
        }else{
            $this->ajaxReturnStatus(1000, '不存在');
        }
    }


    protected function getBrandSort($data)
    {
        foreach( $data as $k=>$v ){
            $arr[$v['first']][] = $v;
        }
        ksort($arr);//按键名对数组排序
        return $arr;
    }
    /*
     *  生成图片验证码
     *  @return 验证码图片
     */
    public function ImgVerify()
    {
        ob_clean();     //清除缓存
        $Verify = new \Think\Verify();
        $Verify->fontSize = 20; //验证码字体大小
        $Verify->length = 4;    //验证码位数
        $Verify->entry(); //输出
    }

    /*
     *  检查图片验证码
     * @return json  返回状态与提示
     */
    public function checkImgVerify( $code='' )
    {
        if( IS_AJAX ){
            $code = $_GET['code'];  //验证码
        }
        $verify = new \Think\Verify(array('reset'=>false));
        if($verify->check($code)){
            $this->ajaxReturnStatus(0000); //验证码正确
        }else{
            $this->ajaxReturnStatus(1002); //验证码错误
        }
    }

    public function feedback()
    {
        if( IS_POST && IS_AJAX ){
            $data['content'] = I('content');
            $data['user_id'] = session('userId')?:0;
            $data['star'] = empty(I('star'))?1: I('star');
            $data['name'] = empty(I('name'))?'':I('name');
            $data['mobile'] = empty(I('mobile'))?'':I('mobile');
            $re = M('feedback')->add($data);
            if( $re ){
                $this->ajaxReturnStatus( 000, '发送成功!感谢您宝贵的意见');
            }else{
                $this->ajaxReturnStatus(1000, '系统繁忙!反馈失败');
            }
        }else{
            if( is_login() ){
                redirect(U('Home/User/feedback'));
            }
            $this->display();
        }
    }

    public function fault()
    {
        $this->display();
    }
}