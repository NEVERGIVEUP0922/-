<?php
// +----------------------------------------------------------------------
// | FileName:   UbaLinksModel.class.php
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date:  2018-03-06 14:07
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Model;


class UbaVisitLinksModel extends UbaModel
{
	protected $tableName = 'visit_links';

	protected $dbName = 'uba';

	public function getLinkId( $link_url, $link_name )
	{
		$linksModule = [
			'link_url'    => $link_url,
			'link_name'   => $link_name,
		];
		$linkEx      = $this->checkLinksExists( $link_url );
		if ( $linkEx ) {
			$this->where(['link_url'=>$link_url])->save( $linksModule );
			$this->updateLinkCount( $link_url );

			return $linkEx[ '_id' ];
		} else {
			$linksModule = [
				'link_url'    => $link_url,
				'link_name'   => $link_name,
				'count'       => 1,
				'create_time' => (string)time(),
			];
			$addId = $this->add( $linksModule );

			return $addId ? $addId->__toString() : $addId;
		}
	}

	public function updateLinkCount( $link_url )
	{
		return $this->where( [ 'link_url' => $link_url ] )->setInc( 'count', 1 );

	}

	public function checkLinksExists( $link_url )
	{
		return $this->where( [ 'link_url' => $link_url ] )->find();
	}

	public function updateLinkSumCount( $link_id )
	{
		return $this->where( [ '_id' => new \MongoId( $link_id ) ] )->setInc( 'count', 1 );
	}
	
}