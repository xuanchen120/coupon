<?php

namespace XuanChen\Coupon;

use App\Models\User;
use XuanChen\UnionPay\Models\UnionpayCoupon;

/**
 * 自有卡券系统
 */
class Coupon
{

    /**
     * Notes: 发券接口
     * @Author: 玄尘
     * @Date  : 2020/6/28 15:07
     * @param $activityId  活动编号
     * @param $outletId    网点编号
     * @param $mobile      手机号
     */
    public static function Grant($activityId, $outletId, $mobile)
    {
        $model = config('xuanchen_coupon.rules.ysd.model');

        return (new $model)->setActivityId($activityId)
                           ->setOutletId($outletId)
                           ->setMobile($mobile)
                           ->grant();

    }

    /**
     * Notes: 查询接口
     * @Author: 玄尘
     * @Date  : 2020/7/21 11:58
     * @param $redemptionCode
     */
    public static function Query($redemptionCode, $outletId)
    {
        if (!$redemptionCode) {
            return '查询失败，未获取到券码';
        }

        $model = self::getModelByCode($redemptionCode);
        if (is_string($model)) {
            return $model;
        }

        return $model->setCode($redemptionCode)
                     ->setOutletId($outletId)
                     ->detail();
    }

    /**
     * Notes: 卡券作废
     * @Author: 玄尘
     * @Date  : 2020/9/2 16:54
     * @param $redemptionCode
     * @param $outletId
     * @return string
     */
    public static function Destroy($redemptionCode, $outletId)
    {
        try {
            $model = self::getModelByCode($redemptionCode);
            if (is_string($model)) {
                return $model;
            }

            return $model->setCode($redemptionCode)
                         ->setOutletId($outletId)
                         ->destroy();

        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }

    /**
     * Notes: 根据券码 获取class
     * @Author: 玄尘
     * @Date  : 2020/7/21 12:00
     * @param $code
     * @return string
     */
    public static function getModelByCode($code)
    {
        $rules = config('xuanchen_coupon.rules');

        if (!$rules) {
            throw new \Exception('系统出错，未找到配置文件');
        }

        $model = '';
        foreach ($rules as $rule) {
            if (is_array($rule['pattern']) && count($rule['pattern']) > 1) {
                foreach ($rule['pattern'] as $pattern) {
                    if (preg_match($pattern, $code, $matches)) {
                        $model = $rule['model'];
                        break;
                    }
                }
            } else {
                if (preg_match($rule['pattern'], $code, $matches)) {
                    $model = $rule['model'];
                    break;
                }
            }

        }

        if (!$model) {
            throw new \Exception('操作失败。未查到卡券所属');
        }

        return new $model;

    }

    /**
     * Notes: description
     * @Author: 玄尘
     * @Date  : 2020/8/21 13:33
     * @param  \App\Models\User  $user            渠道
     * @param  string            $redemptionCode  要核销的券码
     * @param  float             $total           订单金额
     * @param  string            $outletId        网点id
     * @param  string            $orderid         订单id
     * @param  string            $from            来源
     * @return string
     */
    public static function Redemption(
        User $user,
        string $redemptionCode,
        float $total,
        string $outletId,
        string $orderid = '',
        string $from = ''
    ) {
        try {

            $is_unionpay = UnionpayCoupon::where('coupon_no', $redemptionCode)->exists();
            if ($is_unionpay) {
                return "核销失败，无次优惠券核销权限";
            }

            $model = self::getModelByCode($redemptionCode);
            if (is_string($model)) {
                return $model;
            }

            return $model->setUser($user)
                         ->setCode($redemptionCode)
                         ->setTotal($total)
                         ->setOutletId($outletId)
                         ->setOrderId($orderid)
                         ->setFrom($from)
                         ->start();

        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }

    /**
     * Notes: 冲正 撤销 已经核销的改为未核销状态
     * @Author: 玄尘
     * @Date  : 2020/10/12 11:54
     * @param $redemptionCode
     * @param $outletId
     * @return string
     */
    public static function Reversal($redemptionCode, $outletId)
    {
        try {
            $model = self::getModelByCode($redemptionCode);
            if (is_string($model)) {
                return $model;
            }

            return $model->setCode($redemptionCode)
                         ->setOutletId($outletId)
                         ->reversal();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

}
