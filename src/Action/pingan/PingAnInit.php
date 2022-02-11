<?php

namespace XuanChen\Coupon\Action\pingan;

use App\Models\PinganToken;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use XuanChen\Coupon\Action\Init;

/**
 * 超市购物券
 */
class PingAnInit extends Init
{

    protected $this_type;

    protected $baseUri;

    protected $tokenUri;

    protected $client_id;

    protected $grant_type;

    protected $client_secret;

    protected $access_token;

    protected $userName;

    protected $error;

    protected $aes_code; //aes 密钥

    protected $log;//日志

    public function __construct()
    {
        $this->this_type = config('pingan.this_type');
        $pingan          = config('pingan.' . $this->this_type);

        $this->baseUri       = $pingan['Uri'];
        $this->tokenUri      = $pingan['tokenUri'];
        $this->client_id     = $pingan['client_id'];
        $this->grant_type    = $pingan['grant_type'];
        $this->userName      = $pingan['userName'];
        $this->client_secret = $pingan['client_secret'];
        $this->aes_code      = $pingan['AES_CODE'];
    }

    /**
     * 获取access_token
     * @return void [type] [description]
     */
    public function getToken()
    {
        //从数据库里找token
        $token = PinganToken::where('type', $this->this_type)->orderBy('id', 'desc')->first();

        if ($token) {
            $access_token   = $token->access_token;
            $expires_in     = $token->expires_in;
            $get_token_time = $token->get_token_time;
            $diffMinutes    = $get_token_time->diffInMinutes(now(), false);
            if ($diffMinutes < $expires_in) {
                $this->access_token = $access_token;
            } else {
                $this->getAjaxToken();
            }
        } else {
            $this->getAjaxToken();
        }
    }

    /**
     * 获取毫秒级别的时间戳
     */
    public function getMsecTime()
    {
        [$msec, $sec] = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        $msectime = explode('.', $msectime);

        return $msectime[0];
    }

    /**
     * 请求平台 access_token
     * @return void [type] [description]
     */
    public function getAjaxToken()
    {
        $params = [
            'client_id'     => $this->client_id,
            'grant_type'    => $this->grant_type,
            'client_secret' => $this->client_secret,
        ];

        try {
            $log = $this->createLog($this->tokenUri, 'POST', $params, 'pingan');

            $client   = new Client();
            $response = $client->request('POST', $this->tokenUri, [
                'form_params' => $params,
            ]);
            $body     = $response->getBody();
            $content  = $body->getContents();
            $result   = json_decode($content, true);

            $this->updateLog($log, $result); //更新日志

            if ($result['ret'] > 0) {
                $this->error = $result['msg'];
            } else {
                $data = $result['data'];
                PinganToken::create([
                    'type'           => $this->this_type,
                    'access_token'   => $data['access_token'],
                    'expires_in'     => $data['expires_in'],
                    'get_token_time' => now(),
                ]);
                $this->access_token = $data['access_token'];
                $this->error        = '';
            }
        } catch (RequestException $e) {
            $this->error = $e->getMessage();
            $this->updateLog($log, [$this->error]); //更新日志
        }

    }

    /**
     * 通用获取数据接口
     * @param  [type] $url    请求地址
     * @param array  $query  传递参数
     * @param array  $json   需要传的json数据
     * @param string $method 方式
     * @return array|mixed [type]         [description]
     */
    public function getPingAnData($url, $query = [], $json = [], $method = 'POST')
    {
        $this->getToken();

        if ($this->error) {
            return $this->error;
        }

        $postData = [
            'query'   => array_merge([
                'access_token' => $this->access_token,
                'request_id'   => $this->getMsecTime(),
                'userName'     => $this->userName,
            ], $query),
            'json'    => $json,
            'headers' => [
                'Content-Type' => 'application/json;charset=utf-8',
                'accept'       => 'application/json;charset=utf-8',
            ],
        ];

        $log = $this->createLog($url, $method, $postData, 'pingan'); //日志

        try {
            $client   = new Client();
            $response = $client->request($method, $url, $postData);
            $body     = $response->getBody();
            $content  = $body->getContents();
            $result   = json_decode($content, true);

            if ($result['ret'] > 0) {
                $retData = $result['msg'];
            } else {
                $retData = $result['data'];
            }
            $this->updateLog($log, $retData);//更新日志

            return $retData;
        } catch (RequestException $e) {
            $this->updateLog($log, [$e->getMessage()]);//更新日志

            return ['ret' => '99999', $e->getMessage()];
        }
    }

    //加密
    public function encrypt($str)
    {
        if (is_array($str)) {
            $str = json_encode($str);
        }
        $data = openssl_encrypt($str, 'aes-128-ecb', $this->aes_code, OPENSSL_RAW_DATA);

        return base64_encode($data);
    }

    //解密
    public function decrypt($str)
    {
        $encrypted = base64_decode($str);

        return openssl_decrypt($encrypted, 'aes-128-ecb', $this->aes_code, OPENSSL_RAW_DATA);
    }

}
