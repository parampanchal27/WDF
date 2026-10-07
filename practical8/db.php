<?php

$dbHost = getenv('STUDENTHUB_DB_HOST') ?: '127.0.0.1';
$dbName = getenv('STUDENTHUB_DB_NAME') ?: 'studenthub';
$dbUser = getenv('STUDENTHUB_DB_USER') ?: 'root';
$dbPassword = getenv('STUDENTHUB_DB_PASSWORD') ?: '';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    error_log('StudentHub database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Database connection failed. Import studenthub.sql and check the local MySQL settings.');
}
