<?php

namespace XuanChen\Coupon\Action;

use XuanChen\Coupon\Action\ysd\YsdDestroy;
use XuanChen\Coupon\Action\ysd\YsdGrant;
use XuanChen\Coupon\Action\ysd\YsdQuery;
use XuanChen\Coupon\Action\ysd\YsdReversal;
use XuanChen\Coupon\Action\ysd\YsdVerification;
use XuanChen\Coupon\Contracts\CouponContracts;

/**
 * Class YsdAction 自有卡券核销
 * @Author  : 玄尘
 * @Date    : 2020/7/21 9:41
 * @package XuanChen\Coupon\Action
 */
class YsdAction extends Init implements CouponContracts
{

    /**
     * Notes: 发券
     * @Author: 玄尘
     * @Date  : 2020/7/21 10:08
     * @return mixed
     */
    public function grant()
    {
        return (new YsdGrant)->setActivityId($this->activityId)
                             ->setOutletId($this->outletId)
                             ->setMobile($this->mobile)
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
        $query_coupon = (new YsdQuery)->setOutletId($this->outletId)
                                      ->setCode($this->redemptionCode)
                                      ->start();

        if (!is_string($query_coupon)) {
            return [
                'name'      => $query_coupon->activity->title,
                'code'      => $query_coupon->code,
                'full'      => $query_coupon->full,
                'price'     => $query_coupon->price,
                'status'    => $query_coupon->status,
                'used_at'   => (string)$query_coupon->used_at,
                'startTime' => (string)$query_coupon->start_at,
                'endTime'   => (string)$query_coupon->end_at,

            ];
        }

        return $query_coupon;
    }

    /**
     * Notes: 作废
     * @Author: 玄尘
     * @Date  : 2020/7/21 11:32
     */
    public function destroy()
    {
        return $res = (new YsdDestroy)->setCode($this->redemptionCode)
                                      ->setOutletId($this->outletId)
                                      ->start();
    }

    /**
     * Notes: 核销执行入口
     * @Author: 玄尘
     * @Date  : 2020/6/29 14:49
     * @return mixed
     */
    public function start()
    {
        return $res = (new YsdVerification)->setCode($this->redemptionCode)
                                           ->setUser($this->user)
                                           ->setOutletId($this->outletId)
                                           ->setTotal($this->total)
                                           ->setOrderId($this->orderid)
                                           ->setFrom($this->from)
                                           ->start();
    }

    /**
     * Notes: 撤销
     * @Author: 玄尘
     * @Date  : 2020/10/12 11:55
     * @return array|string
     */
    public function reversal()
    {
        return $res = (new YsdReversal)->setCode($this->redemptionCode)
                                       ->setOutletId($this->outletId)
                                       ->start();
    }

}
