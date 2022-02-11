<?php

namespace XuanChen\Coupon\Action\ysd;

use App\Events\ConponCallback;
use App\Models\ActivityCoupon;
use App\Models\User;
use XuanChen\Coupon\Action\Init;

class YsdDestroy extends Init
{

    public function start()
    {
        if ($this->redemptionCode) {
            try {
                if (!$this->outletId) {
                    throw new \Exception('缺少网点id');
                }

                $info = ActivityCoupon::where('code', $this->redemptionCode)->first();

                if (!$info) {
                    throw new \Exception('未查询到卡券信息');
                }

                $outlet = User::where('outlet_id', $this->outletId)->first();

                if (empty($outlet)) {
                    return '作废失败,未查询到此网点信息。';
                }

                $grants = $info->activity->grants()->pluck('user_id');
                if ($grants->isEmpty()) {
                    return '作废失败，此活动还没有配置可发券渠道，请联系相关人员进行配置。';
                }

                if (!in_array($outlet->parent_id, $grants->toArray())) {
                    return '作废失败，您没有权限作废此优惠券。';
                }

                if (!$info->canDestroy()) {
                    throw new \Exception('作废失败，' . $info->status_text . '不能操作');
                }

                $info->status = ActivityCoupon::STATUS_CLOSE;
                $info->save();

                event(new ConponCallback($info));

                return true;
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        } else {
            return '未获取到券码。';
        }

    }

}