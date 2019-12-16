<?php

// +----------------------------------------------------------------------
// | FileName:   access.php
// +----------------------------------------------------------------------
// | Dscription:权限豁免
// +----------------------------------------------------------------------
// | Date:  2017/12/07 23:58
// +----------------------------------------------------------------------
// | Author: liqiang <466395102@qq.com>
// +----------------------------------------------------------------------
return [
        'admin'=>[
            'spread'=>1,
            'AutoComplete'=>[
                'spread'=>2,
            ],
            'Index'=>[
                'spread'=>1,
                'logout'=>[
                    'spread'=>1,
                ],
                'login'=>[
                    'spread'=>1,
                ],
                'index'=>[
                    'spread'=>1,
                ],
                'main'=>[
                    'spread'=>1,
                ],
                'getMenu'=>[
                    'spread'=>1,
                ],
            ],
            'Customer'=>[
                'spread'=>1,
                'updateCompanyNameFirstString'=>[
                    'spread'=>1,
                ],
                'companyPinyinSearch'=>[
                    'spread'=>1,
                ],
                'updateAdminFirstString'=>[
                    'spread'=>1,
                ],
                'adminPinyinSearch'=>[
                    'spread'=>1,
                ],
                'erpCustomerAll'=>[
                    'spread'=>1,
                ]
            ],
            'Msg'=>[
                'spread'=>1,
                'getAllMsgList'=>[
                    'spread'=>1,
                ],
                'getAllUnReadMsgList'=>[
                    'spread'=>1,
                ],
                'pullMsg'=>[
                    'spread'=>1,
                ],
                'getMsgContent'=>[
                    'spread'=>1,
                ],
                'setMsgReaded'=>[
                    'spread'=>1,
                ],
                'setMsgDel'=>[
                    'spread'=>1,
                ],
            ],
        ],

];