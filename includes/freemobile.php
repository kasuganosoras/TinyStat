<?php
class FreeMobile {

    public string $user;
    public string $pass;

    public function __construct($user, $pass) {
        $this->user = $user;
        $this->pass = $pass;
    }

    public function sendMessage($message) {
        //encode message to %20
        $message = urlencode($message);
        $curl = curl_init('https://smsapi.free-mobile.fr/sendmsg?user=' . $this->user . '&pass=' . $this->pass . '&msg=' . $message);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
		return curl_exec($curl);
    }
}