<?php

namespace XuanChen\Coupon\Action\ysd;

use App\Events\ConponCallback;
use App\Models\ActivityCoupon;
use App\Models\Coupon;
use Illuminate\Support\Facades\DB;
use XuanChen\Coupon\Action\Init;

class YsdVerification extends Init
{

    public $ticket;

    /**
     * Notes: 核销具体流程
     *
     * @Author: 玄尘
     * @Date  : 2020/7/29 13:12
     * @return array|string
     */
    public function start()
    {
        //检查是否已经核销过
        $res = $this->hasVerify();
        if ($res !== false) {
            return $res;
        }

        //查询卡券信息  返回model  返回string 说明报错
        $this->query_coupon = (new YsdQuery)->setOutletId($this->outletId)
            ->setCode($this->redemptionCode)
            ->start();

        if (is_string($this->query_coupon)) {
            return $this->query_coupon;
        }

        //检查网点是否有权限
        $res = $this->verify_shop();
        if ($res !== true) {
            return $res;
        }

        //当日可用券检查
        $res = $this->verify_day();
        if ($res !== true) {
            return $res;
        }

        //校验卡券
        $ticket = $this->checkCoupon();
        if (! is_array($ticket)) {
            return $ticket;
        }

        //检查可核销次数，100元为1次。
        if ($this->query_coupon->activity && $this->query_coupon->activity->need_check) {
            $ret = $this->CheckCount();
            if ($ret !== true) {
                return $ret;
            }
        }

        //增加核销记录
        $coupon = $this->addCoupon();
        if (is_string($coupon)) {
            return $coupon;
        }

        DB::beginTransaction();

        try {
            $this->query_coupon->status  = ActivityCoupon::STATUS_USED;
            $this->query_coupon->used_at = now();
            $this->query_coupon->save();

            $this->coupon->status = 2;
            $this->coupon->remark = '核销成功';
            $this->coupon->save();
            //返回的数据
            $resdata = [
                'name'  => $this->coupon->couponName,
                'total' => $this->coupon->total,
                'price' => $this->coupon->price,
            ];
            //核销成功 执行分润
            $this->coupon->profit();
            DB::commit();

            event(new ConponCallback($this->query_coupon));

            return $resdata;
        } catch (\Exception $e) {
            DB::rollback();

            $this->coupon->status = 3;
            $this->coupon->remark = '核销失败 '.$e->getMessage();
            $this->coupon->save();

            return $this->coupon->remark;
        }

    }

    /**
     * Notes: 检查卡券信息
     *
     * @Author: 玄尘
     * @Date  : 2020/6/29 16:40
     * @return string
     */
    public function checkCoupon()
    {

        if (! $this->query_coupon->canRedemption()) {
            return '核销失败，优惠券不可核销';
        }

        $now = now();

        if ($this->query_coupon->start_at->gt($now)) {
            return '核销失败，卡券未到可用时间。请在'.$this->query_coupon->start_at->format('Y-m-d H:i:s').'后使用';
        }

        if ($now->gt($this->query_coupon->end_at)) {
            return '核销失败，卡券已过期。';
        }

        $rule_code = $this->query_coupon->activity->rule->code;
        $code      = $this->user->code->where('code', $rule_code)->first();

        if (! $code) {
            return "核销失败，您没有权限使用此卡券优惠活动。";
        }

        $ticket = explode('-', $rule_code);
        if (! is_array($ticket) || count($ticket) != 3) {
            return "核销失败，卡券规则格式不正确";
        }

        $full  = $ticket[1]; //full100
        $price = $ticket[2];
        //        preg_match('/(\d{3}(\.\d+)?)/is', $full, $match);
        preg_match('/\d+/', $full, $match);

        if (! is_array($match)) {
            return "核销失败，卡券规则格式不正确。";
        }

        if (! is_numeric($this->total)) {
            return "核销失败，订单金额必须是数字";
        }

        if ($match[0] > $this->total) {
            return '核销失败，订单金额不足。';
        }

        return $this->ticket = [
            'full'   => $match[0],
            'price'  => $price,
            'profit' => $code->profit,
        ];

    }

    /**
     * Notes: 如可核销记录
     *
     * @Author: 玄尘
     * @Date  : 2020/7/21 15:03
     * @return string
     */
    public function addCoupon()
    {
        DB::beginTransaction();

        try {
            $couponData = [
                'user_id'           => $this->user->id,
                'type'              => Coupon::TYPE_YSD,
                'outletId'          => $this->outletId,
                'orderid'           => $this->orderid,
                'PaOutletId'        => '',
                'redemptionCode'    => $this->redemptionCode,
                'thirdPartyGoodsId' => $this->query_coupon->activity->rule->code,
                'couponName'        => $this->query_coupon->activity->title,
                'full'              => $this->ticket['full'],
                'price'             => $this->ticket['price'],
                'total'             => $this->total,
                'profit'            => $this->ticket['profit'],
                'status'            => $this->query_coupon->status,
                'startTime'         => $this->query_coupon->start_at,
                'endTime'           => $this->query_coupon->end_at,
            ];

            $this->coupon = Coupon::create($couponData);

            DB::commit();

            return $this->coupon;
        } catch (\Exception $e) {
            DB::rollback();

            return $e->getMessage();
        }

    }

}