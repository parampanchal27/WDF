<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Invalid request method.');
}

$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'users.json';
$user = null;

if (filter_var($email, FILTER_VALIDATE_EMAIL) && is_readable($filePath)) {
    $contents = file_get_contents($filePath);
    $users = $contents === false ? null : json_decode($contents, true);
    if (is_array($users)) {
        foreach ($users as $candidate) {
            if (($candidate['email'] ?? '') === $email && password_verify($password, $candidate['passwordHash'] ?? '')) {
                $user = $candidate;
                break;
            }
        }
    }
}

if ($user === null) {
    http_response_code(401);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Login failed</title><link rel="stylesheet" href="/styles.css"></head><body><main><h1>Login failed</h1><p>Invalid email or password.</p><p><a href="/Login.html">Try again</a> | <a href="/register.html">Create an account</a></p></main></body></html>';
    exit;
}

session_regenerate_id(true);
$_SESSION['user'] = [
    'fullName' => $user['fullName'],
    'username' => $user['username'],
    'email' => $user['email'],
];

header('Location: /Index.html');
exit;
