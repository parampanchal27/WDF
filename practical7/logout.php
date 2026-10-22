<?php
require __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
startStudentHubSession();
require_once __DIR__ . DIRECTORY_SEPARATOR . 'db_mysqli.php';
if (!empty($_SESSION['user']['id'])) {
    $statement = $mysqli->prepare('DELETE FROM remember_tokens WHERE student_id = ?');
    $studentId = (int) $_SESSION['user']['id'];
    $statement->bind_param('i', $studentId);
    $statement->execute();
    $statement->close();
}
expireStudentHubSession();
setcookie('studenthub_remember', '', time() - 3600, '/');
header('Location: ../Login.html?logged_out=1');
exit;
