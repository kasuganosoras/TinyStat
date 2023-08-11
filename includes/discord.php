<?php
class Discord {
	
	public string $channel;
	public string $token;
	
	public function __construct($channel, $token)
	{
		$this->channel = $channel;
		$this->token = $token;
	}
	
	public function sendMessage($message)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, sprintf("https://discord.com/api/webhooks/%s/%s", $this->channel, $this->token));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($message));
		return curl_exec($curl);
	}
	
	public function sendSingleMessage($message)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, sprintf("https://discord.com/api/webhooks/%s/%s", $this->channel, $this->token));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(["content" => $message]));
		return curl_exec($curl);
	}
}
