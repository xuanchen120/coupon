<?php

namespace XuanChen\Coupon\Action\ysd;

use App\Events\ConponCallback;
use App\Models\ActivityCoupon;
use App\Models\Coupon;
use App\Models\User;
use XuanChen\Coupon\Action\Init;

class YsdReversal extends Init
{

    public function start()
    {
        if ($this->redemptionCode) {
            try {
                if (!$this->outletId) {
                    throw new \Exception('缺少网点id');
                }

                $activityCoupon = ActivityCoupon::where('code', $this->redemptionCode)->first();

                if (!$activityCoupon) {
                    throw new \Exception('未查询到卡券信息');
                }

                if (!$activityCoupon->canReversal()) {
                    throw new \Exception('操作失败，卡券当前状态不能操作');
                }

                $outlet = User::where('outlet_id', $this->outletId)->first();

                if (empty($outlet)) {
                    return '操作失败,未查询到此网点信息。';
                }

                $grants = $activityCoupon->activity->grants()->pluck('user_id');
                if ($grants->isEmpty()) {
                    return '操作失败，此活动还没有配置可发券渠道，请联系相关人员进行配置。';
                }

                if (!in_array($outlet->parent_id, $grants->toArray())) {
                    return '操作失败，您没有权限作废此优惠券。';
                }

                $coupon = Coupon::where('redemptionCode', $this->redemptionCode)
                                ->where('status', 2)
                                ->first();

                if ($activityCoupon && $coupon) {
                    //撤销
                    $activityCoupon->reversal();
                    //撤销
                    $coupon->reversal();
                }
                event(new ConponCallback($activityCoupon));

                return true;
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        } else {
            return '未获取到券码。';
        }

    }

}