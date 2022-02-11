<?php

namespace XuanChen\Coupon\Action\ysd;

use App\Models\ActivityCoupon;
use App\Models\User;
use XuanChen\Coupon\Action\Init;

class YsdQuery extends Init
{

    public function start()
    {
        try {
            $info = User::where('outlet_id', $this->outletId)->first();

            if (!$info) {
                throw new \Exception('网点编号错误，未查询到网点信息');
            }

            $coupon = ActivityCoupon::where('code', $this->redemptionCode)->first();

            if (!$coupon) {
                throw new \Exception('卡券编号错误，未查询到卡券信息');
            }

            $activity = $coupon->activity;
            if (!$activity) {
                throw new \Exception('操作失败,未查到活动信息');
            }

            //获取所有可核销渠道
            $verifications = $activity->verifications()->pluck('user_id');

            if ($verifications->isEmpty()) {
                throw new \Exception('操作失败，此活动还没有配置可核券渠道，请联系相关人员进行配置。');
            }

            if (!in_array($info->parent_id, $verifications->toArray())) {
                throw new \Exception('操作失败,您没有权限查询此卡券信息。');
            }

            return $coupon;

        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }

}