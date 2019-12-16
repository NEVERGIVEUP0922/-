<?php

// +----------------------------------------------------------------------
// | FileName:   menu.php
// +----------------------------------------------------------------------
// | Dscription:
// +----------------------------------------------------------------------
// | Date:  2017/8/6 14:54
// +----------------------------------------------------------------------
// | Author: showkw <showkw@163.com>
// +----------------------------------------------------------------------
return [
    'POSITION_DESCRIBE'=>[//产品excel表头描述
          'index' => '序号',
          'p_sign' => '商城型号',
          'fitemno' => '默认关联ERP型号',
          'product_fitemno' => '多关联ERP型号',
          'is_search_top' => '头条（1）',
          'search_top' => '头条型号',
          'package' => '封装',//封装（sop16,sop8）
          'brand_id' => '品牌',//品牌
          'delivery' => '交期(天)',//交期(天)
          'pack_unit' => '包装形态(1盘装/2管装/3袋装/4托盘)',//包装形态(1盘装/2管装/3袋装/4托盘)
          'unit' => '单位',//单位
          'min' => '最小包装数(PCS)',//最小包装数(PCS)
          'fstcb'=>'标准成本',//标准成本
          'lft_num' => '价格区间开始',//价格区间开始
          'right_num' => '价格区间结束',//价格区间结束
          'price_ratio' => '价格比列',//价格比列

          'earnest_scale' => '订金比例（%）',//订金比例（%）
          'min_price' => '建议最低价（未税',//建议最低价（未税）
          'is_tax' => '进项(1 有/0 没)',//进项(1 有/0 没)
          'tax' => '开票税点(10-17)',//开票税点(10-17)
          'discount_num' => '折扣限（金额）',//折扣限（金额）
          'min_open' => '是否拆(1 拆/0 不拆)',//是否拆(1 拆/0 不拆)
          'cate_id' => '分类三级',// 分类三级

          'parameter' => '功能、参数简要描述',//功能、参数简要描述

          'show_site' => '展示状态/产品销售状态：1最新，2推荐，3特卖',//展示状态/产品销售状态：1最新，2推荐，3特卖
          'note' => '备注',//备注
          'note_isShow' => '备注是否显示',//备注是否显示
          'is_inquiry_table' => '需要报备表？(1 要/0 不要)',//需要报备表？(1 要/0 不要)
          'fitemno_access'=>'替代料号',//替代料号

          'voltage_input_start' => '电压(输入)V',//电压(输入)V
          'voltage_input_end' => '电压(输入)V',//电压(输入)V
          'voltage_output_start' => '电压(输出)V',//电压(输出)V
          'voltage_output_end' => '电压(输出)V',//电压(输出)V
          'current_start' => '/电流A',//电流A
          'current_end' => '电流A',//电流A
          'volume_length' => '体积',//体积长（毫米）
          'person_liable' => '产品负责人',//产品负责人

          'describe' => '产品描述',//产品描述
    ],
    'POSITION'=>[//商品批量录入的表格位置
        'index' => 'A',//序号
        'p_sign' => 'B',//商城型号
        'fitemno' => 'C',//默认关联ERP型号
        'product_fitemno' => 'D',//多关联ERP型号
        'is_search_top' => 'E',//头条（1）
        'search_top' => 'F',//头条型号
         'package' => 'G',//封装（sop16,sop8）
                 'brand_id' => 'H',//品牌
                 'delivery' => 'I',//交期(天)
                 'pack_unit' => 'J',//包装形态(1盘装/2管装/3袋装/4托盘)
                 'unit' => 'K',//单位
                 'min' => 'L',//最小包装数(PCS)
                 'fstcb'=>'M',//标准成本
                 'lft_num' => 'N',//价格区间开始
                 'right_num' => 'O',//价格区间结束
                 'price_ratio' => 'P',//价格比列

                 'earnest_scale' => 'Q',//订金比例（%）
                 'min_price' => 'R',//建议最低价（未税）
                 'is_tax' => 'S',//进项(1 有/0 没)
                 'tax' => 'T',//开票税点(10-17)
                 'discount_num' => 'U',//折扣限（金额）
                 'min_open' => 'V',//是否拆(1 拆/0 不拆)
                 'cate_id' => 'W',// 分类三级

                 'parameter' => 'X',//功能、参数简要描述

                 'show_site' => 'Y',//展示状态/产品销售状态：1最新，2推荐，3特卖
                 'note' => 'Z',//备注
                 'note_isShow' => 'AA',//备注是否显示
                 'is_inquiry_table' => 'AB',//需要报备表？(1 要/0 不要)
                 'fitemno_access'=>'AC',//替代料号

                 'voltage_input_start' => 'AD',//电压(输入)V
                 'voltage_input_end' => 'AE',//电压(输入)V
                 'voltage_output_start' => 'AF',//电压(输出)V
                 'voltage_output_end' => 'AG',//电压(输出)V
                 'current_start' => 'AH',//电流A
                 'current_end' => 'AI',//电流A
                 'volume_length' => 'AJ',//体积长（毫米）
                 'person_liable' => 'AK',//产品负责人

                 'describe' => 'AL',//产品描述
     ],

    'POSITION_OUT_PRICE'=>[
        'lft_num'=>'N',//价格计算
        'right_num'=>'O',//价格计算
        'price_ratio'=>'P',//价格计算
    ],
    'PACK_UNIT'=> [
            1=>'盘',
            2=>'管',
            3=>'袋',
            4=>'托盘'
    ],
];