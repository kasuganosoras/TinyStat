<?php
include(__DIR__ . "/../locales/" . LOCALE . ".php");
function IcmpPing($host) {
    $package = hex2bin("080000005243430001");
    for($i = strlen($package); $i < 64; $i++) {
        $package .= chr(0);
    }
    $tmp = unpack("n*", $package);
    $sum = array_sum($tmp);
    $sum = ($sum >> 16) + ($sum & 0xFFFF);
    $sum = $sum + ($sum >> 16);
    $sum = ~ $sum;
    $checksum   = pack("n*", $sum);
    $package[2] = $checksum[0];
    $package[3] = $checksum[1];
    $socket     = socket_create(AF_INET, SOCK_RAW, getprotobyname('icmp'));
    $start      = microtime(true);
    socket_sendto($socket, $package, strlen($package), 0, $host, 0);
    $read   = array($socket);
    $write  = null;
    $except = null;
    $select = socket_select($read, $write, $except, 5);
    $error  = null;
    if ($select === false) {
        $error = 'Failed to create socket: ' . socket_strerror(socket_last_error());
        socket_close($socket);
    } else if($select === 0) {
        $error = "Request timeout";
        socket_close($socket);
    }
    if($error !== null) {
        return $error;
    }
    socket_recvfrom($socket, $recv, 65535, 0, $host, $port);
    $end      = microtime(true);
    $recv     = unpack("C*", $recv);
    $length   = count($recv) - 20;
    $ttl      = $recv[9];
    $seq      = $recv[28];
    $duration = round(($end - $start) * 1000,3);
    socket_close($socket);
    return [
        'length' => $length,
        'host'   => $host,
        'seq'    => $seq,
        'ttl'    => $ttl,
        'time'   => $duration,
    ];
}

function CheckHttpService($url, $status, $response, $extra) {
    $errLvl = error_reporting(0);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, TIMEOUT_SEC);
    curl_setopt($curl, CURLOPT_TIMEOUT, TIMEOUT_SEC);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $extra['ssl_verify'] ?? false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $extra['ssl_verify'] ? 2 : 0);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $extra['method'] ?? 'GET');
    curl_setopt($curl, CURLOPT_POSTFIELDS, $extra['data'] ?? '');
    curl_setopt($curl, CURLOPT_HTTPHEADER, $extra['headers'] ?? []);
    $result = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    error_reporting($errLvl);
    if($status && $httpCode != $status) {
        PrintLog("HTTP status code error: {$httpCode} ({$url}), expected {$status}");
        return false;
    }
    if(!empty($response) && strpos($result, $response) === false) {
        PrintLog("HTTP response error: {$response} ({$url}), expected {$response}");
        return false;
    }
    return true;
}

function CheckTcpService($host, $port) {
    $fp = @fsockopen($host, $port, $errno, $errstr, TIMEOUT_SEC);
    if(!$fp) {
        return false;
    }
    fclose($fp);
    return true;
}

function CheckUdpService($host, $port) {
    $fp = @fsockopen("udp://{$host}", $port, $errno, $errstr, TIMEOUT_SEC);
    if(!$fp) {
        return false;
    }
    fclose($fp);
    return true;
}

function CheckIcmpService($host) {
    $result = IcmpPing($host);
    if (is_string($result)) {
        return false;
    }
    return true;
}

function PrintLog() {
    $args = func_get_args();
    $str = implode(' ', $args);
    echo sprintf('[%s] %s', date('Y-m-d H:i:s'), $str) . PHP_EOL;
}

function GetSiteConfig() {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM `config`');
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $config = [];
    foreach($result as $row) {
        $config[$row['key']] = $row['value'];
    }
    return $config;
}

function GetUserByUsername($username) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM `users` WHERE `username` = ?');
    $stmt->execute([$username]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}

function GetUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM `users` WHERE `id` = ?');
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}

function GetUserByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM `users` WHERE `email` = ?');
    $stmt->execute([$email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}

function GetServices() {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM `services`');
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $services = [];
    foreach($result as $row) {
        $row['extra'] = json_decode($row['extra'], true) ?: [];
        $services[$row['id']] = $row;
    }
    return $services;
}

function GenerateSalt() {
    return bin2hex(random_bytes(16));
}

function CreateService($data) {
    global $pdo;
    if (!isset($data['name']) || empty($data['name'])) {
        return ['code' => 403, 'message' => _U('error.name.required')];
    }
    if (mb_strlen($data['name']) > 64) {
        return ['code' => 403, 'message' => _U('error.name.length')];
    }
    if ($data['type'] == 'tcp' || $data['type'] == 'udp') {
        if (!isset($data['port'])) {
            return ['code' => 403, 'message' => _U('error.port.required')];
        }
        $port = Intval($data['port']);
        if (!$port) {
            return ['code' => 403, 'message' => _U('error.port.number')];
        }
        if ($port < 1 || $port > 65535) {
            return ['code' => 403, 'message' => _U('error.port.range')];
        }
    } else if ($data['type'] == 'http') {
        if (isset($data['status']) && !empty($data['status'])) {
            $status = Intval($data['status']);
            if (!$status) {
                return ['code' => 403, 'message' => _U('error.status.number')];
            }
            if ($status < 100 || $status > 599) {
                return ['code' => 403, 'message' => _U('error.status.range')];
            }
        }
        if (isset($data['response'])) {
            if (mb_strlen($data['response']) > 1024) {
                return ['code' => 403, 'message' => _U('error.response.length')];
            }
        }
    } else if ($data['type'] !== 'icmp') {
        return ['code' => 403, 'message' => _U('error.type.invalid')];
    }
    $headers = [];
    if (isset($data['headers']) && !empty($data['headers'])) {
        $exp = explode("\n", $data['headers']);
        foreach($exp as $header) {
            $headers[] = trim($header);
        }
    }
    $name = $data['name'];
    $type = $data['type'];
    $host = $data['host'];
    $port = isset($data['port']) && !empty($data['port']) ? Intval($data['port']) : 0;
    $status = isset($data['status']) && !empty($data['status']) ? Intval($data['status']) : null;
    $response = isset($data['response']) && !empty($data['response']) ? $data['response'] : '';
    $stmt = $pdo->prepare('INSERT INTO `services` (`name`, `type`, `host`, `port`, `status`, `response`, `extra`) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $name,
        $type,
        $host,
        $port,
        $status,
        $response,
        json_encode([
            'ssl_verify' => $data['ssl_verify'] ?? false,
            'method'     => $data['method'] ?? 'GET',
            'data'       => $data['data'] ?? '',
            'headers'    => $headers,
        ]),
    ]);
    return ['code' => 200, 'message' => _U('success.service.created')];
}

function EditService($data) {
    global $pdo;
    if (!isset($data['id']) || empty($data['id'])) {
        return ['code' => 403, 'message' => _U('error.id.required')];
    }
    $id = Intval($data['id']);
    if (!$id) {
        return ['code' => 403, 'message' => _U('error.id.number')];
    }
    $stmt = $pdo->prepare('SELECT * FROM `services` WHERE `id` = ?');
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result === false) {
        return ['code' => 403, 'message' => _U('error.id.notexist')];
    }
    if (!isset($data['name']) || empty($data['name'])) {
        return ['code' => 403, 'message' => _U('error.name.required')];
    }
    if (mb_strlen($data['name']) > 64) {
        return ['code' => 403, 'message' => _U('error.name.length')];
    }
    if ($data['type'] == 'tcp' || $data['type'] == 'udp') {
        if (!isset($data['port'])) {
            return ['code' => 403, 'message' => _U('error.port.required')];
        }
        $port = Intval($data['port']);
        if (!$port) {
            return ['code' => 403, 'message' => _U('error.port.number')];
        }
        if ($port < 1 || $port > 65535) {
            return ['code' => 403, 'message' => _U('error.port.range')];
        }
    } else if ($data['type'] == 'http') {
        if (isset($data['status']) && !empty($data['status'])) {
            $status = Intval($data['status']);
            if (!$status) {
                return ['code' => 403, 'message' => _U('error.status.number')];
            }
            if ($status < 100 || $status > 599) {
                return ['code' => 403, 'message' => _U('error.status.range')];
            }
        }
        if (isset($data['response'])) {
            if (mb_strlen($data['response']) > 1024) {
                return ['code' => 403, 'message' => _U('error.response.length')];
            }
        }
    } else if ($data['type'] !== 'icmp') {
        return ['code' => 403, 'message' => _U('error.type.invalid')];
    }
    $headers = [];
    if (isset($data['headers']) && !empty($data['headers'])) {
        $exp = explode("\n", $data['headers']);
        foreach($exp as $header) {
            $headers[] = trim($header);
        }
    }
    $name = $data['name'];
    $type = $data['type'];
    $host = $data['host'];
    $port = isset($data['port']) && !empty($data['port']) ? Intval($data['port']) : 0;
    $status = isset($data['status']) && !empty($data['status']) ? Intval($data['status']) : null;
    $response = isset($data['response']) && !empty($data['response']) ? $data['response'] : '';
    $stmt = $pdo->prepare('UPDATE `services` SET `name` = ?, `type` = ?, `host` = ?, `port` = ?, `status` = ?, `response` = ?, `extra` = ? WHERE `id` = ?');
    $stmt->execute([
        $name,
        $type,
        $host,
        $port,
        $status,
        $response,
        json_encode([
            'ssl_verify' => $data['ssl_verify'] ?? false,
            'method'     => $data['method'] ?? 'GET',
            'data'       => $data['data'] ?? '',
            'headers'    => $headers,
        ]),
        $id,
    ]);
    return ['code' => 200, 'message' => _U('success.service.updated')];
}

function DeleteService($id) {
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM `services` WHERE `id` = ?');
    $stmt->execute([$id]);
    $stmt = $pdo->prepare('DELETE FROM `status` WHERE `service` = ?');
    $stmt->execute([$id]);
    return ['code' => 200, 'message' => _U('success.service.deleted')];
}

function _C($key, $default = null) {
    global $config;
    if($config === null) {
        $config = GetSiteConfig();
    }
    return isset($config[$key]) ? $config[$key] : $default;
}

function _CE($key, $default = null) {
    $value = _C($key, $default);
    echo $value;
}

function _U($name) {
    global $locale;
    return $locale[LOCALE][$name] ?? $name;
}

function _UE($name) {
    $value = _U($name);
    echo $value;
}
