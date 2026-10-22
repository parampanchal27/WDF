<?php
require __DIR__ . DIRECTORY_SEPARATOR . 'db_mysqli.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Invalid request method.');
}

$fullName = trim((string) ($_POST['fullName'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$mobile = trim((string) ($_POST['mobile'] ?? ''));
$course = trim((string) ($_POST['course'] ?? ''));
$semester = trim((string) ($_POST['year'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirmPassword'] ?? '');
$gender = trim((string) ($_POST['gender'] ?? ''));
$errors = [];

if (!preg_match("/^[\p{L} .'-]{2,100}$/u", $fullName)) {
    $errors[] = 'Enter a valid full name.';
}
if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
    $errors[] = 'Username must be 3-30 characters and use only letters, numbers, dot, underscore, or hyphen.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    $errors[] = 'Enter a valid email address.';
}
if (!preg_match('/^\d{10}$/', $mobile)) {
    $errors[] = 'Enter a valid 10-digit mobile number.';
}
if (!in_array($course, ['btech-it', 'btech-cse'], true)) {
    $errors[] = 'Select a valid course.';
}
if (!in_array($semester, ['3rd-semester', '4th-semester'], true)) {
    $errors[] = 'Select a valid semester.';
}
if (strlen($password) < 8 || !preg_match('/\d/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
    $errors[] = 'Password must contain at least 8 characters, one number, and one symbol.';
}
if (!hash_equals($password, $confirmPassword)) {
    $errors[] = 'Passwords must match.';
}
if (!in_array($gender, ['female', 'male', 'other'], true) || !isset($_POST['terms'])) {
    $errors[] = 'Select a gender and accept the terms and conditions.';
}

if (!$errors) {
    $duplicate = $mysqli->prepare('SELECT student_id FROM students WHERE email = ? OR username = ? LIMIT 1');
    $duplicate->bind_param('ss', $email, $username);
    $duplicate->execute();
    $duplicate->store_result();
    if ($duplicate->num_rows > 0) {
        $errors[] = 'That email address or username is already registered.';
    }
    $duplicate->close();
}

if (!$errors) {
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $courseName = $course === 'btech-it' ? 'B.Tech Information Technology' : 'B.Tech Computer Science';
    $semesterNumber = $semester === '3rd-semester' ? 3 : 4;
    $statement = $mysqli->prepare(
        'INSERT INTO students (full_name, username, email, mobile, course, semester, password_hash)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->bind_param('sssssis', $fullName, $username, $email, $mobile, $courseName, $semesterNumber, $passwordHash);
    if (!$statement->execute()) {
        $errors[] = $mysqli->errno === 1062
            ? 'That email address or username is already registered.'
            : 'Unable to create the account right now.';
    }
    $statement->close();
}

if ($errors) {
    http_response_code(422);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Registration error</title><link rel="stylesheet" href="../styles.css"></head><body><main><h1>Registration failed</h1><ul>';
    foreach ($errors as $error) {
        echo '<li>' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    echo '</ul><p><a href="../register.html">Return to registration</a></p></main></body></html>';
    exit;
}

header('Location: ../Login.html?registered=1');
exit;
