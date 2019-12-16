<?php

// +----------------------------------------------------------------------
// | FileName:   orderExcel.php
// +----------------------------------------------------------------------
// | Dscription:订单excel下载配置
// +----------------------------------------------------------------------
// | Date:  2018/6/12 13:45
// +----------------------------------------------------------------------
// | Author: kelly <466395102@qq.com>
// +----------------------------------------------------------------------
return [
    'POSITION_DESCRIBE'=>[//订单excel表头描述
          'order_sn' => '订单编号',
          'order_status' => '订单状态',
          'ship_status' => '贷运状态',
          'create_at' => '下单时间',
          'pay_status' => '支付状态',
          'total_origin' => '下单总额',
          'total' => '结算总额',
          'orderHasPay' => '已付金额',
          'p_sign' => '商品编号',
          'current_num' => '需发贷总量',
          'erp_num' => '已发贷数量',
          'show_total_current' => '优惠前金额',
          'total_current' => '优惠后金额',
    ],
    'POSITION_COUNT_DESCRIBE'=>[//统计信息
         'p_sign' => '商品型号',
         'num_total_current' => '需发贷总量',
         'money_total_current' => '需支付总额',
    ],
    'POSITION_COUNT_MONEY_DESCRIBE'=>[//统计信息
          'money_total' => '待付总额',
          'money_total_has_pay' => '已付总额',
    ],
    'POSITION'=>[//定单批量录入的表格位置
        'order_sn' => 'A',
        'order_status' => 'B',
        'ship_status' => 'C',
        'create_at' => 'D',
        'pay_status' => 'E',
        'show_total_current' => 'F',
        'total_current' => 'G',
        'orderHasPay'=>'H',
        'p_sign' => 'I',
        'current_num' => 'J',
         'erp_num' => 'K',
     ],
    'POSITION_COUNT'=>[//统计信息
         'p_sign' => 'M',
         'num_total_current' => 'N',
         'money_total_current' => 'O',
    ],
    'POSITION_COUNT_MONEY'=>[//统计信息
          'money_total' => 'R',
          'money_total_has_pay' => 'S',
    ],
    'POSITION_MERGE'=>[//合并单元
         'position' =>['A','B','C','D','E','F','G','H'],
    ],


];