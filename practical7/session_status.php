<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'authenticated' => isset($_SESSION['user']['email']),
    'user' => isset($_SESSION['user'])
        ? [
            'fullName' => $_SESSION['user']['fullName'] ?? '',
            'email' => $_SESSION['user']['email'] ?? '',
        ]
        : null,
], JSON_UNESCAPED_UNICODE);
