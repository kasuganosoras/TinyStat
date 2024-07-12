<?php
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line\n");
}

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/includes/functions.php');
require_once(__DIR__ . '/includes/database.php');

$pdo = Database::getConnection();
$action = isset($argv[1]) ? strtolower($argv[1]) : '';
if ($action) {
    switch($action) {
        case 'createuser':
            while(true) {
                echo 'Username: ';
                $username = trim(fgets(STDIN));
                if (strlen($username) < 4) {
                    echo "Username must be at least 4 characters.\n";
                    continue;
                }
                $stmt = $pdo->prepare('SELECT * FROM `users` WHERE `username` = ?');
                $stmt->execute([$username]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result !== false) {
                    echo "Username already exists.\n";
                    continue;
                }
                break;
            }
            while(true) {
                echo 'Email: ';
                $email = trim(fgets(STDIN));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo "Invalid email address.\n";
                    continue;
                }
                $stmt = $pdo->prepare('SELECT * FROM `users` WHERE `email` = ?');
                $stmt->execute([$email]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result !== false) {
                    echo "Email address already exists.\n";
                    continue;
                }
                break;
            }
            while(true) {
                echo 'Password: ';
                $password = trim(fgets(STDIN));
                if (strlen($password) < 6) {
                    echo "Password must be at least 6 characters.\n";
                    continue;
                }
                echo 'Confirm password: ';
                $confirm = trim(fgets(STDIN));
                if ($password !== $confirm) {
                    echo "Passwords do not match.\n";
                    continue;
                }
                break;
            }
            $salt = GenerateSalt();
            $password = password_hash($password . $salt, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO `users` (`username`, `email`, `password`, `salt`) VALUES (?, ?, ?, ?)');
            $stmt->execute([$username, $email, $password, $salt]);
            echo "User created successfully.\n";
            break;
        case 'changepassword':
            while(true) {
                echo 'Username: ';
                $username = trim(fgets(STDIN));
                if (strlen($username) < 4) {
                    echo "Username must be at least 4 characters.\n";
                    continue;
                }
                $stmt = $pdo->prepare('SELECT * FROM `users` WHERE `username` = ?');
                $stmt->execute([$username]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result === false) {
                    echo "Username does not exist.\n";
                    continue;
                }
                break;
            }
            while(true) {
                echo 'Password: ';
                $password = trim(fgets(STDIN));
                if (strlen($password) < 6) {
                    echo "Password must be at least 6 characters.\n";
                    continue;
                }
                echo 'Confirm password: ';
                $confirm = trim(fgets(STDIN));
                if ($password !== $confirm) {
                    echo "Passwords do not match.\n";
                    continue;
                }
                break;
            }
            $salt = GenerateSalt();
            $password = password_hash($password . $salt, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE `users` SET `password` = ?, `salt` = ? WHERE `username` = ?');
            $stmt->execute([$password, $salt, $username]);
            echo "Password changed successfully.\n";
            break;
        case 'getconfig':
            $config = GetSiteConfig();
            foreach($config as $key => $value) {
                echo "{$key}: {$value}\n";
            }
            break;
        case 'setconfig':
            $config = GetSiteConfig();
            $key = isset($argv[2]) ? strtolower($argv[2]) : '';
            $value = isset($argv[3]) ? $argv[3] : '';
            if ($key && $value) {
                $config[$key] = $value;
                $stmt = $pdo->prepare('UPDATE `config` SET `value` = ? WHERE `key` = ?');
                $stmt->execute([$value, $key]);
                echo "Config updated successfully.\n";
            } else {
                echo "Usage: php console.php setconfig <key> <value>\n";
            }
            break;
        case 'install':
            echo "Are you sure you want to install? This will overwrite all existing data. (y/n): ";
            $confirm = trim(fgets(STDIN));
            if (strtolower($confirm) == 'y') {
                if (_E('DB_TYPE') == 'mysql') {
                    $sqlData = <<<EOF
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `config`;
CREATE TABLE `config`  (
    `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    PRIMARY KEY (`key`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;
DROP TABLE IF EXISTS `incidents`;
CREATE TABLE `incidents`  (
    `date` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `incident` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `user` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    PRIMARY KEY (`date`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;
DROP TABLE IF EXISTS `services`;
CREATE TABLE `services`  (
    `id` int(10) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `host` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `port` int(5) NULL DEFAULT NULL,
    `failure` int(5) NULL DEFAULT NULL,
    `status` int(5) NULL DEFAULT NULL,
    `response` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `extra` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `sort` int(10) NULL DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;
DROP TABLE IF EXISTS `status`;
CREATE TABLE `status`  (
    `id` bigint(30) NOT NULL AUTO_INCREMENT,
    `service` int(10) NOT NULL,
    `date` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `incident` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
    `id` int(10) NOT NULL AUTO_INCREMENT,
    `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `salt` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;
SET FOREIGN_KEY_CHECKS = 1;
EOF;
                    $pdo->exec($sqlData);
                } else {
                    $sqlData = <<<EOF
DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
    `key` TEXT NOT NULL,
    `value` TEXT NULL DEFAULT NULL
);
DROP TABLE IF EXISTS `incidents`;
CREATE TABLE `incidents` (
    `date` TEXT NOT NULL,
    `incident` TEXT NULL DEFAULT NULL,
    `user` TEXT NULL DEFAULT NULL
);
DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    `name` TEXT NOT NULL,
    `type` TEXT NOT NULL,
    `host` TEXT NOT NULL,
    `port` INTEGER NULL DEFAULT NULL,
    `failure` INTEGER NULL DEFAULT NULL,
    `status` INTEGER NULL DEFAULT NULL,
    `response` TEXT NULL DEFAULT NULL,
    `extra` TEXT NULL DEFAULT NULL,
    `sort` INTEGER NULL DEFAULT NULL
);
DROP TABLE IF EXISTS `status`;
CREATE TABLE `status` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    `service` INTEGER NOT NULL,
    `date` TEXT NULL DEFAULT NULL,
    `status` TEXT NULL DEFAULT NULL,
    `incident` TEXT NULL DEFAULT NULL
);
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    `username` TEXT NOT NULL,
    `password` TEXT NOT NULL,
    `email` TEXT NOT NULL,
    `salt` TEXT NULL DEFAULT NULL
);
EOF;
                    $pdo->exec($sqlData);
                }
                echo "Database installed successfully.\n";
            }
            break;
        case 'testmail':
            echo "Input email address to send test email to: ";
            $email = trim(fgets(STDIN));
            SendEmail($email, 'Test Email', 'This is a test email.');
            echo "Test email sent successfully.\n";
            break;
        case 'testdiscord':
            SendDiscordCard('Test', 'Normal', 'This is a test message.');
            echo "Test Discord message sent successfully.\n";
            break;
        case 'testkook':
            SendKookCard('Test', 'Normal', 'This is a test message.');
            echo "Test Kook message sent successfully.\n";
            break;
        case 'testdingtalk':
            SendDingTalkMsg('Test', 'Normal', 'This is a test message.');
            echo "Test DingTalk message sent successfully.\n";
            break;
        case 'testwecom':
            SendWeComMsg('Test', 'Normal', 'This is a test message.');
            echo "Test WeCom message sent successfully.\n";
            break;
        case 'testfreemobile':
            SendFreeMobileMsg('Test', 'Normal', 'This is a test message.');
            echo "Test FreeMobile message sent successfully.\n";
            break;
        default:
            DisplayHelp();
            break;
    }
    exit;
} else {
    DisplayHelp();
    exit;
}

function DisplayHelp() {
    echo "Usage: php console.php <action>\n";
    echo "Actions:\n";
    echo "  createuser      Create a new user\n";
    echo "  changepassword  Change user password\n";
    echo "  getconfig       Get site config\n";
    echo "  setconfig       Set site config\n";
    echo "  install         Install database\n";
    echo "  testmail        Send test email\n";
    echo "  testdiscord     Send test Discord message\n";
    echo "  testkook        Send test Kook message\n";
    echo "  testdingtalk    Send test DingTalk message\n";
    echo "  testwecom       Send test WeCom message\n";
    echo "  testfreemobile  Send test FreeMobile message\n";
}