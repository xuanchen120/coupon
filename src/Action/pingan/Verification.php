<?php

namespace XuanChen\Coupon\Action\pingan;

use App\Models\Coupon;
use App\Models\User;
use Carbon\Carbon;

class Verification extends PingAnInit
{

    public $ticket;

    public function start()
    {
        //检查是否已经核销过
        $res = $this->hasVerify();
        if ($res !== false) {
            return $res;
        }

        //查询卡券信息
        $this->query_coupon = (new Query)->setOutletId($this->outletId)->setCode($this->redemptionCode)->start();

        if (is_string($this->query_coupon)) {
            return $this->query_coupon;
        }

        //校验卡券
        $ticket = $this->checkCoupon();
        if (! is_array($ticket)) {
            return $ticket;
        }

        //检查可核销次数
        $ret = $this->CheckCount();
        if ($ret !== true) {
            return $ret;
        }

        //增加核销记录
        $coupon = $this->addCoupon();
        if (is_string($coupon)) {
            return $coupon;
        }

        try {
            $params = [
                'couponNo'       => $coupon->redemptionCode,
                'partnerOrderId' => date('ymdHis').sprintf("%0".strlen(999999)."d", mt_rand(0, 999999)),
                'outletId'       => $coupon->PaOutletId,
                'productId'      => $coupon->productId,
                'timestamp'      => $this->getMsecTime(),
            ];

            $url = $this->baseUri.'partner/redemption';
            $str = $this->encrypt($params);
            $res = $this->getPingAnData($url, [], ['data' => $str]);

            if (! is_array($res)) {
                $coupon->remark = $res;
                $coupon->status = 3;
                $coupon->save();
                throw new \Exception($res);
            }

            if ($res['code'] != 200) {
                $coupon->remark = $res['code'].'--'.$res['message'];
                $coupon->status = 3;
                $coupon->save();
                throw new \Exception($res['message']);
            }

            $data = $res['data'];

            $coupon->remark          = $data['message'];
            $coupon->pa_order_id     = $data['orderId'] ?? null;
            $coupon->pa_sub_order_id = $data['subOrderId'] ?? null;
            $coupon->status          = ($data['status'] == 1) ? 2 : 3;
            $coupon->save();

            //返回的数据
            $resdata = [
                'price' => $coupon->price,
                'name'  => $coupon->couponName,
                'total' => $coupon->total,
            ];

            //核销成功 执行分润
            $coupon->profit();

            return $resdata;

        } catch (\Exception $e) {
            $coupon->status = 3;
            $coupon->remark = '核销失败 '.$e->getMessage();
            $coupon->save();

            return $coupon->remark;
        }

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
        try {
            $couponData = [
                'user_id'           => $this->user->id,
                'type'              => Coupon::TYPE_PINGAN,
                'outletId'          => $this->outletId,
                'orderid'           => $this->orderid,
                'PaOutletId'        => $this->queryData['PaOutletId'],
                'redemptionCode'    => $this->redemptionCode,
                'productId'         => $this->queryData['productId'],
                'thirdPartyGoodsId' => $this->queryData['thirdPartyGoodsId'],
                'couponName'        => $this->query_coupon['couponName'],
                'full'              => $this->ticket['full'],
                'price'             => $this->ticket['price'],
                'total'             => $this->total,
                'profit'            => $this->ticket['profit'],
                'status'            => 0,
                'startTime'         => $this->query_coupon['startTime'],
                'endTime'           => $this->query_coupon['endTime'],
            ];

            return Coupon::create($couponData);

        } catch (\Exception $e) {
            return $e->getMessage();
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

        //检查卡券是否已被核销
        if ($this->query_coupon['status'] > 0) {
            if (isset(config('pingan.coupon_status')[$this->query_coupon['status']])) {
                return '核销失败，平安券'.config('pingan.coupon_status')[$this->query_coupon['status']];
            }

            return '核销失败，平安券不可用。';
        }

        $startTime = Carbon::parse($this->query_coupon['startTime']);
        $endTime   = Carbon::parse($this->query_coupon['endTime']);
        $now       = now();

        if ($startTime->gt($now)) {
            return '核销失败，平安券未开始使用。';
        }

        if ($now->gt($endTime)) {
            return '核销失败，平安券已过期。';
        }

        //查找适配的商品id 和 网点id
        $pinganData = $this->getPinganProduct();

        if (is_string($pinganData)) {
            return $pinganData;
        }

        //获取相关优惠信息
        return $this->checkCode();

    }

    /**
     * 校验平安券编号
     *
     * @return array|string [type]                 [description]
     */
    public function checkCode()
    {
        $code = $this->user->code->where('code', $this->queryData['thirdPartyGoodsId'])->first();
        if (! $code) {
            return "核销失败，未找到此项平安券规则,请联系管理人员检查渠道配置。";
        }

        $ticket = explode('-', $this->queryData['thirdPartyGoodsId']);

        if (! is_array($ticket) || count($ticket) != 3) {
            return "核销失败，平安券规则格式不正确。";
        }

        $full  = $ticket[1]; //full100
        $price = $ticket[2];
        preg_match('/\d+/', $full, $result);

        if (empty($result) || ! is_array($result)) {
            return "核销失败，平安券规则格式不正确。";
        }

        if (! is_numeric($this->total)) {
            return "核销失败，订单金额必须是数字";
        }
        if ($result[0] > $this->total) {
            return '核销失败，订单金额不足，平安券不可使用。';
        }

        $this->ticket = [
            'full'   => $result[0],
            'price'  => $price,
            'profit' => $code->profit,
        ];

        return $this->ticket;
    }

    /**
     * 查找平安商品id
     *
     * @return array|string [type]       [description]
     * @author 玄尘 2020-04-04
     */
    public function getPinganProduct()
    {
        //查询网点是否存在
        $outlet = User::where('outlet_id', $this->outletId)->first();

        if (! $outlet) {
            return '核销失败，网点编号错误，未查询到网点信息';
        }

        $PaOutletId             = $outlet->PaOutletId;
        $outlet_id              = $outlet->outlet_id;
        $profitOfferItemVersion = $this->query_coupon['profitOfferItemVersion'];

        if (! $PaOutletId && $profitOfferItemVersion != 1) {
            return '核销失败，参数错误，渠道信息缺少平安网点id';
        }

        $productItemList = $this->query_coupon['productItemList'];

        if (! is_array($productItemList) || ! is_array($productItemList[0])) {
            return '核销失败，平安券数据有误，可能是未配置网点。';
        }

        //循环查找
        $first = '';
        foreach ($productItemList as $key => $item) {
            $productId         = $item['productId'];
            $thirdPartyGoodsId = $item['thirdPartyGoodsId'];
            $outletList        = $item['outletList'];
            if (! is_array($outletList) || ! is_array($outletList[0])) {
                return '核销失败，网点信息有误！请检查平安券配置信息。';
                break;
            }

            $outletList = collect($outletList);
            //判断是新版还是旧版
            if ($profitOfferItemVersion) {
                //新版通过第三方查询
                $first = $outletList->firstWhere('thirdOutletNo', $outlet_id);

                if ($first) {
                    break;
                }
            } else {
                //旧版通过平安网点查询
                $first = $outletList->firstWhere('outletNo', $PaOutletId);
                if ($first) {
                    break;
                }
            }
        }

        if (! $first) {
            return '核销失败，未找到可用网点信息。';
        }

        if (! $thirdPartyGoodsId) {
            return '核销失败，平安券编号规则有误。';
        }

        if (! $productId) {
            return '核销失败，未查询到平安券商品id。';
        }

        return $this->queryData = [
            'thirdPartyGoodsId' => $thirdPartyGoodsId,
            'productId'         => $productId,
            'PaOutletId'        => $first['outletNo'],
        ];

    }

}
