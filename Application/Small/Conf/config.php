<?php
return array(
	//'配置项'=>'配置值'

    'DEFAULT_AJAX_RETURN'=>'json',

    //不用验证token的接口
    'NO_TOKEN'=>[
        'Login'=>[
            'getOpenid'=>2,
        ]
    ],
    //需要验证token的接口
    'TOKEN'=>[
        'MemberCenter'=>[
            'memberCenter'=>2,
            'my'=>2,
        ],
        'Basket'=>[
            'basket'=>2,
            'basketAction'=>2,
        ],
        'Order'=>[
            'orderList'=>2,
            'createOrder'=>2,
            'hyReceive'=>2,
            'cancelOrder'=>2,
            'knotOrderMoney'=>2,
            'knotOrder'=>2,
        ],
        'UserPay'=>[
            'userPay'=>2,
        ],
        'Customer'=>[
            'customerInfo'=>2,
            'userComment'=>2,
        ],
        'File'=>[
            'xcxQRCode'=>2,
        ],
    ]



);