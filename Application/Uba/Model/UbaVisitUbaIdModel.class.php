<?php
// +----------------------------------------------------------------------
// | FileName:   UbaVisitUbaIdModel.class.php
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date:  2018-03-06 17:05
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Model;

class UbaVisitUbaIdModel extends UbaModel
{
    protected $tableName = 'visit_uba_id';

    protected $dbName = 'uba';

    public function updateUbaId( $uba_id )
    {
        $data = $this->checkExists($uba_id);
        if( $data ){
           return $this->where(['_id'=>new \MongoId($data['_id'])])->setInc('count',1);
        }else{
            return $this->add(['uba_id'=>$uba_id, 'count'=>0])?true:false;
        }
    }

    public function checkExists( $uba_id )
    {
        return  $this->where(['uba_id'=>$uba_id])->find();
    }
}