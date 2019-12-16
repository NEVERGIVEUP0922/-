<?php

// +----------------------------------------------------------------------
// | FileName:   AccountController.class.php
// +----------------------------------------------------------------------
// | Dscription:    前台用户登录注册控制器
// +----------------------------------------------------------------------
// | Date:  2017/8/3 17:36
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace  Home\Controller;

use Home\Controller\HomeController;
use Org\ThinkSDK\ThinkOauth;
use Common\Controller\SmsController as sms;

class AccountController extends HomeController
{
    use sms;

	public function _initialize()
	{
		parent::_initialize();
	}

	public function __call($method, $args)
	{
		redirect(U('Home/Default/index'));
		return parent::__call($method, $args); // TODO: Change the autogenerated stub
	}
	/*
	 * 登录
	 *
	 */
	public function login()
	{
             
		if(is_login()){
			return $this->error('您已登录！',U('Home/Default/index'), 1);
			exit();
		}
		$type = I('get.type')?I('get.type'):1;
		if( IS_GET ){
			switch( $type ){
				case 'qq':
					$this->loginByOauth('qq');
					break;
				case 'wechat':
					$this->loginByOauth('wechat');
					break;
				case 'sina':
					$this->loginByOauth('sina');
					break;
				default:
					$is_relogin = I('get.isRelogin');
					if( $is_relogin ){
						$this->assign('is_relogin', $is_relogin);
					}
					$this->display();
					break;
			}
		}
		//如果是post请求
		if( IS_POST ){
			//处理默认登录       
			$this->loginByName();
		}

	}

	/*
	 * 默认用户名密码登录处理
	 */
	protected function loginByName()
	{
    
		$data = I('post.');
		//多次密码错误后出现验证码
		//$data['verify'] = I('post.verify')?I('post.verify'): null;
		if( empty($data) ){
			$this->ajaxReturnStatus(1000 , '数据为空!请检查');
		}
		//检查账号是否锁定
		if( $this->isUserNameLock($data['user_name'])){
			$user = M('user');
			//如果全是数字 那么就匹配账号为手机号码
			if(is_numeric($data['user_name']) && mb_strlen($data['user_name']) == 11 ){
				//是手机号码
                $re = $user->where([ 'user_mobile' => $data['user_name'] ])->select();
			}else{
				if(filter_var($data['user_name'], FILTER_VALIDATE_EMAIL)){
					//是邮箱
					$re = $user->where([ 'user_email' => $data['user_name'] ])->select();
				}else{
					//是用户名
					//检查账号合法性
					if($this->checkName($data['user_name'])){
						$re = $user->where([ 'user_name' => $data['user_name'] ])->select();
					}
				}
			}
			if( !$re ){
				$this->ajaxReturnStatus(1100, '用户名错误');
			}
			$re_arr=[];
			foreach($re as $k=>$v){ $re_arr[$v['id']]=$v; }

			//用户是否有两个账号
			if( !isset($data['user_id']) && count($re)==2 && hash_check($data['user_pass'], $re[0]['user_pass']) && hash_check($data['user_pass'], $re[1]['user_pass']) ){
//                die(json_encode(['error'=>0,'msg'=>'选择子账号','data'=>[$re[0]['user_type']=>$re[0]['id'],$re[1]['user_type']=>$re[1]['id']]]));
                $return_data=[];
                if($re[0]['user_type']==1){
                    $return_data=[
                        'personal'=>[   'user_id'=>$re[0]['id'],
                            'user_type'=>$re[0]['user_type']
                        ],
                        'company'=>[   'user_id'=>$re[1]['id'],
                            'user_type'=>$re[1]['user_type']
                        ]
                    ];
                } else{
                    $return_data=[
                        'personal'=>[   'user_id'=>$re[1]['id'],
                            'user_type'=>$re[1]['user_type']
                        ],
                        'company'=>[   'user_id'=>$re[0]['id'],
                            'user_type'=>$re[0]['user_type']
                        ]
                    ];
                }
                $this->ajaxReturnStatus(0000,['error'=>0,'msg'=>'选择子账号','data'=>$return_data]);
            }

            $user_pass='';
            $user_id='';
//            if( count($re)==2 && hash_check($data['user_pass'], $re[0]['user_pass']) && hash_check($data['user_pass'], $re[1]['user_pass'])){
            if( count($re)==2 ){
                if(hash_check($data['user_pass'], $re[0]['user_pass']) && hash_check($data['user_pass'], $re[1]['user_pass'])){
                    foreach($re as $k=>$v){
                        if($v['id']==$data['user_id']){
                            $user_pass=$v['user_pass'];
                            $user_id=$v['id'];
                        }
                    }
                }else if(hash_check($data['user_pass'], $re[0]['user_pass'])){
                    $user_pass=$re[0]['user_pass'];
                    $user_id=$re[0]['id'];
                }else if(hash_check($data['user_pass'], $re[1]['user_pass'])){
                    $user_pass=$re[1]['user_pass'];
                    $user_id=$re[1]['id'];
                }
            }else{
                $user_pass=$re[0]['user_pass'];
                $user_id=$re[0]['id'];
            }

            if($re_arr[$user_id]['user_type']==20){
                $userAccount=M('user_son')->where(['user_id'=>$user_id])->find();
                if($userAccount['is_delete']==1) $this->ajaxReturnStatus(1200, '帐号被停用');
            }

            $password_pass=hash_check($data['user_pass'], $user_pass);
            //验证记住密码
            if(!$password_pass){
                $remember=D('Home/Account','Event')->checkRememberPasswordAction($data['user_name'],$data['user_pass']);
            }
            //记住密码
            if($data['remember']!='remember'){//删除记住密码
                //删除记住密码
                $rememberDelete=D('Home/Account','Event')->rememberPasswordDelete($data['user_name']);
            }
 
			//验证密码
			if( !$password_pass&&$remember['error']<0 ){            
				return $this->loginFalseCheck( $data['user_name'] );
			}else{

			    //记住密码
                if($data['remember']=='remember'){
                    $remember=D('Home/Account','Event')->rememberPasswordAction($data['user_name'],$data['user_pass'],$re[0]['create_time']);
                    if($remember['error']<0)$this->loginFalseCheck( $data['user_name'] );
                }
             
				//session存储用户
				$this->addUserToSession($user_id);
           
				if( IS_AJAX ){
					$dljf=M("intergral_all","wa_")->alias('wa')
						->join('wa_intergral_rule as wr on wr.id = wa.integral_id')
						->where(["wa.user_id"=>$user_id,"wa.create_time"=>["egt",date("Y:m:d 00:00:01")]])->find();
               
				    if(!$dljf){              
				        objectIntergral($user_id,111,'登录');
                    }
					$this->ajaxReturnStatus(0, '登录成功!',session()['userInfo']);
				}else{
					$dljf=M("intergral_all","wa_")->alias('wa')
						->join('wa_intergral_rule as wr on wr.id = wa.integral_id')
						->where(["wa.user_id"=>$user_id,"wa.create_time"=>["egt",date("Y:m:d 00:00:01")]])->find();
				    if(!$dljf){
				        objectIntergral($user_id,111,'登录');
                    }
					$this->success('登录成功',U('Home/Default/index'));
				}
			}
		}else{
			return $this->loginFalseCheck( $data['user_name'] );
		}
	}


	/*
	 * 验证用户名格式是否合法
	 *
	 */
	protected function checkName( $user_name )
	{
		if( empty($user_name) ){
			$this->ajaxReturnStatus(1102, '用户名不能为空');
		}
		$len = mb_strlen($user_name);
		if( $len < 2 ){
			$this->ajaxReturnStatus(1103, '用户名长度至少2位');
		}elseif( $len > 20 ){
			$this->ajaxReturnStatus(1104, '用户名长度超过限制');
		}
		//$re = '/^[a-zA-Z\x{4e00}-\x{9fa5}]+[\w\-\x{4e00}-\x{9fa5}\_]?[^_^\-]$/u';
		////正则 已字母开头，长度最少为2位，最大20位
		//$bool = preg_match( $re,$user_name);
		//if( empty($bool) ){
		//	$this->ajaxReturnStatus(1105, '用户名格式不正确');
		//}
		return true;
	}

	/*
	 * 检查指定账号是否被锁定
	 */
	protected function isUserNameLock($userName)
	{
		$lockKey = 'LoginLock:'.$userName;
		$lockData = S( $lockKey );
		if( $lockData ){
			return false;
		}else{
			return true;
		}
	}

	/*
	 * 登录错误限制
	 */
	protected function loginFalseCheck( $userName )
	{
		//$redis = $this->connectRedis();
		//获取是否配置检查错误限制
		$status = C('Home_LOGIN_FALSE_CHECK');
		//为零时 ,代表不检查登录错误限制
		if( $status == 0 ){
			return true;
			exit();
		}elseif( $status == 1 ){
			//读取配置
			//最大错误次数
			$maxNum = C('Home_LOGIN_FALSE_MAX');
			//锁定有效时长
			$time = C('Home_LOGIN_FALSE_TIME');//默认900秒
			//定义锁定键 键名
			$lockKey = 'LoginLock:'.$userName;
//			S( $lockKey, null );
			$lockData = S( $lockKey );
			//$lockData = $redis->hGetAll( $lockKey );
			if( $lockData ){
				//获取锁定剩余有效时间
				//$cle = $redis->ttl( $lockKey );
				$oldTime = S($lockKey);
				$nowTime = time();
				$cle = ($oldTime + $time ) - $nowTime;
				if( $cle < 60 ){
					$this->ajaxReturnStatus( 1200, '您的账号已锁定,请您'. $cle .'秒后再次尝试登录' );
				}else{
					$m = ceil( $cle/60 );
					$this->ajaxReturnStatus( 1200, '您的账号已锁定,请您'. $m .'分钟后再登录' );
				}
			}
			//读取登录错误次数
			$key = 'loginFalse:'.$userName;
			//$data = $redis->hGetAll( $key );
			$num = S($key);
			if( empty( $num ) ){
				//$redis->hIncrBy( $key, 'num', 1 ); //错误次数加1
				S( $key, 1 );
				$this->ajaxReturnStatus(1200, '密码错误!请重新输入');
			}else{
				//$redis->hIncrBy( $key, 'num', 1 ); //错误次数加1
				S( $key, $num + 1 );
				//$newNum = $redis->hGet( $key, 'num' );
				$newNum = S($key);
				if( $newNum > $maxNum ) { //达到次数限制时
					//删除错误次数键 设置账号锁定键 并设置有效期
					//$redis->delete( $key );
					S($key, null);
					//$redis->hSet($lockKey, 'time', time());
					//$redis->expire( $lockKey, $time );
					S($lockKey,time(),300);
					$m = ceil( $time/60 );
					$this->ajaxReturnStatus(1200, '密码输入错误已超过' . $maxNum . '次!<br>账号暂时锁定,请您' . $m . '分钟后再登录');
				}else {
					$this->ajaxReturnStatus(1200, '密码错误!请重新输入!您还有' . ($maxNum - $newNum+1) . '次机会');
				}
			}
		}
	}


	/*
	 * 注册
	 */
	public function register()
	{
		if(is_login()) $this->error('您已登录！',U('Home/Default/index'), 1) ;
		$this->getRegister();
	}

	/*
	 * 注册模板响应
	 */
	protected function getRegister()
	{
		//用户类型  默认为1是个人用户 2为企业用户
		$type = I('type', 1);
		switch( $type ){
			case 2;
				$this->display('register_company');
				break;
			default:
				$this->display('register_normal');
				break;
		}
	}

	/*
	 * 注册处理
	 */
	public function doRegister()
	{
		//用户类型  默认为1是个人用户 2为企业用户
		$type = I('get.type');
		if( empty($type) ){
			$this->normalRegister();
		}else{
			$this->companyRegister();
		}
	}

	/*
	 * 个人用户注册处理
	 *
	 */
	protected function normalRegister()
	{
		$data = I('post.');

		//检查用户名合法性
		if($this->checkName($data['user_name'])){
			$user = M('user');
			$re = $user->field('id')->where([ 'user_name' => $data['user_name'] ])->find();
			if( !$re ){
				//检查手机号码
				if( $this->checkMobile( $data['user_mobile'] ) ){
					$res = $user->field('id')->where(['user_mobile'=>$data['user_mobile'],'user_type'=>1])->find();//查询手机号是否存在
					if (!$res) {
						//检查短信验证
						if($this->checkSms($data['mobile_code'])){
							//检查密码合法性
							if( $this->checkPwd($data['user_pass']) ){
								$data['user_pass'] = hash_string($data['user_pass']);//加密
								$data['user_type'] = 1; //个人用户
								$data['nick_name'] = $data['user_name']; //用户昵称为用户名
								$data['user_ip'] = get_client_ip(); //获取客户端ip
								//事务
								$user->startTrans();
								$id = $user->add($data);
								$normal = M('user_normal');
								$dataNormal = [
									'user_id'   => $id,
								];
								$normalId = $normal->add($dataNormal);
//								//测试账期写入
//								$add_data['user_id'] = $id;
//								$add_data['type'] = 1;
//								$add_data['status'] = 1;//提交审核
//								$add_data['mobile'] = $data['user_mobile'];
//								$add_data['human_id'] = 123456789;
//								$add_data['bank_acount'] = 123456789;
//								$add_data['quota'] = 100000;
//								$add_data['day_type'] = 4;
//								$account_id = M('user_account')->add($add_data);
								if( $id && $normalId ){
                                    $pinyin=[
                                        'customerId_arr'=>[$id]
                                    ];
                                    $pinyin_result=D('Admin/Customer')->updateCompanyNameFirstString($pinyin);//更新拼音

									$user->commit();
									$key = 'ssid_'.$id;
									S($key,null);
									$this->addUserToSession($id);
									$this->ajaxReturnStatus(0000 , '注册成功!');
								}else{
									$user->rollback();
									$this->ajaxReturnStatus(1000 , '注册失败!请重试');
								}
							}
						}else{
//							$this->ajaxReturnStatus(1000, '验证码错误!'); //存在
						}
					}else{
						$this->ajaxReturnStatus(1000, '手机号码已注册!'); //存在
					}
				}
			}else{
				$this->ajaxReturnStatus(1000, '用户名已存在');
			}
		}
	}

	/*
	 * 企业用户注册处理
	 */
	protected function companyRegister()
	{
		$data = I('post.');
		//检查用户名合法性
		if($this->checkName($data['user_name'])){
			$user = M('user');
			$re = $user->field('id')->where([ 'user_name' => $data['user_name'] ])->find();
			if( !$re ){
				//检查手机号码
				if( $this->checkMobile( $data['user_mobile'] ) ){
					$res = $user->field('id')->where(['user_mobile'=>$data['user_mobile'],'user_type'=>2])->find();//查询手机号是否存在

					if (!$res) {

                        $userCompany=M('user_company');
                        if(!isset($data['company_name'])||empty($data['company_name'])){
                            $this->ajaxReturnStatus(1000, '公司名称不能为空'); //存在
                        }
                        $re2 = $userCompany->field('id')->where(['company_name'=>$data['company_name']])->find();//查询用户名是否存在
                        if ( $re2 ) {
                            $this->ajaxReturnStatus(1000, '公司名称已经存在'); //存在
                        }

						//检查验证码
//						if( $this->checkSms( $data['mobile_code'] ) ){
                        if( 1 ){
							//检查密码
							if( $this->checkPwd($data['user_pass']) ){
								//事务处理
								$user->startTrans();
								$dataCompany = I('post.');
								$company = M('user_company');
								$rules = [
									['company_name', 'require','企业名称不能为空'],
									['company_area', 'require','企业所在地不能为空'],
									['company_address', 'require','企业详细地址不能为空'],
									['company_people_num', 'require','企业人数不能为空'],
									['company_user_name', 'require','联系人不能为空'],
									['company_user_sector', 'require','所在部门不能为空'],
									['company_phone_num', 'require', '固定电话不能为空'],
									['company_user_email','email','邮箱格式不正确']
								];
								//检查 公司详细信息数据
								if( !$company->validate($rules)->create($dataCompany) ){
									//错误回滚
									$user->rollback();
									$this->ajaxReturnStatus(1000 , $company->getError());
								}else{
									$userData['user_name'] = $dataCompany['user_name'];
									$userData['user_pass'] = hash_string($dataCompany['user_pass']);//加密
									$userData['user_type'] = 2; //企业用户
									$userData['nick_name'] = $dataCompany['company_name']; //企业账户昵称为公司名
									$userData['user_mobile'] = $dataCompany['user_mobile'];
									$userData['user_email'] = $dataCompany['user_email'];
									//$data['birthday'] = date('Y-m-d', strtotime('19700101'));
									$userData['last_ip'] = get_client_ip(); //获取客户端ip
									$userId = $user->add($userData);
									$dataCompany['check_status'] = 0;
									$dataCompany['user_id'] = $userId;
                                    $dataCompany['company_user_phone'] = $dataCompany['user_mobile'];
                                    $dataCompany['company_user_position'] = $dataCompany['company_user_sector'];
                                    $dataCompany['company_user_email'] = $dataCompany['user_email'];
									$companyId = $company->add($dataCompany);
									
//									//测试账期
//									$add_data['user_id'] = $userId;
//									$add_data['type'] = 2;
//									$add_data['status'] = 1;//提交审核
//									$add_data['mobile'] = $userData['user_mobile'];
//									$add_data['human_id'] = 123456789;
//									$add_data['bank_acount'] = 123456789;
//									$add_data['quota'] = 100000;
//									$add_data['day_type'] = 4;
//									$account_id = M('user_account')->add($add_data);
//									if( $userId && $companyId && $account_id ){
                                    if( $userId && $companyId ){
                                        $pinyin=[
                                            'customerId_arr'=>[$userId]
                                        ];
                                        $pinyin_result=D('Admin/Customer')->updateCompanyNameFirstString($pinyin);//更新拼音

										//提交事务
										$user->commit();
										$key = 'ssid_'.$userId;
										S($key,null);
										$this->addUserToSession($userId);
										$this->ajaxReturnStatus(0000 , '注册成功!');
									}else{
										//回滚事务
										$user->rollback();
										$this->ajaxReturnStatus(1000 , '注册失败!请重试');
									}
								}
							}
						}
					}else{
						$this->ajaxReturnStatus(1000, '手机号码已注册!'); //存在
					}
				}
			}else{
				$this->ajaxReturnStatus(1000, '用户名已存在');
			}
		}
	}

    /*
     * 企业子用户注册处理
     */
    public function companySonRegister()
    {
        $session = session();
        if(!$session['userId']) $this->dataReturnFront(1,'账号信息错误');
        if($session['userType']!=2) $this->dataReturnFront(1,'账号信息错误');
        if(!IS_AJAX)  $this->dataReturnFront(1,'is not ajax request');
        $data = I('get.');
        if($data['user_pass']&&preg_match('/^[a-zA-Z_]\w{5,}$/',$data['user_pass'])==0) $this->dataReturnFront(1,'密码未设置,或格式错误<br/>(以字母开头长度不小于6的子符串)');
        if(!$data['nick_name']) $this->dataReturnFront(1,'昵称未设置');

        $company=M('user_company')->field('company_name')->where(['user_id'=>$session['userId']])->find();
        if(strpos($data['nick_name'],$company['company_name'])===false){
            $data['nick_name']=$company['company_name'].'--'.$data['nick_name'];
        }
        $phone=$data['user_mobile'];
        $this->checkMobile( $phone );
        if(isset($data['childId'])&&$data['childId']){
            $where_user=['user_mobile'=>$phone,'id'=>['neq',$data['id']]?:0];
        }else{
            $where_user=['user_mobile'=>$phone];
        }
        $res = M('user')->field('id')->where($where_user)->find();//查询手机号是否存在
        if( $res ) $this->dataReturnFront(2,'手机号码已注册');

        $son_count=D('user_son')->where(['p_id'=>$session['userId']])->count();
        if($son_count==10) $this->dataReturnFront(1,'子账号数量已有10个');
        $last_son=D('user_son')->where(['p_id'=>$session['userId']])->order('id desc')->find();
        $last_son['id']=isset($last_son['id'])?$last_son['id']:'0';
        $son_nick_name=$session['userInfo']['nick_name'].$last_son['id'];

        $userData['user_name'] = $phone;
        $userData['user_pass'] = $data['user_pass']?hash_string($data['user_pass']):'';//加密
        $userData['user_type'] = 20; //企业子用户
        $userData['user_mobile'] = $phone;
        $userData['fcustjc'] = $userData['nick_name'] = $data['nick_name']?:$son_nick_name;
        $userData['last_ip'] = get_client_ip(); //获取客户端ip
        //开启事务处理
        $user=D('user');
        $user->startTrans();
        $userId='';
        if(isset($data['childId'])&&$data['childId']){
            $field_user_son='user_name,user_mobile,nick_name,fcustjc';
            if($data['user_pass']) $field_user_son.=',user_pass';
            $user_son = $user->field($field_user_son)->where(['id'=>$data['childId']])->save($userData);
        }else{
            $userId = $user->add($userData);
            $user_son = D('user_son')->add(['p_id'=>$session['userId'],'user_id'=>$userId]);
        }
        $error=0;
        $msg='子账号生成成功';
        if($userId!==false&&$user_son){
            $user->commit();
//            $content= '【玖隆芯城】亲爱的用户,您的企业账号已生效，账号：'.$phone.'密码：'.$user_pass;
//            $this->sendSms($phone,$content,$active='companySon');
        }else{
            $error=1;
            $msg='子账号生成失败';
//            $user->rollback();
        }
        $this->dataReturnFront($error,$msg);
    }

    public function dataReturnFront($error=0,$msg='',$data=''){
        header('Content-Type:application/json; charset=utf-8');
        die(json_encode(['error'=>$error,'msg'=>$msg,'data'=>$data]));
    }

    /*
     * 停用启用子账号
     *
     */
    public function userDeleteSon(){
        $session=session();
        if(!IS_AJAX)  die(json_encode(['error'=>1,'msg'=>'is not ajax request']));
        if(!$session['userId']) die(json_encode(['error'=>1,'msg'=>'登录信息错误']));
        $user_id=I('get.user_id');
        if(!$user_id) die(json_encode(['error'=>1,'msg'=>'参数错误']));
        $request=I('get.');
        $is_delete=($request['is_delete']==1)?1:0;//1停用，0启用
        //开启事务处理
        $user=D('user');
        $user->startTrans();
        $user_son_delete=D('user_son')->where(['user_id'=>$user_id,'p_id'=>$session['userId']])->save(['is_delete'=>$is_delete]);
        if($user_son_delete!==false){
            $user->commit();
            die(json_encode(['error'=>0,'msg'=>'子账号删除成功']));
        }else{
            $user->rollback();
            die(json_encode(['error'=>1,'msg'=>'子账号删除失败']));
        }
    }


	/*
	 * 找回密码
	 *
	 */
	public function passForget()
	{
		//检查是否已登录
		if( is_login() ){
			$this->error('您已登录！',U('Home/Default/index'),1);
			exit();
		}
		$type = I('type')?I('type'):0;
		switch( $type ){
			default:
				//默认通过手机号码找回密码
				$this->forgetFormMobile();
				break;
		}
	}

	/*
	 * 默认通过手机号码找回
	 * @return false   返回状态与提示
	 *        true     返回true
	 */
	protected function forgetFormMobile()
	{
		if( IS_POST ){
			$data = I('post.');
			$user = M('user');
			$rules = [
				['user_mobile','require', '手机号码不能为空'],
				['new_pass','require','密码不能为空'],
				['mobile_code','require','验证码不能为空'],
				// 验证确认密码是否和密码一致
				['re_pass','new_pass','请确认两次密码是否一致！',0,'confirm'],
			];
			//验证表单数据
			if (!$user->validate($rules)->create($data)) {
				$this->ajaxReturnStatus(1001, $user->getError());
			} else {
				//检查手机合法性
				if ($this->checkMobile($data['user_mobile'])) {
					$re = $user->field('id')->where('user_mobile=' . $data['user_mobile'])->find();
					//验证手机号码是否存在数据库
					if (!$re) {
						$this->ajaxReturnStatus(1002, '手机号码不存在！请检查');
					} else {
						//验证手机验证码是否正确
						if ($this->checkSms($data['mobile_code'])) {
							//验证密码合法性
							if( $this->checkPwd($data['new_pass']) ){
								//执行修改密码
								$res = $user->where('id=' . $re['id'])->setField('user_pass', hash_string($data['new_pass']));
								if ($res !== false) {
									$this->ajaxReturnStatus(0000, '找回密码成功!立即去登录');
								} else {
									$this->ajaxReturnStatus(1000, '系统繁忙!请稍后再试');
								}
							}
						}
					}
				}
			}
		}else{
			//输出模板
			$this->display();
		}

	}

	/*
	 * 验证手机号格式是否合法
	 * @return false   返回状态与提示
	 *        true     返回true
	 */
	public function checkMobile( $mobile )
	{
		$bool = preg_match('/^1[345789]+\d{9}$/', $mobile);
		if( empty($bool) ){
			$this->ajaxReturnStatus(1000, '手机号码格式不正确'); //存在
		}
		return true;
	}


	/*
	 * 验证密码格式是否合法
	 * @return false   返回状态与提示
	 *        true     返回true
	 */
	protected function checkPwd( $pass)
	{
		//正则 已字母开头，长度最少为6位，最大20位
		$bool = preg_match( '/^[a-zA-Z]\w{5,19}/',$pass);
		if( empty($bool) ){
			$len = strlen($pass  );
			if( $len < 6 ){
				$this->ajaxReturnStatus(1000, '密码长度至少6位'); //存在
			}elseif( $len > 20 ){
				$this->ajaxReturnStatus(1000, '密码长度超过限制'); //存在
			}
			$this->ajaxReturnStatus(1000, '密码格式不正确'); //存在
		}
		return true;
	}
    /*
         * ajax验证公司名称是否已存在
         * @method  post
         * @param  $company_name 用户名
         * @return json  返回状态与提示
         *
         */
    public function ajaxCheckCompanyName()
    {
        $userCompany = M('user_company');
        $company_name = I('post.company_name');
        //$this->checkName($user_name);
        if(!isset($company_name)||empty($company_name)){
            $this->ajaxReturnStatus(1000, '公司名称不能为空'); //存在
        }
        $res = $userCompany->field('user_id')->where(['company_name'=>$company_name])->find();//查询用户名是否存在
        if ( $res ) {
            $this->ajaxReturnStatus(1000, '公司名称已经存在'); //存在
        } else {
            $this->ajaxReturnStatus(0000); //不存在
        }
    }
	/*
	 * ajax验证用户名是否已存在
	 * @method  post
	 * @param  $user_name 用户名
	 * @return json  返回状态与提示
	 *
	 */
	public function ajaxCheckUserName()
	{
		$user = M('user');
		$user_name = I('post.user_name');
		$this->checkName($user_name);
		$res = $user->field('id')->where(['user_name'=>$user_name])->find();//查询用户名是否存在
		if ( $res ) {
			$this->ajaxReturnStatus(1000, '用户名已存在'); //存在
		} else {
			$this->ajaxReturnStatus(0000); //不存在
		}
	}

	/*
	 * ajax验证手机号是否注册
	 * @method  post
	 * @param  $user_mobile 手机号码
	 * @return json  返回状态与提示
	 */
	public function ajaxCheckMobile()
	{
		$user = M('user');
		$mobile = floatval(I('user_mobile'));
		$type = I('type')?I('type'):0;
        $user_type = I('user_type');
        $user_type = ($user_type==2)?2:1;
		if($this->checkMobile($mobile)) {
			$res = $user->field('id')->where(['user_mobile' => $mobile,'user_type'=>$user_type])->find();//查询手机号是否存在
			if( $type ){
				if ($res) {
					$this->ajaxReturnStatus(0000); //存在
				} else {
					$this->ajaxReturnStatus(1000, '手机号码不存在!'); //存在
				}
			}else{
				if ($res) {
					$this->ajaxReturnStatus(1000, '手机号码已注册!'); //存在
				} else {
					$this->ajaxReturnStatus(0000); //不存在
				}
			}

		}
	}

	/*
	 *  注销
	 *  @return 跳转页面
	 */
	public function logout()
	{
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
		redirect(U('Home/Default/index'));
	}


	/*
	 *
	 * 第三方登录
	 *
	 **/

	public function loginByOauth($type = null)
	{
		empty($type) && $this->error('参数错误');

		//加载ThinkOauth类并实例化一个对象
		$oauth = ThinkOauth::getInstance($type);

		//跳转到授权页面
		redirect($oauth->getRequestCodeURL());
	}

	//qq回调地址
	public function qqReturn()
	{
		$code = I('code');
		$this->callback('qq',$code);
	}

	//新浪微博回调地址
	public function sinaReturn()
	{
		if( I('error_code') == 21330 ){
			redirect(U('Home/Default/index'));
		}
		$code = I('code');
		$this->callback('sina',$code);
	}

	//微信回调地址
	public function wechatReturn()
	{
		$code = I('code');
		$this->callback('wechat',$code);
	}

	//授权回调处理
	public function callback($type = null, $code = null)
	{
		(empty($type) || empty($code)) && $this->error('参数错误');

		//加载ThinkOauth类并实例化一个对象
		$sns = ThinkOauth::getInstance($type);

		//腾讯微博需传递的额外参数
		$extend = null;
		if ($type == 'tencent') {
			$extend = array('openid' => $this->_get('openid'), 'openkey' => $this->_get('openkey'));
		}

		$token = $sns->getAccessToken($code, $extend);

		//获取当前登录用户信息
		if (is_array($token)) {
			$userInfo = A('Login', 'Event')->$type($token);

//			echo("<h1>恭喜！使用 {$type} 用户登录成功</h1><br>");
//			echo("授权信息为：<br>");
//			dump($token);
//			echo("当前登录用户信息为：<br>");
//			dump($userInfo);
			//快捷处理
			//判断当前用户是否登录
			//未登录 则为第三方账号登录
			if( is_login() == 0 ){
				$this->oauthlogin($userInfo,$token);
			}else{
				//已登录 则是 账号绑定
				$this->oauthBind($userInfo,$token);
			}

		}
	}

	/*
	 * 第三方登录处理
	 */
	protected function oauthlogin($userInfo,$token)
	{
		//登录数据处理
		//增加用户快捷登录绑定表，数据提交到此表与用户id关联，做判断处理。此处代码省略。。。。
		$user = M('user');
		$open = M('user_oauth');
		$open_id = $token['openid'];
		$type = strtolower($userInfo['type']);
		$res= $open->field('user_id')->where(['type'=>$type,'open_id'=>$open_id])->find();
		if( $res ){
			//找到user_id数据 说明绑定过账号 则执行登录
			$this->addUserToSession(intval($res['user_id']));
			$cache[$type]=$token;
			S($this->ssid.'open',$cache,7200);
			redirect(U('Home/Default/index'));
			exit();
		}else{
			//没找到user_id数据 说明第一次登录 则执行跳转创建账号 或 绑定已有账号
			//将第三方登录数据写入缓存
			$oauthData = [
				'type'=>$type,
				'open_id'=>$token['openid'],
				'nick'=> $this->filter(strFilter($userInfo['nick'])),//过滤特殊昵称字符
				'avator'=>$userInfo['head']
			];
			S($this->ssid.'oauth',$oauthData,7200);
			$this->redirect('create');
			exit();
		}
	}

	/**
	 * $str  过滤微信昵称
	 **/
	public function filter($str) {
		if($str){
			$name = $str;
			$name = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '', $name);
			$name = preg_replace('/xE0[x80-x9F][x80-xBF]‘.‘|xED[xA0-xBF][x80-xBF]/S','?', $name);
			$return = json_decode(preg_replace("#(\\\ud[0-9a-f]{3})#ie","",json_encode($name)));
			if(!$return){
				return $this->jsonName($return);
			}
		}else{
			$return = '';
		}
		return $return;
	}

	/*
	 * 第一次第三方登录 账号创建或绑定处理
	 *
	 */
	public function create()
	{
		$oauth = S($this->ssid.'oauth');
		!$oauth && $this->error('非法操作!', U('Home/Default/index'),2);
		$type=I('get.type')?I('get.type'):1;
		$typeStr = [
			'qq'        => 'QQ',
			'wechat'    => '微信',
			'sina'      => '新浪微博'
		];

		$this->assign('nick',$oauth['nick']);
		$this->assign('type',$typeStr[$oauth['type']]);
		$this->assign('avator',$oauth['avator']);
		if( $type == 1 ){
			$this->display('unite_no_normal');
		}elseif( $type == 2 ){
			$this->display('unite_no_company');
		}elseif( $type == 3 ){
			$this->display('unite_yes_normal');
		}elseif( $type == 4 ){
			$this->display('unite_yes_company'); //绑定
		}
	}

	public function doCreate()
	{
		$oauth = S($this->ssid.'oauth');
		$type = I('get.type')?I('get.type'):1;
		//绑定账号处理
		if( I('get.act') == 'bd' ){
			$user_name = I('post.user_name');
			$user_pass = I('post.user_pass');
			$user = M('user');
			if( is_numeric($user_name) ){
                $info = $user->field('id,user_pass,avator')->where(['user_mobile'=>$user_name])->find();
            }else{
                $info = $user->field('id,user_pass,avator')->where(['user_name'=>$user_name])->find();
            }
			if( $info ){
				//检查用户名
				$this->checkName($user_name);
				//验证密码
				if(!hash_check($user_pass,$info['user_pass'])){
					$this->ajaxReturnStatus(1001, '您输入的密码不正确');
				}
				//检测该账号是否已绑定该平台;
				$re = D('user_oauth')->where(['user_id'=>$info['id'],'type'=>$oauth['type']])->find();
				$re && $this->ajaxReturnStatus(1002, '您的账号已绑定过'.$oauth['type'].'平台账号!不能进行再次绑定!请您先解绑!');

				//如果用户头像为空  则将第三方头像设为头像
				empty($info['avator']) && $user->where(['id'=>$info['id']])->setField('avator',getUrlImages($oauth['avator'],'User/avator'));

				//执行登录 并绑定账号
				$data['user_id'] = $info['id'];
				$data['type'] = $oauth['type'];
				$data['open_id'] = $oauth['open_id'];
				$re = D('user_oauth')->data($data)->add();
				//dump($info);
				if( $re ){
					//清除第三方登录临时缓存数据
					S($this->ssid.'oauth',null);
					//执行登录
					$this->addUserToSession($info['id']);
					$this->ajaxReturnStatus(0000, '绑定账号成功!');
				}else{
					$this->ajaxReturnStatus(1000, '绑定失败!');
				}
			}else{
				$this->ajaxReturnStatus(1000, '用户不存在!');
			}

			//创建新账号处理
		}elseif( I('get.act') == 'create' ){
			$data = I('post.');
			//检查用户名合法性
			if($this->checkName($data['user_name'])){
				$user = M('user');
				$re = $user->field('id')->where([ 'user_name' => $data['user_name'] ])->find();
				if( !$re ){
					//检查手机号码格式
					if( $this->checkMobile( $data['user_mobile'] ) ){
						$res = $user->field('id')->where(['user_mobile'=>$data['user_mobile']])->find();//查询手机号是否存在
						if (!$res) {
							//检查短信验证
							if($this->checkSms($data['mobile_code'])){
								//检查密码格式
								if( $this->checkPwd($data['user_pass']) ){
									$data['user_pass'] = hash_string($data['user_pass']);//加密
									$data['user_type'] = $type == 2?2:1; //个人用户
									$data['nick_name'] = $type == 2?$data['company_name']:$data['user_name'];
									$data['user_ip'] = get_client_ip(); //获取客户端ip
									$data['avator'] = getUrlImages($oauth['avator'],'User/avator');//获取第三方头像保存到本地
									//开启事务处理
									$user->startTrans();
									//插入用户主表 获取用户id;
									$id = $user->add($data);

									//账号类型不同资料
									if( $type == 1 ){
										$normal = M('user_normal');
										$dataNormal = [
											'user_id'   => $id,
											'last_ip'   => $data['user_ip'],
										];
										$nId = $normal->add($dataNormal);
									}else{
										$company = M('user_company');
										$dataCompany = $data;
										$dataCompany['user_id'] = $id;
										$nId = $company->add($dataCompany);
									}
									//第三方账号信息
									$oau['user_id'] = $id;
									$oau['type'] = $oauth['type'];
									$oau['open_id'] = $oauth['open_id'];
									$oauId = D('user_oauth')->data($oau)->add();

									if( $id && $nId && $oauId ){
										$user->commit();
										$this->addUserToSession($id);
										$this->ajaxReturnStatus(0000 , '注册成功!');
									}else{
										$user->rollback();
										$this->ajaxReturnStatus(1000 , '注册失败!请重试');
									}
								}
							}else{
								$this->ajaxReturnStatus(1000, '验证码错误!'); //存在
							}
						}else{
							$this->ajaxReturnStatus(1000, '手机号码已注册!'); //存在
						}
					}
				}else{
					$this->ajaxReturnStatus(1000, '用户名已存在');
				}
			}

		}
	}

	/*
	 * 账号绑定
	 *
	 */
	public function bind($type = null)
	{
		if( empty($type) ){
			$type = I('type')?strtolower(I('type')):null;
		}
		empty($type) && $this->error('参数错误');

		//加载ThinkOauth类并实例化一个对象
		$oauth = ThinkOauth::getInstance($type);

		//跳转到授权页面
		redirect($oauth->getRequestCodeURL());
	}

	/*
	 * 第三方账号绑定处理
	 */
	protected function oauthBind($userInfo,$token)
	{
		!is_login() && redirect(U('Home/Account/login'));
		$oauth = M('user_oauth');
		//先查询一次
		$data['open_id'] = $token['openid'];
		$data['type'] = strtolower($userInfo['type']);
		$data['user_id'] = session('userId');
		$data['status'] = 0;
		$re = $oauth->field('id')->where(['open_id'=>$data['open_id'],'user_id'=>$data['user_id']])->find();
		$typeAll = [
			'qq'=>'QQ',
			'wechat'=>'微信',
			'sina'=>'新浪',
		];
		if( $re ){
			//$this->ajaxReturnStatus(1001, '您的'.$typeAll[$data['type']].'账号已绑定!请不要重复操作');
			$this->redirect('Home/User/bindAccount',['act'=>2,'msg'=>'您的'.$typeAll[$data['type']].'账号已绑定!请不要重复操作']);
		}
		//不存在再写入数据
		$res = $oauth->data($data)->add();
		if( $res ){
			$open = S($this->ssid.'open');
			if(!array_key_exists($data['type'],$open)){
				$open[$data['type']] = $token;
			}
			S($this->ssid.'open',$open,7200);
			//$this->ajaxReturnStatus(0000, $typeAll[$data['type']].'账号绑定成功');
			$this->redirect('Home/User/bindAccount',['act'=>1,'msg'=>'绑定成功!']);
		}else{
			//$this->ajaxReturnStatus(1000, $typeAll[$data['type']].'账号绑定失败');
			$this->redirect('Home/User/bindAccount',['act'=>2,'msg'=>$typeAll[$data['type']].'账号绑定失败']);
		}
	}


	/*
	 * 注册成功
	 */
	public function register_success()
	{
		$this->display();
	}

	/*
	 * 注册成功
	 */
	public function forget_paw_success()
	{
		$this->display();
	}


	//ajax测试
    public function ajax_test(){
        header('Content-Type:application/json; charset=utf-8');
	    $get=I('get.');
	    $phone=$get['phone'];
	    $password=$get['password'];

	    if(!$phone) die(json_encode(['error'=>1,'msg'=>'手机号码不能为空']));
        if(!$password) die(json_encode(['error'=>2,'msg'=>'密码不能为空']));

        if($phone!='18820188419')  die(json_encode(['error'=>3,'msg'=>'没有此用户']));
        if($phone!=123456)  die(json_encode(['error'=>4,'msg'=>'密码错误']));

        die(json_encode(['error'=>0,'msg'=>'登录成功']));
    }


}