<?php
namespace Small\Controller;
use Think\Controller;
use Small\Tool\AccessTool as Access;

class LoginController extends BaseController {
    use Access;

    protected $appid;
    protected $secret;

    /**
     * @desc 用户登陆
     *
     */
    public function getOpenid(){
        $request=$this->post;

        $open_key=$this->exchangeXCX($request['open_key']);//获取小程序appid
        if(!isset($open_key['error'])||$open_key['error']<0){

            $this->return_data['data']=$open_key['data'];
            $this->return_data['statusCode']=$open_key['error'];
            $this->return_data['msg']=$open_key['msg'];
            $this->ajaxReturn($this->return_data);
        }
        $this->appid=$open_key['data']['appid'];
        $this->secret=$open_key['data']['secret'];

        $url="https://api.weixin.qq.com/sns/jscode2session?";
        $url.="appid=".$this->appid;
        $url.="&secret=".$this->secret;
        $url.="&js_code=".$request['code'];
        $url.="&grant_type=authorization_code";

        $url_result=file_get_contents($url);
        $url_result=json_decode($url_result,true);
        $this->session_key=$url_result['session_key'];

        if(isset($url_result['openid'])&&$url_result['openid']){
            $data='';
            Vendor('Xaochenxu.WXBizDataCrypt');
            $pc = new \WXBizDataCrypt($this->appid, $this->session_key);//验证用户数据
            $errCode = $pc->decryptData($request['encryptedData'], $request['iv'], $data );
            if($errCode!==0){
                $this->return_data['statusCode']=$errCode;
                $this->return_data['msg']='非法数据';
                $this->ajaxReturn($this->return_data);
            }
            $data=json_decode($data,true);

            $data['openid']=$data['openId'];
            $data['appid']=$this->appid;
            $add_result=D('customer')->userFirstLoginAdd($data);

            $this->return_data['statusCode']=$add_result['error'];
            $this->return_data['msg']=$add_result['msg'];
            if($add_result['error']>=0){
                $session_id=session_id();
                $this->return_data['session_token']=$session_id;

                $xcx_user_id=$add_result['data']['xcx_user_id'];

                $this->saveCustomer($session_id,[$url_result['openid'],$url_result['session_key'],$this->appid,$xcx_user_id,$this->secret, $this->session_key,$request['encryptedData'],$request['iv']]);

                $userInfo=$this->getUserInfo($session_id);//用户是否绑定商城用户
                $userInfo_return=[
                    'error'=>-1,
                    'msg'=>'没有绑定商城用户',
                ];
                if($userInfo['id']){
                    $userInfo_return=[
                        'error'=>0,
                        'msg'=>'登陆成功',
                    ];
                }
                $this->return_data['data']['user_info']=$userInfo_return;
            }
        }else{

            $this->return_data['statusCode']=-1;
            $this->return_data['msg']='openid错误';
        }

        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 用户绑定商城帐号
     * $request=[
     *      'session_token'=>'fewqfewf',
     *      'user_name'=>'kelly',
     *      'password'=>'liqiang123'
     *      'mobile'=>'liqiang123'
     *      'verification_code'=>'liqiang123'
     * ]
     *
     */
    public function customerToLongICMall(){
        $request=$this->post;
        $arguments=['session_token','user_name','password','isBindAccount'];
        $arguments_register=['session_token','user_name','password','isBindAccount','mobile','verification_code'];

        $customer=$this->getCustomer($request['session_token']);
        $mobile=$request['mobile'];
        if($request['isBindAccount']!='isBindAccount'){//新注册用户
            if($request['return_phone']!='return_phone'&&$request['encryptedData']){//解密手机号码不用参数检测
                foreach($arguments_register as $k=>$v){
                    if(!$request[$v]&&$v!='isBindAccount'){
                        $this->return_data['statusCode']=-1;
                        $this->return_data['msg']=$v.':参数错误2';
                        $this->ajaxReturn($this->return_data);
                    }
                }
            }

            $encryptedData= $request['encryptedData']?:$customer[6];
            $iv= $request['iv']?:$customer[7];
            if($encryptedData){//获取微信息小程序手机号码
                $data='';
                Vendor('Xaochenxu.WXBizDataCrypt');
                $pc = new \WXBizDataCrypt($customer[2], $customer[1]);//验证用户数据
                $errCode = $pc->decryptData($encryptedData, $iv, $data );
                if($errCode!==0){
                    $this->return_data['statusCode']=$errCode;
                    $this->return_data['msg']='非法数据';
                    $this->ajaxReturn($this->return_data);
                }
                $data=json_decode($data,true);
                $request['mobile']=$data['purePhoneNumber'];
                if($request['return_phone']=='return_phone'){
                    $this->return_data['statusCode']=$errCode;
                    $this->return_data['msg']='phone';
                    $this->return_data['data']=$request['mobile'];
                    $this->ajaxReturn($this->return_data);
                }
                $request['country_code']=$data['countryCode'];
                $request['user_mobile']=$data['purePhoneNumber'];
            }else{//获取微信息小程序手机号码失败
                $code=D('Small/Tool','Logic')->getCode($request['mobile']);
                if($request['verification_code']!=$code['code']){
                    $this->return_data['statusCode']=-2;
                    $this->return_data['msg']='验证码错误';
                    $this->ajaxReturn($this->return_data);
                }
            }
            $request['user_mobile']=$mobile;
            $request['mobile']=$mobile;
        }else{//绑定已有帐号
            foreach($arguments as $v){
                if(!$request[$v]){
                    $this->return_data['statusCode']=-1;
                    $this->return_data['msg']=$v.':参数错误';
                    $this->ajaxReturn($this->return_data);
                }
            }
        }

        $request['xcx_user_id']=$customer[3];
        $request['openid']=$this->openid;
        $request['appid']=$customer[2];

        if(!$request['xcx_user_id']||!$request['openid']||!$request['appid']){
            $this->return_data['statusCode']=-1;
            $this->return_data['msg']='身份未认证';
            $this->ajaxReturn($this->return_data);
        }

        $result=D('Small/Customer','Logic')->customerToLongicmall($request);

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 用户信息更新
     * $argument=[
     *  iv=>'1111',
     * encryptedData=>'111111'
     * ];
     *
     */
    public function xcxUserInfoUpdate(){
        $data='';
        $request=$this->post;
        Vendor('Xaochenxu.WXBizDataCrypt');
        $pc = new \WXBizDataCrypt($this->appid, $this->session_key);//验证用户数据
        $errCode = $pc->decryptData($request['encryptedData'], $request['iv'], $data );
        if($errCode!==0){
            $this->return_data['statusCode']=$errCode;
            $this->return_data['msg']='非法数据';
            $this->ajaxReturn($this->return_data);
        }

        $data=json_decode($data,true);
        $data['openid']=$this->openid;
        $result=D('customer')->xcxUserInfoUpdate($data);

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc 注册商城用户，并绑定当前openid
     *
     */
    public function registerPC(){
        $request=$this->post;
        $session_token=$request['session_token'];
        $customer=$this->getCustomer($session_token);
        $request['openid']=$customer[0];
        $request['appid']=$customer[2];
        $result=D('Account','Logic')->registerPC($request);

        $this->return_data['data']=$result['data'];
        $this->return_data['statusCode']=$result['error'];
        $this->return_data['msg']=$result['msg'];
        $this->ajaxReturn($this->return_data);
    }

    /**
     * @desc open_key交换appid,secret
     *
     */
    public function exchangeXCX($open_key){
        if(!$open_key) return ['error'=>-1,'msg'=>'open_key:参数错误'];

        $one=M('open_key','xcx_')->where(['open_key'=>$open_key])->find();
        if(!$one) return ['error'=>-1,'msg'=>'非法用户'];

        $checkOpenKey=$this->createOpenKey($one['appid'],$one['secret']);
        if((string)$checkOpenKey!==(string)$open_key) return ['error'=>-1,'msg'=>'open_key错误'];
        return ['error'=>0,'data'=>$one];
    }


}