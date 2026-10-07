<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Invalid request method.');
}

$fullName = trim((string) ($_POST['fullName'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$mobile = trim((string) ($_POST['mobile'] ?? ''));
$course = trim((string) ($_POST['course'] ?? ''));
$year = trim((string) ($_POST['year'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirmPassword'] ?? '');
$gender = trim((string) ($_POST['gender'] ?? ''));
$termsAccepted = isset($_POST['terms']);

$errors = [];

if (mb_strlen($fullName) < 2 || !preg_match("/^[\p{L} .'-]+$/u", $fullName)) {
    $errors[] = 'Enter a valid full name.';
}
if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
    $errors[] = 'Username must be 3-30 characters and use only letters, numbers, dot, underscore, or hyphen.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Enter a valid email address.';
}
if (!preg_match('/^\d{10}$/', $mobile)) {
    $errors[] = 'Enter a valid 10-digit mobile number.';
}
if (!in_array($course, ['btech-it', 'btech-cse'], true)) {
    $errors[] = 'Select a valid course.';
}
if (!in_array($year, ['3rd-semester', '4th-semester'], true)) {
    $errors[] = 'Select a valid year or semester.';
}
if (strlen($password) < 8 || !preg_match('/\d/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
    $errors[] = 'Password must contain at least 8 characters, one number, and one symbol.';
}
if (!hash_equals($password, $confirmPassword)) {
    $errors[] = 'Passwords must match.';
}
if (!in_array($gender, ['female', 'male', 'other'], true)) {
    $errors[] = 'Select a gender.';
}
if (!$termsAccepted) {
    $errors[] = 'You must accept the terms and conditions.';
}

$dataDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$filePath = $dataDirectory . DIRECTORY_SEPARATOR . 'users.json';

if (!$errors) {
    if (!is_dir($dataDirectory) && !mkdir($dataDirectory, 0755, true)) {
        $errors[] = 'Unable to create the user storage directory.';
    } else {
        $fileHandle = fopen($filePath, 'c+b');
        if ($fileHandle === false || !flock($fileHandle, LOCK_EX)) {
            $errors[] = 'Unable to access user storage.';
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }
        } else {
            $contents = stream_get_contents($fileHandle);
            $users = [];
            if ($contents !== false && trim($contents) !== '') {
                $users = json_decode($contents, true);
                if (!is_array($users) || json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = 'User storage contains invalid JSON.';
                    $users = [];
                }
            }

            foreach ($users as $user) {
                if (($user['email'] ?? '') === $email || ($user['username'] ?? '') === $username) {
                    $errors[] = 'That email address or username is already registered.';
                    break;
                }
            }

            if (!$errors) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $users[] = [
                    'fullName' => $fullName,
                    'username' => $username,
                    'email' => $email,
                    'mobile' => $mobile,
                    'course' => $course,
                    'year' => $year,
                    'gender' => $gender,
                    'passwordHash' => $passwordHash,
                    'registeredAt' => date('Y-m-d H:i:s'),
                ];
                $encoded = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $saved = $encoded !== false && ftruncate($fileHandle, 0) && rewind($fileHandle)
                    && fwrite($fileHandle, $encoded . PHP_EOL) !== false;
                if (!$saved) {
                    $errors[] = 'Unable to save the new account.';
                }

                if (!$errors) {
                    try {
                        require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'practical8' . DIRECTORY_SEPARATOR . 'db.php';
                        $studentStatement = $pdo->prepare(
                            'INSERT INTO students
                                (full_name, username, email, mobile, course, semester, password_hash)
                             VALUES
                                (:full_name, :username, :email, :mobile, :course, :semester, :password_hash)'
                        );
                        $studentStatement->execute([
                            'full_name' => $fullName,
                            'username' => $username,
                            'email' => $email,
                            'mobile' => $mobile,
                            'course' => $course === 'btech-it'
                                ? 'B.Tech Information Technology'
                                : 'B.Tech Computer Science',
                            'semester' => $year === '3rd-semester' ? 3 : 4,
                            'password_hash' => $passwordHash,
                        ]);
                    } catch (Throwable $exception) {
                        error_log('StudentHub database registration failed: ' . $exception->getMessage());
                        $errors[] = 'Account could not be linked to the StudentHub database.';
                    }
                }
            }

            flock($fileHandle, LOCK_UN);
            fclose($fileHandle);
        }
    }
}

if ($errors) {
    http_response_code(422);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Registration error</title><link rel="stylesheet" href="/styles.css"></head><body><main><h1>Registration failed</h1><ul>';
    foreach ($errors as $error) {
        echo '<li>' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    echo '</ul><p><a href="/register.html">Return to registration</a></p></main></body></html>';
    exit;
}

header('Location: /Login.html?registered=1');
exit;
