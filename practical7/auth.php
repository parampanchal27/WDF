<?php

const STUDENTHUB_SESSION_TIMEOUT = 1800;

function studentHubRootUrl(string $path): string
{
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $rootDirectory = preg_replace('#/practical7$#', '', rtrim($scriptDirectory, '/'));
    return ($rootDirectory === '' ? '' : $rootDirectory) . '/' . ltrim($path, '/');
}

function startStudentHubSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (empty($_SESSION['user']['id'])) {
        restoreRememberedStudent();
    }
}

function restoreRememberedStudent(): void
{
    $cookie = (string) ($_COOKIE['studenthub_remember'] ?? '');
    $parts = explode(':', $cookie, 2);
    if (count($parts) !== 2 || !ctype_xdigit($parts[0]) || !ctype_xdigit($parts[1])) {
        return;
    }

    require_once __DIR__ . DIRECTORY_SEPARATOR . 'db_mysqli.php';
    $selector = $parts[0];
    $validatorHash = hash('sha256', $parts[1]);
    $statement = $mysqli->prepare(
        'SELECT s.student_id, s.full_name, s.username, s.email, s.role, t.token_hash
         FROM remember_tokens t
         INNER JOIN students s ON s.student_id = t.student_id
         WHERE t.selector = ? AND t.expires_at > NOW() LIMIT 1'
    );
    $statement->bind_param('s', $selector);
    $statement->execute();
    $result = $statement->get_result();
    $remembered = $result->fetch_assoc();
    $statement->close();

    if (!$remembered || !hash_equals($remembered['token_hash'], $validatorHash)) {
        setcookie('studenthub_remember', '', time() - 3600, '/');
        return;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $remembered['student_id'],
        'fullName' => $remembered['full_name'],
        'username' => $remembered['username'],
        'email' => $remembered['email'],
        'role' => $remembered['role'],
    ];
    $_SESSION['last_activity'] = time();
}

function expireStudentHubSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function requireStudentHubLogin(): void
{
    startStudentHubSession();

    if (!empty($_SESSION['user']['id'])) {
        if (time() - (int) ($_SESSION['last_activity'] ?? time()) > STUDENTHUB_SESSION_TIMEOUT) {
            expireStudentHubSession();
            header('Location: ' . studentHubRootUrl('Login.html?timeout=1'));
            exit;
        }
        $_SESSION['last_activity'] = time();
        return;
    }

    header('Location: ' . studentHubRootUrl('Login.html?required=1'));
    exit;
}

function currentStudentHubUser(): array
{
    return $_SESSION['user'] ?? [];
}
