<?php
// +----------------------------------------------------------------------
// | FileName:   UbaVisitRefferModel.class.php
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date:  2018-03-06 15:27
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Model;


class UbaVisitRefferModel extends UbaModel
{
    protected $tableName = 'visit_reffer';

    protected $dbName = 'uba';

    //来源域名识别列表  暂时就先随便写了几个
    protected $refferDomainList = [
        'www.baidu.com'=>'百度搜索',
        'www.so.com'=>'360搜索',
        'www.google.com'=>'google',
        'www.sogou.com'=>'搜狗搜索',
    ];

    public function getRefferLinkId( $link_url )
    {
        $refferLinksModule = [
            'link_url'=>$link_url,
        ];

        $linkEx = $this->checkLinksExists( $link_url );
        if( $linkEx ){
			$this->where(['link_url'=>$link_url])->save( $refferLinksModule );
            $this->updateLinkCount( $link_url );
            return $linkEx['_id'];
        }else{
            $refferLinksModule = $this->getRefferSignNameAndSignType( $refferLinksModule );
            $refferLinksModule['create_time'] = (string)time();
            $refferLinksModule['count'] = 1;
            $addId = $this->add( $refferLinksModule );

            return $addId?$addId->__toString():$addId;
        }
    }

    //获取来源链接所属类型与所属分类名称
    protected function getRefferSignNameAndSignType( $linkData )
    {
        if(empty( $linkData ) || $linkData['link_url'] === '' ){
            $linkData['link_sign'] = '直接访问';
            $linkData['link_type'] = 100;//直接访问
        }else{
			$parse = parse_url($linkData['link_url']);
            if ( $parse['host'] == $_SERVER['HTTP_HOST'] ){
                $linkData['link_sign'] = '站内访问';
                $linkData['link_type'] = 200;//站内访问
            }else{
                if( in_array( $parse['host'], $this->refferDomainList ) ){
                    $linkData['link_sign'] = $this->refferDomainList[$parse['host']];
                    $linkData['link_type'] = 300;//外部搜索等
                }else{
                    $linkData['link_sign'] = '未知来源';
                    $linkData['link_type'] = 0;//未知来源
                }
            }
        }
        return $linkData;
    }

    public function updateLinkCount( $link_url )
    {
        return $this->where(['link_url'=>$link_url])->setInc('count',1);
    }

    public function checkLinksExists( $link_url)
    {
        return $this->where(['link_url'=>$link_url])->find();
    }

    public function updateLinkSumCount( $link_id )
    {
        return $this->where(['_id'=>new \MongoId($link_id)])->setInc('count',1);
    }

}