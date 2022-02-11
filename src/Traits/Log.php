<?php

namespace XuanChen\Coupon\Traits;

use App\Models\Log as LogModel;

trait Log
{

    /**
     * Notes: 插入日志
     * @Author: 玄尘
     * @Date  : 2020/6/30 10:29
     * @param        $url
     * @param        $method
     * @param        $params
     * @param string $type
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

}
