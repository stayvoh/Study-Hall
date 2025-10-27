<?php
class Database {
    private static $pdo = null;

    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            $host = getenv('DB_HOST') ?: 'db';          // service name in docker-compose.yml
            $db   = getenv('DB_NAME') ?: 'studyhall';
            $user = getenv('DB_USER') ?: 'studyhall';
            $pass = getenv('DB_PASS') ?: 'change_me';
            $charset = "utf8mb4";

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            self::$pdo = new PDO($dsn, $user, $pass, $options);
            
        }
        return self::$pdo;
    }
}
?>