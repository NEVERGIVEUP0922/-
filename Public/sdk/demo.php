<?php


require_once dirname( __FILE__ ) . '/sdk.php'; //引入sdk文件


//创建实例
$client = Client::create();

//请求参数
$data = [
    'data'=>  [
        //user表的数据
        'user_name'=>'测试的用户名',
//        'user_mobile'=> '17688862039',
        'user_type'=>2, //用户分类 1是个人 2是企业
//        'user_email'=>'test@email.com',//联系人邮箱
//        //['info'] 里面 个人放normal表的数据 企业方company表的数据
        'info'=>[
            'company_name'=>'测试天玖隆有限公司', //企业名称
//            'company_people_num'=>130, //企业人数
//            'company_city'=>'',//企业所在城市
//            'company_address'=>'深圳南山区粤海街道南二道富安科技大厦',//企业地址(完整地址)
//            'company_business_nature'=> '',//企业经营性质
//            'company_business_product'=>'',//企业经营产品
//            'company_business_brand'=>'',//企业经营品牌
//            'company_annual_turnover'=> '',//企业年营业额
//            'company_sales_channel'=> '',//企业销售渠道
//            'company_user_name'=>'浩昌',
//            'company_user_sector'=>'大芯数据', //联系人部门
//            'company_user_position'=>'web前端',//联系人职务
//            'company_phone_num'=>'15091877533',//联系人手机
//            'company_user_qq'  =>'1234556',//联系人QQ
//            'company_user_wechat'=>'wexhsisj',//联系人微信
        ],
//        //['address'] 里面放收货地址的信息
//        'address'=>[
//            [
//                'consignee'=>'叶浩昌',
//                'address'=>'富安科技大厦A302',        //请把商城当时下单时使用的收货地址信息放在 第一个数组元素内
//                'zipcode'=>'723100',
//                'mobile'=>'1234412232',
//            ],
//            [
//                'consignee'=>'叶昌',
//                'address'=>'富安科技大厦302',
//                'zipcode'=>'723100',
//                'mobile'=>'123441222',
//            ],
//            [
//                'consignee'=>'号昌',
//                'address'=>'富安科技大厦02',
//                'zipcode'=>'72300',
//                'mobile'=>'123441222',
//            ],
//        ],
    ],
];

$rs = $client->withHost('http://192.168.5.199') //设置请求地址
->withService('App.Custinfor.GetSameCustList') //设置请求接口服务名
->setData($data)
    ->send();
echo($rs); //请求结果

