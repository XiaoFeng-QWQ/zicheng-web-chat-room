<?php

namespace ChatRoom\Core\Modules;

use ChatRoom\Core\Config\App;

class Captcha
{
    private $apiServer = "http://gcaptcha4.geetest.com";
    private $appConfig;

    public function __construct()
    {
        $this->appConfig = new App;
    }

    public function validate($lotNumber, $captchaOutput, $passToken, $genTime)
    {
        // 生成签名
        $signToken = hash_hmac('sha256', $lotNumber, $this->appConfig->geetest['captchaKey']);

        // 上传校验参数到极验二次验证接口, 校验用户验证状态
        $query = array(
            "lot_number" => $lotNumber,
            "captcha_output" => $captchaOutput,
            "pass_token" => $passToken,
            "gen_time" => $genTime,
            "sign_token" => $signToken
        );
        $url = sprintf($this->apiServer . "/validate" . "?captcha_id=%s", $this->appConfig->geetest['captchaId']);
        $res = $this->request($url, $query);
        $obj = json_decode($res, true);

        // 根据极验返回的用户验证状态
        return  $obj;
    }

    private function request($url, $postdata)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CAINFO, FRAMEWORK_DIR . '/Writable/cacert.pem');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
