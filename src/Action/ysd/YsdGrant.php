<?php

namespace XuanChen\Coupon\Action\ysd;

use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use XuanChen\Coupon\Action\Init;

class YsdGrant extends Init
{

    public function start()
    {
        try {

            $activity = Activity::withCount('coupons')->where('code', $this->activityId)->first();
            if (!$activity) {
                return '发券失败,没有找到这个活动。';
            }

            if (!$activity->status) {
                return '发券失败,活动已经关闭。';
            }

            if ($activity->type == Activity::TYPE_SCOPE && Carbon::now()->gt($activity->end_at)) {
                return '发券失败,此活动已经结束。';
            }

            if ($activity->total > 0 && $activity->coupons_count >= $activity->total) {
                return '发券失败,已达到可发券总数。';
            }

            $outlet = User::where('outlet_id', $this->outletId)->first();
            if (!$outlet) {
                return '发券失败,未查询到此网点信息。';
            }

            $grants = $activity->grants()->pluck('user_id');
            if ($grants->isEmpty()) {
                return '发券失败，此活动还没有配置可发券渠道，请联系相关人员进行配置。';
            }

            if (!in_array($outlet->parent_id, $grants->toArray())) {
                return '发券失败，您没有权限发此类优惠券。';
            }

            $coupon = $activity->grant($this->mobile, $this->outletId);

            if (!is_string($coupon)) {
                return [
                    'name'      => $activity->title,
                    'code'      => $coupon->code,
                    'full'      => $coupon->full,
                    'price'     => $coupon->price,
                    'startTime' => $coupon->start_at->format('Y-m-d H:i:s'),
                    'endTime'   => $coupon->end_at->format('Y-m-d H:i:s'),
                ];
            }

            return $coupon;
        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }

}