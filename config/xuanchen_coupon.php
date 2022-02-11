<?php

return [
    'coupon_model' => \App\Models\Coupon::class,
    'rules'        => [
        'ysd'      => [
            'pattern' => '/^YSD/',
            'model'   => \XuanChen\Coupon\Action\YsdAction::class,
        ],
        'unionpay' => [
            'pattern' => '/^66406/',
            'model'   => \XuanChen\Coupon\Action\YsdAction::class,
        ],
        'pingan'   => [
            'pattern' => '/^\d{12}$/',
            'model'   => \XuanChen\Coupon\Action\PinganAction::class,
        ],

    ],

    'froms' => [
        'bsshop',//本时商城
        'bslive',//本时生活
    ],
];
