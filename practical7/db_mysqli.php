<?php

$dbHost = getenv('STUDENTHUB_DB_HOST') ?: '127.0.0.1';
$dbName = getenv('STUDENTHUB_DB_NAME') ?: 'studenthub';
$dbUser = getenv('STUDENTHUB_DB_USER') ?: 'root';
$dbPassword = getenv('STUDENTHUB_DB_PASSWORD') ?: '';

$mysqli = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);

if ($mysqli->connect_errno) {
    error_log('StudentHub MySQLi connection failed: ' . $mysqli->connect_error);
    http_response_code(500);
    exit('Database connection failed. Import practical8/studenthub.sql and check the local MySQL settings.');
}

$mysqli->set_charset('utf8mb4');
