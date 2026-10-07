<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Invalid request method. Please submit the form using POST.');
}

$submittedToken = (string) ($_POST['csrf_token'] ?? '');
$sessionToken = (string) ($_SESSION['csrf_token'] ?? '');

if ($sessionToken === '' || $submittedToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    http_response_code(403);
    exit('Invalid CSRF token. Please return to the form and try again.');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$errors = [];

if ($name === '') {
    $errors[] = 'Full name is required.';
} elseif (mb_strlen($name) > 80) {
    $errors[] = 'Full name must not exceed 80 characters.';
} elseif (!preg_match("/^[\p{L} .'-]+$/u", $name)) {
    $errors[] = 'Full name contains invalid characters.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    $errors[] = 'Please enter a valid email address.';
}

if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
    $errors[] = 'Please enter a valid phone number.';
}

if ($message === '') {
    $errors[] = 'Message is required.';
} elseif (mb_strlen($message) > 1000) {
    $errors[] = 'Message must not exceed 1000 characters.';
}

if ($errors) {
    $_SESSION['form_data'] = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'message' => $message,
    ];
    $_SESSION['form_errors'] = $errors;
    header('Location: index.php');
    exit;
}

$dataDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$filePath = $dataDirectory . DIRECTORY_SEPARATOR . 'submissions.json';

if (!is_dir($dataDirectory) && !mkdir($dataDirectory, 0755, true)) {
    http_response_code(500);
    exit('Unable to create the data directory.');
}

$fileHandle = fopen($filePath, 'c+b');
if ($fileHandle === false) {
    http_response_code(500);
    exit('Unable to open the storage file.');
}

if (!flock($fileHandle, LOCK_EX)) {
    fclose($fileHandle);
    http_response_code(500);
    exit('Unable to lock the storage file.');
}

$contents = stream_get_contents($fileHandle);
$records = [];

if ($contents !== false && trim($contents) !== '') {
    $decoded = json_decode($contents, true);
    if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
        flock($fileHandle, LOCK_UN);
        fclose($fileHandle);
        http_response_code(500);
        exit('The JSON storage file contains invalid data.');
    }
    $records = $decoded;
}

$records[] = [
    'date' => date('Y-m-d H:i:s'),
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'message' => $message,
];

$encoded = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$recordSaved = $encoded !== false
    && ftruncate($fileHandle, 0)
    && rewind($fileHandle)
    && fwrite($fileHandle, $encoded . PHP_EOL) !== false;

flock($fileHandle, LOCK_UN);
fclose($fileHandle);

if (!$recordSaved) {
    http_response_code(500);
    exit('Unable to save the submitted record.');
}

header('Location: success.php');
exit;
