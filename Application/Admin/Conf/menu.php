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
    [
        'title'     =>'产品管理',
        'spread'    => true,
        'children'  =>[
            [
                'title'     =>'商品列表',
                'href'      =>U('Admin/Product/productList'),
                'icon'      =>'other',
            ],
            [
                'title'     =>'商品录入',
                'href'      =>U('Admin/Product/productListAction'),
                'icon'      =>'write',
            ],
            [
                'title'     =>'分类管理',
                'href'      =>U('Admin/Category/categoryList'),
                'icon'      =>'category',
            ],
            [
                'title'     =>'品牌管理',
                'href'      =>U('Admin/Product/brandList'),
                'icon'      =>'brande',
            ],
        ]
    ],
    [
        'title'     =>'客户管理',
        'spread'    => true,
        'children'  =>[
            [
                'title'     =>'客户列表',
                'href'      =>U('Admin/Customer/customerList'),
                'icon'      =>'users',
            ],
            [
                'title'     =>'价格管理',
                'href'      =>U('Admin/Customer/customerProductBargainList'),
                'icon'      =>'price',
            ],
            [
                'title'     =>'账期审核及管理',
                'href'      =>U('Admin/Customer/customerAccountList'),
                'icon'      =>'price',
            ],
            [
                'title'     =>'报备信息',
                'href'      =>U('Admin/Customer/reportMessage'),
                'icon'      =>'price',
            ],
        ]
    ],
    [
        'title'     =>'订单管理',
        'spread'    => true,
        'children'  =>[
            [
                'title'     =>'订单管理',
                'href'      =>U('Admin/Order/orderList'),
                'icon'      =>'other',
            ],
            [
                'title'     =>'退货退款',
                'href'      =>U('Admin/Retreat/index'),
                'icon'      =>'other',
            ],
            [
                'title'     =>'订单同步管理',
                'href'      =>U('Admin/Sync/index'),
                'icon'      =>'users',
            ],
        ]
    ],
    [
        'title'     =>'财务管理',
        'spread'    => true,
        'children'  =>[
            [
                'title'     =>'水单管理',
                'href'      =>U('Admin/Order/payImgList'),
                'icon'      =>'other',
            ],
            [
                'title'     =>'应收列表',
                'href'      =>U('Admin/Order/orderList/show/depositReceipts'),
                'icon'      =>'other',
            ],
        ]
    ],
    [
        'title'     =>'后台人员管理',
        'spread'    => true,
        'children'  =>[
            [
                'title'     =>'后台用户管理',
                'href'      =>U('Admin/Sysuser/sysUserList'),
                'icon'      =>'users',
            ],
            [
                'title'     =>'后台部门管理',
                'href'      =>U('Admin/Sysuser/sysUserDepartment'),
                'icon'      =>'users',
            ],
            [
                'title'     =>'系统角色管理',
                'href'      =>U('Admin/Sys/roleList'),
                'icon'      =>'users',
            ],
        ]
    ],
    [
        'title'     =>'系统功能管理',
        'spread'    => true,
        'children'  =>[
            [
                'title'     =>'404错误页',
                'href'      =>U('Admin/Index/error/type/404'),
                'icon'      =>'users',
            ],
            [
                'title'     =>'无权限错误页',
                'href'      =>U('Admin/Index/error/type/noPower'),
                'icon'      =>'users',
            ],
        ]
    ],
];