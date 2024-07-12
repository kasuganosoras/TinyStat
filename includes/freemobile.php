<?php
class FreeMobile {

    public string $user;
    public string $pass;

    public function __construct($user, $pass) {
        $this->user = $user;
        $this->pass = $pass;
    }

    public function sendMessage($message) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://smsapi.free-mobile.fr/sendmsg?user=" . $this->user . "&pass=" . $this->pass . "&msg=" . $message);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($curl);
    }
}