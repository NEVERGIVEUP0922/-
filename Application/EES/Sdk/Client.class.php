<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-12-28
 * Time: 17:38
 */
namespace EES\Sdk;

use EES\Sdk\Curl;

class Client
{
    //客户端对象实例
    protected $client;

    //api接口地址
    protected $host = 'http://192.168.5.199/';

    //请求的服务名称
    protected $service;

    //客户端key
    protected $app_key = 'shop';

    //客户端secret
    protected $app_secret = '9fab520d080b120a70d403b575f4c97f';

    //请求参数池
    protected $data = [];

    //结果返回方式 支持json/array  默认json 其他自由扩展
    protected $parser = 'array';


    /**
     * 创建一个接口实例，注意：不是单例模式
     *
     * @return $this
     */
    public static function create()
    {
        return new self();
    }

    protected function __construct()
    {
        $this->client = Curl::create();

    }

    /**
     * 设置请求地址
     *
     * @param string $host
     * @return $this
     */
    public function withHost( $host )
    {
        $this->host = $host;

        return $this;
    }

    /**
     * 设置将在调用的接口服务名称，如：Default.Index
     *
     * @param string $service 接口服务名称
     * @return $this
     */
    public function withService( $service )
    {
        $this->service = $service;

        return $this;
    }

    /**
     * 设置接口参数，此方法是唯一一个可以多次调用并累加参数的操作
     *
     * @param string|array $name 参数名字|参数数组
     * @param string       $value 值
     * @return $this
     */
    public function setData( $name=null, $value = '' )
    {
        if ( is_array( $name ) ) {
            $this->data = array_merge( $name, $this->data );
        } else {
            $this->data[ $name ] = $value;
        }


        return $this;
    }


    /**
     * 设置结果解析方式
     *
     * @param string $parse
     * @return $this
     */
    public function withParser( $parse )
    {

        $this->parser = $parse;

        return $this;
    }

    public function send()
    {
        $token = $this->getAccessToken();
        if(!$token){
            return false;
        }
        $this->client->reset();

        $res = $this->client->withHost( $this->host )
            ->withParams( 'service', $this->service )
            ->withParams('access_token',$token )
            ->withParams( $this->data )
            ->withMethod( 'post' )
            ->request();
        if( $res === false ){
            return false;
        }
        $parser = 'to'.ucfirst(strtolower($this->parser));
        return  $this->$parser( $res );
    }

    protected function toJson( $apiResult )
    {
        return $apiResult;
    }

    protected function toArray( $apiResult )
    {
        return json_decode( $apiResult, true );
    }

    /*
     * 获取AccessToken令牌
     */
    public function getAccessToken()
    {
        $oldT = M('sys_set')->field('value')->where(['class'=>'ees', 'code'=>'token'])->find();
        $oldToken = $oldT['value'];
        if( !$oldToken ){
            return  $this->doGetAccessToken();
        }
        $oldArr = $this->toArray( $oldToken );
        $expires = (int)$oldArr['data']['expires_in'];
        $endTime = $oldArr['timestamp']+$expires;
        if( $expires > 0 && (time() >= $endTime ) ){
            return  $this->doGetAccessToken();
        }
        return $oldArr['data']['access_token'];
    }

    /*
     * 执行从服务端获取AccessToken
     *
     */
    protected function doGetAccessToken()
    {
        $this->client->reset();
        $res = $this->client->withHost( $this->host )
            ->withParams( 'service', 'App.Token.GetAccessToken' )
            ->withParams( 'app_key', $this->app_key )
            ->withParams('app_secret', $this->app_secret)
            ->withMethod( 'post' )
            ->request();
        $arr = $this->toArray( $res );
        if( $res === false || empty( $res )){
            return false;
        }
        if( $arr['status'] == false  ){
            exit($res);
        }
        unset( $arr['debug'] );
        M('sys_set')->where(['class'=>'ees', 'code'=>'token'])->save(['value'=>json_encode($arr)]);
        return $arr['data']['access_token'];

    }

}