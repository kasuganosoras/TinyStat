<?php
class Database {
    protected static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            self::initConnection();
        }
        try {
            $errLvl = error_reporting(0);
            self::$conn->query("SELECT 1");
        } catch (PDOException $e) {
            self::initConnection();
        }
        error_reporting($errLvl);
        return self::$conn;
    }

    protected static function initConnection() {
        try {
            if (_E('DB_TYPE') == 'sqlite') {
                $fileName = __DIR__ . "/../" . _E('DB_NAME');
                self::$conn = new PDO("sqlite:" . $fileName);
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return;
            }
            $dsn = sprintf("mysql:host=%s;dbname=%s;charset=utf8mb4", _E('DB_HOST'), _E('DB_NAME'));
            self::$conn = new PDO($dsn, _E('DB_USER'), _E('DB_PASS'));
            self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Failed to connect to database: " . $e->getMessage());
        }
    }
}
