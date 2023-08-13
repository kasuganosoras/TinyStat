<?php
include(__DIR__ . "/config.php");
include(__DIR__ . "/includes/functions.php");
include(__DIR__ . "/includes/database.php");

$pdo = Database::getConnection();

function CheckServices() {
    global $pdo;
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT * FROM `services`');
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($services as $service) {
        switch($service['type']) {
            case 'http':
                $url      = $service['host'];
                $status   = $service['status'] ?: Intval($service['status']);
                $response = $service['response'];
                $extra    = json_decode($service['extra'], true) ?: [];
                $result   = CheckHttpService($url, $status, $response, $extra);
                break;
            case 'tcp':
                $host   = $service['host'];
                $port   = Intval($service['port']);
                $result = CheckTcpService($host, $port);
                break;
            case 'udp':
                $host   = $service['host'];
                $port   = Intval($service['port']);
                $result = CheckUdpService($host, $port);
                break;
            case 'icmp':
                $host   = $service['host'];
                $result = CheckIcmpService($host);
                break;
            default:
                $result = false;
                break;
        }
        PrintLog("Service {$service['name']} ({$service['host']}) is " . ($result['success'] ? 'online' : 'offline') . (!$result['success'] ? ", reason: {$result['reason']}" : ''));
        if ($result['success']) {
            UpdateServiceStatus($service['id'], 'normal');
            $stmt = $pdo->prepare('UPDATE `services` SET `failure` = 0 WHERE `id` = ?');
            $stmt->execute([$service['id']]);
        } else {
            $stmt = $pdo->prepare('UPDATE `services` SET `failure` = `failure` + 1 WHERE `id` = ?');
            $stmt->execute([$service['id']]);
            if ($service['failure'] >= _E('ERR_FAILURE')) {
                UpdateServiceStatus($service['id'], 'error', $result['reason']);
            } elseif ($service['failure'] >= _E('WARN_FAILURE')) {
                UpdateServiceStatus($service['id'], 'warning', $result['reason']);
            }
        }
    }
}

function UpdateServiceStatus($id, $status, $reason = null) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM `status` WHERE `service` = ? AND `date` = ?');
    $stmt->execute([$id, date('Y.m.d')]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result === false) {
        $stmt = $pdo->prepare('INSERT INTO `status` (`service`, `date`, `status`, `incident`) VALUES (?, ?, ?, ?)');
        $stmt->execute([$id, date('Y.m.d'), $status, $reason]);
    } else {
        /* if ($result['status'] == 'normal' || $status == 'normal' || ($result['status'] == 'warning' && $status == 'error')) {
            if ($reason) {
                $stmt = $pdo->prepare('UPDATE `status` SET `status` = ?, `incident` = ? WHERE `id` = ?');
                $stmt->execute([$status, $reason, $result['id']]);
            } else {
                $stmt = $pdo->prepare('UPDATE `status` SET `status` = ? WHERE `id` = ?');
                $stmt->execute([$status, $result['id']]);
            }
            if ($status !== $result['status']) {
                SendNotification($id, $status, $reason);
            }
        } */
        // 服务状态发生变化时，更新状态和异常记录
        if ($result['status'] !== $status) {
            $incidents = json_decode($result['incident'], true) ?: [];
            // 判断有无异常的 end 为空，如果有则更新 end 为当前时间
            foreach($incidents as $key => $incident) {
                if ($incident['end'] === null) {
                    $incidents[$key]['end'] = time();
                    break;
                }
            }
            // 新增一条异常记录
            $incidents[] = [
                'start'  => time(),
                'end'    => null,
                'status' => $status,
                'reason' => $reason
            ];
            $stmt = $pdo->prepare('UPDATE `status` SET `status` = ?, `incident` = ? WHERE `id` = ?');
            $stmt->execute([$status, json_encode($incidents), $result['id']]);
            SendNotification($id, $status, $reason);
        }
    }
}

function SendNotification($id, $status, $reason = null) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM `services` WHERE `id` = ?');
    $stmt->execute([$id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($service === false) {
        return;
    }
    $stmt = $pdo->prepare('SELECT * FROM `users`');
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($users as $user) {
        if ($user['email'] && _E('NOTIFY_EMAIL')) {
            SendEmail($user['email'], _UF('notify.email.title', $service['name']), GetMailTemplate($service['name'], $status, $reason));
        }
    }
    $statusText = _U("status.label.{$status}");
    if (_E('NOTIFY_DISCORD')) {
        SendDiscordCard($service['name'], $statusText, $reason);
    }
    if (_E('NOTIFY_KOOK')) {
        SendKookCard($service['name'], $statusText, $reason);
    }
    if (_E('NOTIFY_DINGTALK')) {
        SendDingTalkMsg($service['name'], $statusText, $reason);
    }
    if (_E('NOTIFY_WECOM')) {
        SendWeComMsg($service['name'], $statusText, $reason);
    }
}

function GetMailTemplate($name, $status, $reason) {
    $statusText = _U("status.label.{$status}");
    $reason = $reason ?? _U('notify.reason.none');
    return _UF('notify.email.content', $name, $statusText, $reason);
}

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed in CLI mode.');
}

while (true) {
    CheckServices();
    sleep(_E('CHECK_INTERVAL'));
}