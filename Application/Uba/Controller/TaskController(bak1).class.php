<?php
// +----------------------------------------------------------------------
// | FileName:   TaskController.class.php
// +----------------------------------------------------------------------
// | Dscription:   模拟请求类 模拟IP 模拟UA
// +----------------------------------------------------------------------
// | Date:  2018-03-08 10:49
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Controller;

use Think\Controller;

class TaskController
{
    protected static $instance;

    protected $config = [
        'url'=>'',
        'hreaders'=>[],
        'ip'=>'',
        'randIp'=>true,
        'ua'=>'',
        'randUa'=>true,
    ];

    private function __construct()
    {
    }

    public static function getInstance( )
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run($config, $num=1)
    {
        for( $i=0;$i<$num;$i++ ){
            $data = $this->runTask( $config );
            if( $data ){
                dd( '次数:'.($i+1).'<br> 网址:'.$data[0].'<br> 模拟ip:'.$data[1].'<br> 模拟UA:'.$data[2].'<br> 请求时间:'.$data[3] );
            }else{
                de( '配置参数错误' );
            }
        }
    }

    protected function runTask($config)
    {
        $this->config = array_merge( $this->config,$config );
        $url = $this->config['url'];

        if( empty($url) ){
            return false;
        }

        $headers = $this->config['hreaders'];
        if( $this->config['randIp'] === false ){
            $ip = $this->config['ip']?$this->config['ip']:get_client_ip();
            $headers['CLIENT-IP'] = $ip;
            $headers['X-FORWARDED-FOR'] = $ip;
            $headers['REMOTE-ADDR'] = $ip;
        }else{
            $randIpHeaders = $this->randIP();
            $ip = $randIpHeaders['CLIENT-IP'];
            $headers = array_merge( $headers, $randIpHeaders );
        }
        
        $headerArr = [];
        foreach( $headers as $n => $v ) {
            $headerArr[] = $n .':' . $v;
        }
        //ua
        if( $this->config['randUa'] === false ){
            $ua = $this->config['ua']?$this->config['ua']:"Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0";
        }else{
            $ua = $this->randUa();
        }

        //伪造cookie
//		$cookie = $this->createCookie();

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url); //要抓取的网址
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArr);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_REFERER, 0);  //模拟来源网址
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_USERAGENT, $ua); //模拟常用浏览器的useragent
//		curl_setopt($curl,CURLOPT_COOKIE,$cookie);//模拟Cookie;
		$tmpInfo = curl_exec($curl);
        if (curl_errno($curl)) {
            de( curl_error($curl) );
            file_put_contents('task.log', date('Y-m-d H:i:s')."Error: " . curl_error($curl).PHP_EOL, FILE_APPEND);
        } else {
            curl_close($curl);
        }
        return [$url, $ip, $ua,date('Y-m-d H:i:s') ];
    }


    public function randIP(){
        $ip_long = array(
            array('607649792', '608174079'), //36.56.0.0-36.63.255.255
            array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
            array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
            array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
            array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
            array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
            array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
            array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
            array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
            array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
        );
        $rand_key = mt_rand(0, 9);
        $ip= long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
        $headers['CLIENT-IP'] = $ip;
        $headers['X-FORWARDED-FOR'] = $ip;
        $headers['REMOTE-ADDR'] = $ip;
        return $headers;
    }

    public function randUa()
    {
        $uaList = [
            'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50',
            'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0;',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0.1) Gecko/20100101 Firefox/4.0.1',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_0) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11',
            'Mozilla/5.0 (Windows NT 5.2) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.122 Safari/534.30',
            'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.8) Gecko Fedora/1.9.0.8-1.fc10 Kazehakase/0.5.6',
            'Mozilla/5.0 (X11; Linux i686; U;) Gecko/20070322 Kazehakase/0.4.5',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.71 Safari/537.1 LBBROWSER',
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.1',
            'Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.2; .NET CLR 1.1.4322; .NET CLR 2.0.50727; InfoPath.2; .NET CLR 3.0.04506.30)',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.89 Safari/537.1',
            'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.0.12) Gecko/20070731 Ubuntu/dapper-security Firefox/1.5.0.12',
        ];
        $key = rand(0, count($uaList)-1);
        return $uaList[$key];
    }
}