<?php

namespace XuanChen\Coupon\Traits;

trait SetParams
{

    //渠道
    public $user;

    //卡券编号
    public $redemptionCode;

    //订单金额
    public $total;

    //网点编号
    public $outletId;

    //活动id
    public $activityId;

    //手机号
    public $mobile;

    //设置渠道
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    //设置核销码
    public function setCode($redemptionCode)
    {
        $this->redemptionCode = $redemptionCode;

        return $this;

    }

    //设置订单总额
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;

    }

    //设置网点id
    public function setOutletId($outletId)
    {
        $this->outletId = $outletId;

        return $this;

    }

    //设置活动id
    public function setActivityId($activityId)
    {
        $this->activityId = $activityId;

        return $this;

    }

    //设置手机号
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;

    }

}
