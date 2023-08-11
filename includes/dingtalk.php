<?php
class DingTalk {

    private string $token;
    private string $secret;

    public function __construct($token, $secret)
    {
        $this->token = $token;
        $this->secret = $secret;
    }

    public function sendMessage($payload)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->getSignature());
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
        return curl_exec($curl);
    }

    public function sendMarkdownMessage($title, $text)
    {
        return $this->sendMessage([
            "msgtype" => "markdown",
            "markdown" => [
                "title" => $title,
                "text" => $text
            ]
        ]);
    }

    public function getSignature()
    {
        $timestamp = time() * 1000;
        $stringToSign = $timestamp . "\n" . $this->secret;
        $sign = hash_hmac('sha256', $stringToSign, $this->secret, true);
        $sign = urlencode(base64_encode($sign));
        return "https://oapi.dingtalk.com/robot/send?access_token={$this->token}&timestamp={$timestamp}&sign={$sign}";
    }
}