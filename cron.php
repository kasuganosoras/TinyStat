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
        PrintLog("Service {$service['name']} ({$service['host']}) is " . ($result ? 'online' : 'offline'));
        if ($result) {
            UpdateServiceStatus($service['id'], 'normal');
            $stmt = $pdo->prepare('UPDATE `services` SET `failure` = 0 WHERE `id` = ?');
            $stmt->execute([$service['id']]);
        } else {
            $stmt = $pdo->prepare('UPDATE `services` SET `failure` = `failure` + 1 WHERE `id` = ?');
            $stmt->execute([$service['id']]);
            if ($service['failure'] >= MAX_FAILURE) {
                UpdateServiceStatus($service['id'], 'error');
            } else {
                UpdateServiceStatus($service['id'], 'warning');
            }
        }
    }
}

function UpdateServiceStatus($id, $status) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM `status` WHERE `service` = ? AND `date` = ?');
    $stmt->execute([$id, date('Y.m.d')]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result === false) {
        $stmt = $pdo->prepare('INSERT INTO `status` (`service`, `date`, `status`) VALUES (?, ?, ?)');
        $stmt->execute([$id, date('Y.m.d'), $status]);
    } else {
        if ($result['status'] == 'normal' || $status == 'normal' || ($result['status'] == 'warning' && $status == 'error')) {
            $stmt = $pdo->prepare('UPDATE `status` SET `status` = ? WHERE `id` = ?');
            $stmt->execute([$status, $result['id']]);
        }
    }
}

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed in CLI mode.');
}

while (true) {
    CheckServices();
    sleep(CHECK_INTERVAL);
}