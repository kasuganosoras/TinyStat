<?php
class FreeMobile {

    public string $user;
    public string $pass;

    public function __construct($user, $pass)
    {
        $this->user = $user;
        $this->pass = $pass;
    }

    public function sendMessage($message)
    {
        $curl = curl_init(sprintf('https://smsapi.free-mobile.fr/sendmsg?user=%s&pass=%s&msg=%s',
            urlencode($this->user), urlencode($this->pass), urlencode($message)));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
		return curl_exec($curl);
    }
}
