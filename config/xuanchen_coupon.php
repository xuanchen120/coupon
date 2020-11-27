<?php

return [
    'coupon_model' => \App\Models\Coupon::class,
    'rules'        => [
        'pingan' => [
            'pattern' => '/^\d{12}$/',
            'model'   => \XuanChen\Coupon\Action\PinganAction::class,
        ],
        'ysd'    => [
            'pattern' => '/^YSD/',
            'model'   => \XuanChen\Coupon\Action\YsdAction::class,
        ],
    ],
];
