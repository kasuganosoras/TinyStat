<?php
class WeCom {

    private string $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    public function sendMessage($message)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, sprintf("https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=%s", $this->key));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($message));
        return curl_exec($curl);
    }
}
