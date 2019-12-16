<?php

// +----------------------------------------------------------------------
// | FileName:   AdminController.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/6 14:30
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace  Admin\Controller;
use Common\Controller\BaseController;
use EES\System\Redis;

class AdminController extends BaseController
{
    protected $liveTime='';//帐号生存时间
    protected function _initialize()
    {
        parent::_initialize();
        $userAccess=$this->userAccess();
        if($userAccess['error']!=0){
            if(IS_AJAX){
                die(json_encode($userAccess));
            }
            if($userAccess['error']==2){
                $this->redirect($userAccess['data']['templateFile']);
            } else {
                $this->assign('type','noPower');
                $this->display($userAccess['data']['templateFile']);
            }
            die();
        }
        $session=session();
        $adminFront=[
            'uid'=>$session['adminId'],
            'user_name'=>$session['adminInfo']['user_name'],
        ];
        $this->liveTime=C('ON_LINE_LIVE_TIME');//帐号生存时间
        $this->assign('adminFront',$adminFront);
    }

    protected function addAdminUserToSession( $id )
    {
        $admin = M('admin');
        //初始化
        session('adminId', null);
        session('adminInfo', null);
        session('adminFunctions', null);
        session('adminMenu', null);

//		$data['last_ip'] = get_client_ip();
//		$data['login_time'] = time();
//		$admin->where(['id'=>$id])->setField($data);

        $user=D('User')->userFunctions($id);
        if($user['error']!=0){
            session('adminId', null);
            session('adminInfo', null);
            session('adminFunctions', null);
            session('adminMenu', null);
            die(json_encode($user));
        }

        session('adminFunctions', $user['data']['function'], $this->liveTime);
        session('adminInfo', $user['data']['adminInfo'], $this->liveTime);
        session('adminId', $user['data']['adminInfo']['uid'],$this->liveTime);

        $adminMenu=D('User')->adminMenu();
        if($adminMenu['error']!=0){
            session('adminId', null);
            session('adminInfo', null);
            session('adminFunctions', null);
            session('adminMenu', null);
            die(json_encode($adminMenu));
        }
        session('adminMenu',$adminMenu['data'],$this->liveTime);

        //清除缓存中的错误次数
        $key = 'loginFalse:Admin' . $user['data']['user']['user_name'];
        S($key, null);
    }

    private function userAccess(){
        //ip限制
        $redis=Redis::getInstance();
        $ip_filter=$redis->sMembers('ip');
        $clientIp=get_client_ip();
        if(filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)!==false){//外网
            if(!empty($ip_filter)&&!in_array($clientIp,$ip_filter)){
                //                header("HTTP/1.0 404 Not Found");//使HTTP返回404状态码
                //                $this->display("Public:404");
                echo '你的ip：'.$clientIp.'<br/>';
                die('ip变更请联系管理员----');
            }
        }

        $method_name=MODULE_NAME;
        $action_name=CONTROLLER_NAME;
        $model_name=ACTION_NAME;
        $accessFile = APP_PATH.'/Admin/Conf/access.php';
        $access = include($accessFile);

        $key_method=strtolower($method_name);

        if(strtolower($key_method) ==='admin'&&strtolower($action_name) === 'index'&&strtolower($model_name)==='login'){
            if(C('ADMIN_TOKEN')!=I('get.token')&&!IS_AJAX){
                $this->redirect('Home/Default/index');
            }
        }

        //公共模块
        if($access[$key_method]['spread']==2) {
            return ['error'=>0,'msg'=>$key_method.'通过'];
        }else if($access[$key_method]['spread']==1){
            if($access[$key_method][$action_name]['spread']==2){
                return ['error'=>0,'msg'=>$key_method.'/'.$action_name.'/'.'通过'];
            }else if($access[$key_method][$action_name]['spread']==1){
                if($access[$key_method][$action_name][$model_name]['spread']!=0){
                    return ['error'=>0,'msg'=>$key_method.'/'.$action_name.'/'.$model_name.'/通过'];
                }
            }
        }

        $templateFile='Admin@Index:error';

        $url_path=I('path.');
        $certification=1;
        if(($url_path[0]=="Product"&&$url_path[1]=="productList")||($url_path[0]=="product"&&$url_path[1]=="productTextEditImages")){
            $certification=0;
        }
        //是否登录
        if(!session('adminId')&&$certification){
            $templateFile='Home/Default/index';
            return ['error'=>2,'msg'=>'登录信息错误','data'=>['templateFile'=>$templateFile]];
        }

        //私有模块
        $functionTree=session('adminFunctions');
        if($certification){
            if(!$functionTree[$key_method]['spread']){
                return ['error'=>1,'msg'=>'操作没有权限1','data'=>['templateFile'=>$templateFile]];
            }else if($functionTree[$key_method]['spread']==1){
                if(!$functionTree[$key_method][$action_name]['spread']){
                    return ['error'=>1,'msg'=>'操作没有权限2','data'=>['templateFile'=>$templateFile]];
                }else if($functionTree[$key_method][$action_name]['spread']==1){
                    if(!$functionTree[$key_method][$action_name][$model_name]['spread']){
                        return ['error'=>1,'msg'=>'操作没有权限3','data'=>['templateFile'=>$templateFile]];
                    }
                }
            }
        }


        return ['error'=>0,'msg'=>'通过'];
    }



}