<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-12-28
 * Time: 17:39
 */
namespace EES\Sdk;

class Curl
{
    /**
     * 请求地址
     */
    protected $host;

    /**
     * 请求源地址
     */
    protected $oriUrl;

    /*
     * 请求参数池
     */
    protected $params = [];

    /*
     * 请求方式 默认get
     */
    protected $method = 'get';

    /*
     * 请求超时时间 单位毫秒
     */
    protected $timeoutMs;


    protected $isSign = true;

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
        $this->host = '';

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
     * 设置接口参数，此方法是唯一一个可以多次调用并累加参数的操作
     *
     * @param string|array $name 参数名字|参数数组
     * @param string       $value 值
     * @return $this
     */
    public function withParams( $name, $value = '' )
    {
        if ( is_array( $name ) ) {
            $this->params = array_merge( $name, $this->params );
        } else {
            $this->params[ $name ] = $value;
        }

        return $this;
    }

    /**
     * 设置请求方式
     *
     * @param string $method 请求方式
     * @return $this
     */
    public function withMethod( $method )
    {
        $this->method = $method;

        return $this;
    }

    /**
     * 设置超时时间，单位毫秒
     *
     * @param int $timeoutMs 超时时间，单位毫秒
     * @return $this
     */
    public function withTimeout( $timeoutMs )
    {
        $this->timeoutMs = (float)$timeoutMs;

        return $this;
    }

    /**
     * 重置，将请求地址、请求参数、请求超时进行重置，便于重复请求
     *
     * @return $this
     */
    public function reset()
    {
        $this->host = '';
        $this->params = [];
        $this->method = '';
        $this->timeoutMs = 3000;


        return $this;
    }

    public function request()
    {
        $this->parserForArray();

        $this->isSign && $this->creatSign();
        $urlArr = parse_url( $this->host );
        $this->oriUrl = $urlArr[ 'scheme' ] . '://' . $urlArr[ 'host' ];
        if ( in_array( $this->method, [ 'get', 'post', 'put', 'delete' ] ) ) {
            $func = $this->method . 'Request';

            return $this->$func();
        }
    }

    /**
     * 发起get请求
     */
    public function getRequest()
    {
        return $this->doRequest( 0 );
    }

    /**
     * 发起post请求
     */
    public function postRequest()
    {
        return $this->doRequest( 1 );
    }

    /**
     * 发起put请求
     */
    public function putRequest()
    {
        return $this->doRequest( 2 );
    }

    /**
     * 发起delete请求
     */
    public function deleteRequest()
    {
        return $this->doRequest( 3 );
    }

    /**
     * 处理发起非get请求的传输数据
     */
    public function dealPostData()
    {
        $o = null;
        foreach ( $this->params as $k => $v ) {
            $o .= "$k=$v&";
        }
        $data = substr( $o, 0, -1 );
        return $data;
    }

    protected function parserForArray()
    {
        $params = $arr = [];
        foreach ( $this->params as $k => $v ) {
            if( is_array($v) ){
                $arr = $this->parseParams( $k, $v );
            }else{
                $params[$k]=$v;
            }
        }

        $this->params = array_merge( $params, $arr );
    }

    protected function parseParams( $name,&$data )
    {
        $params = [];
        foreach( $data as $k=>$v ){
            if( is_array($v) ){
                $arr = $this->parseParams( "{$name}[$k]", $v );
                $params = array_merge( $params, $arr );
            }else{
                $key= "{$name}[$k]";
                $params[$key] = $v;
            }
        }
        return $params;
    }
    /**
     * 发起请求
     *
     * @param int $isPost 请求方式
     * @return mixed $rs
     */
    protected function doRequest( $isPost = 0 )
    {
        $ch = curl_init();//初始化curl
        curl_setopt( $ch, CURLOPT_URL, $this->host );
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT_MS, $this->timeoutMs );
        // 来源一定要设置成来自本站
        curl_setopt( $ch, CURLOPT_REFERER, $this->oriUrl );

        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        if ( $isPost === 0 ) curl_setopt( $ch, CURLOPT_POST, $isPost );
        if ( !empty( $this->params ) ) {
            $this->params = $this->dealPostData();
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $this->params );
        }

        $rs = curl_exec( $ch );//运行curl
        curl_close( $ch );

        return $rs;
    }

    protected function creatSign()
    {
        if (empty($this->params)) {
            return;
        }
        $this->params['sign'] = $this->encryptAppKey($this->params);
    }

    protected function encryptAppKey($params) {
        ksort($params);
        $paramsStrExceptSign = '';
        foreach ($params as $val) {
            $paramsStrExceptSign .= $val;
        }

        return md5($paramsStrExceptSign);
    }

}