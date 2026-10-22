<?php
require __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
startStudentHubSession();

if (!empty($_SESSION['user']['id']) && time() - (int) ($_SESSION['last_activity'] ?? time()) > STUDENTHUB_SESSION_TIMEOUT) {
    expireStudentHubSession();
}

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'authenticated' => isset($_SESSION['user']['id']),
    'user' => isset($_SESSION['user'])
        ? [
            'fullName' => $_SESSION['user']['fullName'] ?? '',
            'email' => $_SESSION['user']['email'] ?? '',
        ]
        : null,
], JSON_UNESCAPED_UNICODE);
