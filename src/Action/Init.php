<?php

namespace XuanChen\Coupon\Action;

use App\Models\Activity;
use App\Models\Coupon;
use App\Models\Log as LogModel;
use App\Models\User;

class Init
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

    //核销的卡券 创建的核销记录
    public $coupon;

    //查询返回卡券信息
    public $query_coupon;

    //订单id
    public $orderid;

    //查询到的卡券规则和商品id 只有平安券才有
    public $queryData;

    //来源
    public $from;

    //设置渠道
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    //设置渠道
    public function setOrderId($orderid)
    {
        $this->orderid = $orderid;

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

    //设置来源
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;

    }

    /**
     * Notes: 插入日志
     *
     * @Author: 玄尘
     * @Date  : 2020/6/30 10:29
     * @param          $url
     * @param          $method
     * @param          $params
     * @param  string  $type
     * @return mixed
     */
    public function createLog($url, $method, $params, $type = 'pingan')
    {
        $data = [
            'path'      => $url,
            'method'    => $method,
            'type'      => $type,
            'in_source' => $params,
        ];

        $info = LogModel::create($data);

        return $info;
    }

    /**
     * Notes: 更新日志
     *
     * @Author: 玄尘
     * @Date  : 2020/6/30 10:29
     * @param $log
     * @param $params
     */
    public static function updateLog($log, $params)
    {
        $log->out_source = $params;
        $log->save();
    }

    //统一门店 相同金额 3分钟之内看作是一笔订单
    public function CheckCount()
    {
        //排除来源
        if (! empty($this->from) && in_array($this->from, config('xuanchen_coupon.froms'))) {
            return true;
        }

        if ($this->queryData) {
            if (isset($this->queryData['thirdPartyGoodsId']) && $this->queryData['thirdPartyGoodsId'] == 'YSD-full0-0') {
                return true;
            }
        }

        //已核销的券的满多少金额
        if ($this->orderid) {
            $check_count = Coupon::where('orderid', $this->orderid)
                ->where('outletId', $this->outletId)
                ->where('status', 2)
                ->sum('full');

            //获取第一次的核销请求
            $first = Coupon::where('orderid', $this->orderid)->orderBy('id', 'asc')->first();
            //如果两次的金额不对，把金额设置为第一次的金额
            if ($first && $first->total != $this->total) {
                $this->total = $first->total;
            }
        } else {
            $check_count = Coupon::where('outletId', $this->outletId)
                ->where('total', $this->total)
                ->where('status', 2)
                ->where('created_at', '>=', now()->subMinutes(3)->format('Y-m-d H:i:s'))
                ->sum('full');
        }

        //金额判断
        if ($check_count >= $this->total) {
            return "核销失败，此订单您无法再使用优惠券";
        }

        //取差值
        $diff = bcsub($this->total, $check_count);

        if ($diff < $this->ticket['full']) {
            return "核销失败，此订单您无法再使用优惠券";
        }

        return true;
    }

    /**
     * Notes: 校验是否已经核销过
     *
     * @Author: 玄尘
     * @Date  : 2020/8/8 13:43
     */
    public function hasVerify()
    {
        $info = Coupon::where('redemptionCode', $this->redemptionCode)
            ->where('outletId', $this->outletId)
            ->where('total', $this->total)
            ->where('status', 2)
            ->first();
        if ($info) {
            return '核销失败，此优惠券已被使用';
        }

        return false;

    }

    /**
     * Notes: 校验网点
     *
     * @Author: 玄尘
     * @Date  : 2021/4/25 15:46
     */
    public function verify_shop()
    {
        $activity = $this->query_coupon->activity;
        if (! $activity) {
            return "未找到活动";
        }

        if ($activity->verify_shop == Activity::VERIFY_SHOP_YES) {
            $shop = User::where('outlet_id', $this->outletId)->first();

            if (empty($shop)) {
                return '操作失败,未查询到此网点信息。';
            }

            if (! in_array($shop->id, $activity->shops()->pluck('user_id')->toArray())) {
                return '操作失败,此网点没有权限';
            }
        }

        return true;

    }

    /**
     * Notes: 检查当天可用次数
     *
     * @Author: 玄尘
     * @Date: 2023/5/15 9:18
     */
    public function verify_day()
    {
        $activity = $this->query_coupon->activity;
        if ($activity->day_times > 0) {
            $day_times = $activity->day_times;
            $count     = Coupon::query()
                ->whereHas('activityCoupon', function ($q) use ($activity) {
                    $q->where('activity_id', $activity->id)->where('mobile', $this->query_coupon->mobile);
                })
                ->count();

            if ($count >= $day_times) {
                return '核销失败，此类券每天只可使用'.$day_times.'张';
            }
        }

        return true;
    }

}
