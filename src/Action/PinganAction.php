<?php

namespace XuanChen\Coupon\Action;

use XuanChen\Coupon\Action\pingan\Query;
use XuanChen\Coupon\Action\pingan\Verification;
use XuanChen\Coupon\Contracts\CouponContracts;

class PinganAction extends Init implements CouponContracts
{

    /**
     * Notes: 核销执行入口
     * @Author: 玄尘
     * @Date  : 2020/6/29 14:49
     * @return mixed
     */
    public function start()
    {
        return (new Verification)->setCode($this->redemptionCode)
                                 ->setUser($this->user)
                                 ->setOutletId($this->outletId)
                                 ->setTotal($this->total)
                                 ->setOrderId($this->orderid)
                                 ->setFrom($this->from)
                                 ->start();
    }

    /**
     * Notes: 查询卡券详情
     * @Author: 玄尘
     * @Date  : 2020/6/29 15:15
     * @return mixed
     */
    public function detail()
    {
        $info = (new Query)->setOutletId($this->outletId)
                           ->setCode($this->redemptionCode)
                           ->start();

        return $info;
    }

    //发券
    public function grant()
    {
        return '没这个接口';
    }

    //作废
    public function destroy()
    {
        return '没这个接口';
    }

    //撤销
    public function reversal()
    {
        return '没这个接口';
    }

}
