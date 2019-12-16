<?php

// +----------------------------------------------------------------------
// | FileName:   LoginEvent.class.php
// +----------------------------------------------------------------------
// | Dscription:客户订单表格下载
// +----------------------------------------------------------------------
// | Date:  2018/05/18 22:16
// +----------------------------------------------------------------------
// | Author: kelly  <466395102@qq.com>
// +----------------------------------------------------------------------

namespace Home\Design;


class CustomerOrderExcelDesign implements ToolInterfaceDesign {

    public function action()
    {
        // TODO: Implement action() method.

        $path = APP_PATH. "/Home/Conf/orderExcel.php";
        $config = include($path);

        $path='orderExcel.xls';

        $this->result=$this->orderExcelWrite($path,$this->data,$config);
        return $this;
    }

    /**
    * @desc excel客户订单文件写入
    *
    */
    public function orderExcelWrite($path,$data,$config){
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);

        //头部信息写入
        $activeSheet = $objPHPExcel->getActiveSheet();

        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '我的报表');

        foreach($config['POSITION'] as $k=>$v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($v."2", $config['POSITION_DESCRIBE'][$k]);
        }
        $lineMerge=[];
        foreach($data['list'] as $k=>$v){
            if(!isset($lineMerge[$v['order_sn']])){
                $lineMerge[$v['order_sn']]=[
                    'line_start'=>$k+3,
                ];
            }else{
                $lineMerge[$v['order_sn']]['line_end']=$k+3;
            }
            foreach($v as $k2=>$v2){
                if(!isset($config['POSITION'][$k2])) continue;
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue($config['POSITION'][$k2].($k+3), $v2);
            }
        }

        //合并单元
        if($config['POSITION_MERGE']){
            foreach($lineMerge as $k=>$v){
                if(count($v)==1) continue;
                foreach($config['POSITION_MERGE']['position'] as $k2=>$v2){
                    $objPHPExcel->getActiveSheet()->mergeCells($v2.$v['line_start'].':'.$v2.$v['line_end']);//合并单元格A1:F1(起始坐标，结束坐标)
                }
            }
        }

        foreach($config['POSITION_COUNT'] as $k=>$v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($v."2", $config['POSITION_COUNT_DESCRIBE'][$k]);
        }

        $i=0;
        foreach($data['pSign_total'] as $k=>$v){
            foreach($v as $k2=>$v2){
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue($config['POSITION_COUNT'][$k2].($i+3), $v2);
            }
            $i++;
        }

        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($config['POSITION_COUNT_MONEY']['money_total'].'2', $config['POSITION_COUNT_MONEY_DESCRIBE']['money_total']);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($config['POSITION_COUNT_MONEY']['money_total'].'3', $data['money_total']);

        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($config['POSITION_COUNT_MONEY']['money_total_has_pay'].'2', $config['POSITION_COUNT_MONEY_DESCRIBE']['money_total_has_pay']);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($config['POSITION_COUNT_MONEY']['money_total_has_pay'].'3', $data['money_total_has_pay']);

        $objPHPExcel->getActiveSheet()->setTitle('Simple');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $temp_path='Uploads/excelout';
        if(!file_exists($temp_path)){
            mkdir($temp_path,0777,true);
        }
        $temp_path.='/'.md5(session_id()).'.xlsx';
        $result=$objWriter->save($temp_path);
        return ['error'=>0,'data'=>['one'=>$temp_path]];
    }


}
