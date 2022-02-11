<?php

return [
    /**
     * 平安接口参数
     */
    'this_type'     => 'dev',
    /**
     * 测试环境参数
     */
    'test'          => [
        'client_id'     => env('PINGAN_TEST_CLIENT_ID', ''),
        'grant_type'    => 'client_credentials',
        'client_secret' => env('PINGAN_TEST_CLIENT_SECRET', ''),
        'userName'      => env('PINGAN_TEST_USERNAME', ''),
        'AES_CODE'      => env('PINGAN_TEST_AES_CODE', ''),
        'tokenUri'      => 'https://test-api.pingan.com.cn:20443/oauth/oauth2/access_token',
        'Uri'           => 'http://test-api.pingan.com.cn:20080/open/vassPartner/appsvr/property/api/new/',
    ],

    /**
     * 生产环境参数
     */
    'dev'           => [
        'client_id'     => env('PINGAN_DEV_CLIENT_ID', ''),
        'grant_type'    => 'client_credentials',
        'client_secret' => env('PINGAN_DEV_CLIENT_SECRET', ''),
        'userName'      => env('PINGAN_DEV_USERNAME', ''),
        'AES_CODE'      => env('PINGAN_DEV_AES_CODE', ''),
        'tokenUri'      => 'http://api.pingan.com.cn/oauth/oauth2/access_token',
        'Uri'           => 'http://api.pingan.com.cn/open/vassPartner/appsvr/property/api/new/',
    ],
    'profit'        => [
        'YSD-full100-10'  => 0,
        'YSD-full100-25'  => 15,
        'YSD-full100-50'  => 40,
        'YSD-full200-100' => 80,
    ],
    'coupon_status' => [
        1 => '使用中',
        2 => '已使用',
        3 => '已过期',
        4 => '已收回',
        5 => '退兑换',
        6 => '已冻结',
        7 => '未激活',
    ],

    'froms'       => [
        'bsshop',//本时商城
        'bslive',//本时生活
    ],

    //券码长度 15 或 17
    'code_length' => 15,

];
