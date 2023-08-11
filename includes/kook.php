<?php
class Kook {

    private string $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function sendGroupMsg($channelId, $content, $type)
    {
        $url = "https://www.kookapp.cn/api/v3/message/create";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
            "channel_id" => $channelId,
            "content" => $content,
            "type" => $type
        ]));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bot {$this->token}"
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    public function sendPrivateMsg($target, $content, $type)
    {
        $url = "https://www.kookapp.cn/api/v3/direct-message/create";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
            "target_id" => $target,
            "content" => $content,
            "type" => $type
        ]));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bot {$this->token}"
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    public function getCardMessage($title, $data, $cols)
    {
        $fields = [];
        foreach($data as $text) {
            $fields[] = [
                "type" => "kmarkdown",
                "content" => "**{$text[0]}**\n{$text[1]}"
            ];
        }
        $payload = [
            [
                "type"    => "card",
                "theme"   => "secondary",
                "size"    => "lg",
                "modules" => [
                    [
                        "type" => "section",
                        "text" => [
                            "type"    => "kmarkdown",
                            "content" => "**$title**"
                        ]
                    ],
                    [
                        "type" => "divider"
                    ],
                    [
                        "type" => "section",
                        "text" => [
                            "type"   => "paragraph",
                            "cols"   => $cols ?? 2,
                            "fields" => $fields
                        ]
                    ],
                    [
                        "type"     => "context",
                        "elements" => [
                            [
                                "type"    => "kmarkdown",
                                "content" => "Powered by TinyStat"
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return json_encode($payload);
    }

    public function getImageCardMessage($title, $url)
    {
        $payload = [
            [
                "type"    => "card",
                "theme"   => "secondary",
                "size"    => "lg",
                "modules" => [
                    [
                        "type" => "section",
                        "text" => [
                            "type"    => "kmarkdown",
                            "content" => "**$title**"
                        ]
                    ],
                    [
                        "type" => "divider"
                    ],
                    [
                        "type" => "container",
                        "elements" => [
                            [
                                "type" => "image",
                                "src" => $url
                            ],
                        ],
                    ],
                    [
                        "type"     => "context",
                        "elements" => [
                            [
                                "type"    => "kmarkdown",
                                "content" => "Powered by TinyStat"
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return json_encode($payload);
    }
}