<?php

namespace XuanChen\Coupon\Contracts;

interface CouponContracts
{

    //发券接口
    function grant();

    /**
     * Notes: 查询卡券详情
     * @Author: 玄尘
     * @Date  : 2020/6/29 15:15
     * @return mixed
     */
    function detail();

    //作废接口
    function destroy();

    /**
     * Notes: 核销执行入口
     * @Author: 玄尘
     * @Date  : 2020/6/29 14:49
     * @return mixed
     */
    function start();

}
