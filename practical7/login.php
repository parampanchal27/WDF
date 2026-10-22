<?php
require __DIR__ . DIRECTORY_SEPARATOR . 'db_mysqli.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

startStudentHubSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Invalid request method.');
}

$identifier = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
$user = null;

if ($identifier !== '' && $password !== '') {
    $statement = $mysqli->prepare(
        'SELECT student_id, full_name, username, email, role, password_hash
         FROM students WHERE email = ? OR username = ? LIMIT 1'
    );
    $statement->bind_param('ss', $identifier, $identifier);
    $statement->execute();
    $result = $statement->get_result();
    $candidate = $result->fetch_assoc();
    $statement->close();
    if ($candidate && password_verify($password, $candidate['password_hash'])) {
        $user = $candidate;
    }
}

if ($user === null) {
    http_response_code(401);
    exit('<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Login failed</title><link rel="stylesheet" href="../styles.css"></head><body><main><h1>Login failed</h1><p>Invalid email/username or password.</p><p><a href="../Login.html">Try again</a> | <a href="../register.html">Create an account</a></p></main></body></html>');
}

session_regenerate_id(true);
$_SESSION['user'] = [
    'id' => (int) $user['student_id'],
    'fullName' => $user['full_name'],
    'username' => $user['username'],
    'email' => $user['email'],
    'role' => $user['role'],
];
$_SESSION['last_activity'] = time();

if (!empty($_POST['remember'])) {
    $selector = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $validator);
    $expiresAt = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * 30));
    $tokenStatement = $mysqli->prepare(
        'INSERT INTO remember_tokens (student_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)'
    );
    $studentId = (int) $user['student_id'];
    $tokenStatement->bind_param('isss', $studentId, $selector, $tokenHash, $expiresAt);
    $tokenStatement->execute();
    $tokenStatement->close();
    setcookie('studenthub_remember', $selector . ':' . $validator, [
        'expires' => time() + (60 * 60 * 24 * 30),
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

$destination = $user['role'] === 'admin' ? 'admin.php' : '../Index.php';
header('Location: ' . $destination);
exit;
