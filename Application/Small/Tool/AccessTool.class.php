<?php

// +----------------------------------------------------------------------
// | FileName:   OauthModel.class.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/10 15:51
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Small\Tool;


trait AccessTool
{

    /**
     * @desc 生成open_key
     *
     */
    public function createOpenKey($appid,$secret,$key='xcx'){
        $str=$appid.$secret.$key;
        $str=$this->strSort($str);
        return md5($str);
    }

    /**
     * @desc 字符串排序
     *
     */
    public function strSort($str){
        $new_str='';
        $arr=str_split($str);
        sort($arr);
        $new_str=implode('',$arr);
        return $new_str;
    }

    /**
     * @desc unicode解码
     *
     */
    function unicode_decode($name){
        $json = '{"str":"'.$name.'"}';
        $arr = json_decode($json,true);
        if(empty($arr)) return '';
        return $arr['str'];
    }

    /**
     * 最简单的XML转数组
     * @param string $xmlstring XML字符串
     * @return array XML数组
     */
    function simplest_xml_to_array($xmlstring) {
        return json_decode(json_encode((array) simplexml_load_string($xmlstring)), true);
    }

    /**
     * xml解析数组
     * @param string $xmlstring XML字符串
     * @return array XML数组
     */
    public function xmlParseArr($xml){
        $xmls_arr=$this->xml_to_array($xml);

        foreach($xmls_arr['xml'] as $k=>$v){
            $one='';
            if(strpos($v,'CDATA')!==false){
                preg_match('/CDATA\[([^\]]+)\]/',$v,$one);
                $xmls_arr['xml'][$k]=$this->unicode_decode($one[1]);//中文
            }
        }
        return $xmls_arr['xml'];
    }

    function xml_to_array( $xml )
    {
        $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
        if(preg_match_all($reg, $xml, $matches))
        {
            $count = count($matches[0]);
            $arr = array();
            for($i = 0; $i < $count; $i++)
            {
                $key= $matches[1][$i];
                $val = $this->xml_to_array( $matches[2][$i] );  // 递归
                if(array_key_exists($key, $arr))
                {
                    if(is_array($arr[$key]))
                    {
                        if(!array_key_exists(0,$arr[$key]))
                        {
                            $arr[$key] = array($arr[$key]);
                        }
                    }else{
                        $arr[$key] = array($arr[$key]);
                    }
                    $arr[$key][] = $val;
                }else{
                    $arr[$key] = $val;
                }
            }
            return $arr;
        }else{
            return $xml;
        }
    }



}