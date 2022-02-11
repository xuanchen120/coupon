<?php

namespace XuanChen\Coupon\Action\pingan;

use App\Models\User;

class Query extends PingAnInit
{

    public function start()
    {

        try {
            //查询网点是否存在
            $outlet = User::where('outlet_id', $this->outletId)->first();

            if (!$outlet) {
                throw new \Exception('网点编号错误，未查询到网点信息');
            }

            $url    = $this->baseUri . 'partner/v2/coupondetail';
            $params = [
                'redemptionCode' => $this->redemptionCode,
                'outletNo'       => $outlet->PaOutletId,
                'thirdOutletNo'  => $outlet->outlet_id,
            ];

            $res = $this->getPingAnData($url, $params);

            if (!is_array($res)) {
                throw new \Exception($res);
            }

            if ($res['code'] != 200) {
                throw new \Exception($res['message']);
            }

            return collect($res['data']);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }

}