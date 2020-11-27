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
        'client_id'     => 'P_YISHIDAI',
        'grant_type'    => 'client_credentials',
        'client_secret' => 'zGg9e6J5',
        'userName'      => '18804518018',
        'AES_CODE'      => '61DA0376BEBCFE1F',
        'tokenUri'      => 'https://test-api.pingan.com.cn:20443/oauth/oauth2/access_token',
        'Uri'           => 'http://test-api.pingan.com.cn:20080/open/vassPartner/appsvr/property/api/new/',
    ],

    /**
     * 生产环境参数
     */
    'dev'           => [
        'client_id'     => 'P_YISHIDAI',
        'grant_type'    => 'client_credentials',
        'client_secret' => 'F3j5J7bx',
        'userName'      => '13936166646',
        'AES_CODE'      => '108DD27AB83252DB',
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

];
