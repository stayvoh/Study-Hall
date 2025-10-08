<?php
declare(strict_types=1);

/**
 * Central PDO connection (uses docker-compose env).
 */
$DB_HOST = getenv('DB_HOST') ?: 'db';
$DB_NAME = getenv('DB_NAME') ?: 'studyhall';
$DB_USER = getenv('DB_USER') ?: 'studyhall';
$DB_PASS = getenv('DB_PASS') ?: 'change_me'; // dont forget smh

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (Throwable $e) {
  http_response_code(500);
  echo "<pre>DB connection failed: " . htmlspecialchars($e->getMessage()) . "</pre>";
  exit;
}
