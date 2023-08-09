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
            $dsn = sprintf("mysql:host=%s;dbname=%s;charset=utf8mb4", DB_HOST, DB_NAME);
            self::$conn = new PDO($dsn, DB_USER, DB_PASS);
            self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Failed to connect to database: " . $e->getMessage());
        }
    }
}
