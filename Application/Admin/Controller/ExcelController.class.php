<?php

// +----------------------------------------------------------------------
// | FileName:   ProductController.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/28 11:00
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
namespace  Admin\Controller;

class ExcelController extends AdminController
{
	public $_model;
	
	protected function _initialize()
	{
		parent::_initialize(); // TODO: Change the autogenerated stub
		$this->_model = D('product');
        if(!vendor('PHPExcel/PHPExcel'))return ['error'=>1,'msg'=>'phpexcel文件加载错误'];
	}

    /**
     * @desc excel文件读取
     *
     */
    public function excelRead($file){
        if(!file_exists($file)) return ['error'=>1,'msg'=>'文件路径不对'];

        $objPHPExcel = \PHPExcel_IOFactory::load($file);
        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
        if(!$sheetData) return ['error'=>1,'msg'=>'内容错误'];

        return ['error'=>0,'data'=>['list'=>$sheetData]];
    }

    /**
     *
     * @desc 文件上传
     * $post[path] = '',二级目录
     *
     */
    public function fileUpload(){
        $result=fileUpload();
        die(json_encode($result));
    }

    /**
     * @desc excel产品文件写入
     *
     */
    public function poductExcelWrite($path,$data,$count,$config){
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);

        //头部信息写入
        $activeSheet = $objPHPExcel->getActiveSheet();
        foreach($config['POSITION'] as $k=>$v){
            $titleConfig[]=$v."2:".$v.'3';
        }
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '玖隆商品录入');
        foreach($config['POSITION_DESCRIBE'] as $k=>$v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($config['POSITION'][$k].'2', $v);
        }
        $activeSheet->mergeCells("A1:".end($config['POSITION']).'1');//合并单元格
        foreach($titleConfig as $k=>$titlePosition){
            $objPHPExcel->getActiveSheet()->getStyle($titlePosition)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//文字居中
            $activeSheet->mergeCells($titlePosition);//合并单元格
            $objPHPExcel->getActiveSheet()->getStyle($titlePosition)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
        }

        //产品信息写入
        foreach($data as $k=>$v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($k, $v);
        }
        $position_merge=$config['POSITION'];
        foreach($position_merge as $k=>$v){
            if(in_array($v,array_values($config['POSITION_OUT_PRICE']))) continue;
            for($i=4;$i<=$count+3;$i+=3){
                $j=$i+2;
                $onePosition=$v.$i.':'.$v.$j;//合并单元格
                $objPHPExcel->getActiveSheet()->getStyle($onePosition)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//文字居中
                $activeSheet->mergeCells($onePosition);//合并单元格
                $objPHPExcel->getActiveSheet()->getStyle($onePosition)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
            }
        }

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


    /**
     * @desc excel一般文件写入
     *
     */
    public function poductExcelWriteSample($path,$data){
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);

        //产品信息写入
        foreach($data as $k=>$v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($k, $v);
        }

        $objPHPExcel->getActiveSheet()->setTitle('Simple');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $temp_path='Uploads/excelout';
        if(!file_exists($temp_path)){
            mkdir($temp_path,0777,true);
        }
        $temp_path.=$path;
        $result=$objWriter->save($temp_path);
        return ['error'=>0,'data'=>['one'=>$temp_path]];
    }


    public function test(){
        $position=[
            'A'=>'index',
            'B'=>'p_sign',
            'C'=>'p_sign_new',
            'D'=>'fitemno',
            'E'=>'fitemno_duo',
            'F'=>'is_search_top',
            'G'=>'search_top',
            'H'=>'package',
            'I'=>'note',
            'J'=>'note_isShow',
            'K'=>'fitemno_access',
            'L'=>'voltage_input_start',
            'M'=>'voltage_input_end',
            'N'=>'voltage_output_start',
            'O'=>'voltage_output_end',
            'P'=>'current_start',
            'Q'=>'current_end',
            'R'=>'volume_length',
        ];

        $val_x_1000='LMNOPQ';

        $file="1.xls";
        $list=$this->excelRead($file);
        $data=$list['data']['list'];
        $sql_data=[];
        foreach($data as $k=>$v){
            if($k<4||$k%3!=1) continue;

            $one=[];
            $is_continue=1;
            foreach($v as $k2=>$v2){
                if($v2){
                    $is_continue=0;
                }

                if(strpos($val_x_1000,$k2)!==false){
                    $v2*=1000;
                }
                $one[$position[$k2]]=$v2;
            }
            if($is_continue) continue;
            $sql_data[]=$one;
        }
        $result=M('update','product_')->addAll($sql_data);
        echo substr(D()->getDbError(),0,100);
//        echo D()->getLastSql();
        print_r($result);
    }

    //更新dx_product_fitemno
    public function test2(){
        $product_update=M('update','product_')->field('p_sign,p_sign_new,fitemno,fitemno_duo')->select();
//        print_r($product_update);
        $insert_data=[];
        foreach($product_update as $k=>$v){
//            $one_person=M('user','sys_')->field('uid')->where(["femplno in (select vm from erp_product where fitemno in (select fitemno from dx_product where fitemno = '".$v['fitemno']."'))"])->find();
            $one_person=M('user','sys_')->field('uid')->where(["femplno in (select vm from erp_product where fitemno  = '".$v['fitemno']."')"])->find();
            if(!$v['p_sign_new']) continue;
            $insert_data[]=[
                'p_sign'=>$v['p_sign_new'],
                'fitemno'=>$v['fitemno'],
                'person_liable'=>$one_person['uid'],
            ];
            if(trim($v['fitemno_duo'])){
                $one= explode(';',$v['fitemno_duo']);
                foreach($one as $k2=>$v2){
                    $one_person2=M('user','sys_')->field('uid')->where(["femplno in (select vm from erp_product where fitemno  = '".$v2."')"])->find();
                    if($v2){
                        $insert_data[]=[
                            'p_sign'=>$v['p_sign_new'],
                            'fitemno'=>$v2,
                            'person_liable'=>$one_person2['uid'],
                        ];
                    }
                }
            }
        }
//        print_r($insert_data);die();

        $result=M('product_fitemno')->addAll($insert_data);
//        echo D()->getLastSql();
        echo substr(D()->getDbError(),0,100);
        print_r($result);
//        print_r($insert_data);

    }

    //顾客议价更新
    public function test3(){
        $file="2.xls";
        $list=$this->excelRead($file);
        $data=$list['data']['list'];
        print_r($data[1]);
        unset($data[1]);

//        print_r($data);
        $p_sign_delete=$p_sign_update=[];
        $sql_where='where 1=1 and ';
        foreach($data as $k=>$v){
            if(trim($v['F'])=='删除'){
                $one_product=M('product')->field('id')->where(['p_sign'=>$v['C']])->find();
                $p_sign_delete[]=[
                    'uid'=>$v['B'],
                    'p_sign'=>$v['C'],
                    'p_id'=>$one_product['id'],
                ];
                $sql_where.="or (uid=$v[B] and p_id=$one_product[id])";
            }
        }
        print_r($sql_where);
        print_r($p_sign_delete);
    }


    //更新dx_product_fitemno
    public function test4()
    {
        $file = "9.xls";
        $list = $this->excelRead($file);
        $data=$list['data']['list'];
        unset($data[0]);
        unset($data[1]);
        $sql_data=[];
        foreach($data as $k=>$v){
            $one=trim($v['C']);
            if($one){
                $one_arr=[];
                $one_arr=explode(';',$one);
                foreach($one_arr as $k2=>$v2){
                    $sql_data[]=[
                        'p_sign'=>trim($v['A']),
                        'fitemno'=>$v2,
                    ];
                }
            }
        }
        $result=M('product_fitemno_genduo')->addAll($sql_data);
        var_dump($result);
    }

    //更新fitemno_access
    public function test5(){
        $file = "fitemno_access20180702.xlsx";
        $list = $this->excelRead($file);
        $data=$list['data']['list'];
        $insert=[];
        foreach($data as $k=>&$v){
            if($v['B']&&$v['C']){

                if(strpos($v['C'],';')===false){
                    $v['C']=$v['B'] . '{}' . '---' . '{}'.$v['C'].'{}';
                }else{
                    $v['C']=$v['B'] . '{}' . '---' . '{}'.str_replace(';','{}',$v['C']).'{}';
                }

                $insert[]=[
                    'p_sign'=>$v['B'],
                    'fitemno_access'=>$v['C'],
                ];
            }
        }
        $result=M('product_fitemno_access')->addAll($insert);
        var_dump($result);
    }


}