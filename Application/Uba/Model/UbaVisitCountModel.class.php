<?php
// +----------------------------------------------------------------------
// | FileName:   UbaCountModel.class.php
// +----------------------------------------------------------------------
// | Dscription:   
// +----------------------------------------------------------------------
// | Date:  2018-03-06 14:28
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace Uba\Model;

class UbaVisitCountModel extends UbaModel
{
    protected $tableName = 'visit_count';

    protected $dbName = 'uba';

    protected $count_type = ['pv','uv','ip'];

    public function updateCount( $type, $step=1, $date='', $time='' )
    {
        if( !in_array(strtolower($type), $this->count_type) ){
            return null;
        }
        if(empty( $date )){
            $date = date( 'Y-m-d' );
        }else{
            $date = date( 'Y-m-d', strtotime($date) );
        }
        if( empty( $time ) ){
            $time = (int)date( 'H' );
        }else{
            $time = (int)date( 'H', strtotime($time) );
        }
        $step = (int)$step;

        $exis = $this->checkTodayDataExists($type, $date );
        if( !$exis ){
            $count = [ 0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0, 9=>0, 10=>0, 11=>0, 12=>0,
                       13=>0, 14=>0, 15=>0, 16=>0, 17=>0, 18=>0, 19=>0, 20=>0, 21=>0, 22=>0, 23=>0
            ];
            $count[$time] = $step;
            $count = array2object( $count );
            $add = $this->add([
                'date'=>$date,
                'count_type'=>$type,
                'count'=>$count,
                'sum'=>$step,
            ]);
            return $add?$add->__toString():$add;
        }else{
            $count = object2array( $exis['count'] );
            $count[$time] += (int)$step;
            $sum = array_sum( $count );
            $count = array2object( $count );
            if( $this->where(['date'=>$date, 'count_type'=>$type ])->save(['count'=>$count, 'sum'=>$sum]) !== false ){
               return $exis['_id'];
            }else{
                return false;
            }
        }
    }

    public function checkTodayDataExists( $type )
    {
        return $this->where(['date'=>date( 'Y-m-d' ), 'count_type'=>$type])->find();
    }

    public function addUvOrIpLogs( $type, $value )
    {
        return  M('visit_uv_ip_log','dx_', 'DB_MONGO')->add(['type'=>$type, 'value'=>$value,'date'=>date('Y-m-d')]);
    }

    public function checkUvOrIpLogsExistsForToday( $type, $value )
    {
        $ex = M('visit_uv_ip_log','dx_', 'DB_MONGO')->where(['type'=>$type, 'value'=>(string)$value,'date'=>date('Y-m-d')])->limit(1)->select();
        return $ex?$ex:false;
    }


}