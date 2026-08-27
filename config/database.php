<?php
class Database {
    private static ?PDO $conn = null;

    public static function connect(): PDO {
        if (self::$conn === null) {
            $host    = getenv('MYSQLHOST')     ?: 'localhost';
            $db      = getenv('MYSQLDATABASE') ?: 'smart_irrigation';
            $user    = getenv('MYSQLUSER')     ?: 'root';
            $pass    = getenv('MYSQLPASSWORD') ?: '';
            $port    = getenv('MYSQLPORT')     ?: '3306';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            self::$conn = new PDO($dsn, $user, $pass, $options);
        }
        return self::$conn;
    }
}
