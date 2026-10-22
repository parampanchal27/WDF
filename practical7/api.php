<?php
require __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'db_mysqli.php';

startStudentHubSession();
header('Content-Type: application/json; charset=utf-8');

if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Administrator access is required.']);
    exit;
}

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function requestValue(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function validateStudent(): array
{
    $name = requestValue('full_name');
    $username = requestValue('username');
    $email = strtolower(requestValue('email'));
    $mobile = requestValue('mobile');
    $course = requestValue('course');
    $semester = (int) ($_POST['semester'] ?? 0);
    $errors = [];
    if (!preg_match("/^[\p{L} .'-]{2,100}$/u", $name)) {
        $errors[] = 'Enter a valid full name.';
    }
    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
        $errors[] = 'Username must be 3-30 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Enter a valid email address.';
    }
    if (!preg_match('/^[0-9+\-\s()]{7,15}$/', $mobile)) {
        $errors[] = 'Enter a valid mobile number.';
    }
    if ($course === '' || $semester < 1 || $semester > 12) {
        $errors[] = 'Course and semester are required.';
    }
    return [$name, $username, $email, $mobile, $course, $semester, $errors];
}

function savePoster(?string $existingPath = null): ?string
{
    if (empty($_FILES['poster']['name'])) {
        return $existingPath;
    }
    if ($_FILES['poster']['error'] !== UPLOAD_ERR_OK || $_FILES['poster']['size'] > 2 * 1024 * 1024) {
        respond(['success' => false, 'message' => 'Poster must be a JPG, PNG, or WEBP image up to 2 MB.'], 422);
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['poster']['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        respond(['success' => false, 'message' => 'Poster must be a JPG, PNG, or WEBP image.'], 422);
    }
    $directory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        respond(['success' => false, 'message' => 'Unable to create the poster upload directory.'], 500);
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($_FILES['poster']['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) {
        respond(['success' => false, 'message' => 'Unable to save the event poster.'], 500);
    }
    if ($existingPath) {
        $oldFile = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $existingPath);
        if (is_file($oldFile)) {
            unlink($oldFile);
        }
    }
    return 'uploads/' . $filename;
}

$method = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? '';
$action = requestValue('action');
$csrf = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '');
if ($method !== 'GET' && !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $csrf)) {
    respond(['success' => false, 'message' => 'Invalid security token. Refresh the page and try again.'], 403);
}

if ($method === 'GET' && $resource === 'stats') {
    $stats = [];
    foreach (['students' => 'SELECT COUNT(*) AS total FROM students', 'events' => 'SELECT COUNT(*) AS total FROM events', 'users' => 'SELECT COUNT(*) AS total FROM students'] as $key => $query) {
        $result = $mysqli->query($query);
        $stats[$key] = (int) $result->fetch_assoc()['total'];
    }
    $result = $mysqli->query(
        'SELECT s.full_name, s.email, e.title AS event_title, r.registered_at
         FROM registrations r
         INNER JOIN students s ON s.student_id = r.student_id
         INNER JOIN events e ON e.event_id = r.event_id
         ORDER BY r.registered_at DESC LIMIT 5'
    );
    $latest = [];
    while ($row = $result->fetch_assoc()) {
        $latest[] = $row;
    }
    respond(['success' => true, 'stats' => $stats, 'latest' => $latest]);
}

if ($method === 'GET' && $resource === 'students') {
    $search = '%' . trim((string) ($_GET['search'] ?? '')) . '%';
    $course = trim((string) ($_GET['course'] ?? ''));
    $statement = $mysqli->prepare(
        'SELECT student_id, full_name, username, email, mobile, course, semester, role, created_at
         FROM students WHERE (full_name LIKE ? OR username LIKE ? OR email LIKE ?)
         AND (? = "" OR course = ?) ORDER BY created_at DESC'
    );
    $statement->bind_param('sssss', $search, $search, $search, $course, $course);
    $statement->execute();
    $result = $statement->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    respond(['success' => true, 'students' => $rows]);
}

if ($method === 'GET' && $resource === 'events') {
    $statement = $mysqli->prepare(
        'SELECT e.event_id, e.title, e.description, e.event_date, e.location, e.capacity, e.poster_path,
                COUNT(r.registration_id) AS registered_count
         FROM events e LEFT JOIN registrations r ON r.event_id = e.event_id
         GROUP BY e.event_id ORDER BY e.event_date DESC'
    );
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    respond(['success' => true, 'events' => $rows]);
}

if ($action === 'create_student' || $action === 'update_student') {
    [$name, $username, $email, $mobile, $course, $semester, $errors] = validateStudent();
    if ($errors) {
        respond(['success' => false, 'message' => implode(' ', $errors)], 422);
    }
    $id = (int) ($_POST['student_id'] ?? 0);
    if ($action === 'create_student') {
        $password = password_hash('Student@123', PASSWORD_DEFAULT);
        $statement = $mysqli->prepare('INSERT INTO students (full_name, username, email, mobile, course, semester, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $statement->bind_param('sssssis', $name, $username, $email, $mobile, $course, $semester, $password);
    } else {
        $statement = $mysqli->prepare('UPDATE students SET full_name = ?, username = ?, email = ?, mobile = ?, course = ?, semester = ? WHERE student_id = ?');
        $statement->bind_param('sssssii', $name, $username, $email, $mobile, $course, $semester, $id);
    }
    if (!$statement->execute()) {
        $message = $mysqli->errno === 1062 ? 'Username or email already exists.' : 'Unable to save the student record.';
        $statement->close();
        respond(['success' => false, 'message' => $message], 422);
    }
    $statement->close();
    respond(['success' => true, 'message' => $action === 'create_student'
        ? 'Student added successfully. Default password: Student@123.'
        : 'Student updated successfully.']);
}

if ($action === 'delete_student') {
    $id = (int) ($_POST['student_id'] ?? 0);
    if ($id === (int) $_SESSION['user']['id']) {
        respond(['success' => false, 'message' => 'You cannot delete your own admin account.'], 422);
    }
    $statement = $mysqli->prepare('DELETE FROM students WHERE student_id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $deleted = $statement->affected_rows;
    $statement->close();
    respond(['success' => $deleted > 0, 'message' => $deleted > 0 ? 'Student deleted successfully.' : 'Student record not found.'], $deleted > 0 ? 200 : 404);
}

if ($action === 'create_event' || $action === 'update_event') {
    $title = requestValue('title');
    $description = requestValue('description');
    $date = requestValue('event_date');
    $location = requestValue('location');
    $capacity = (int) ($_POST['capacity'] ?? 0);
    if ($title === '' || $description === '' || $date === '' || $location === '' || $capacity < 1) {
        respond(['success' => false, 'message' => 'Complete all event fields with a valid capacity.'], 422);
    }
    $id = (int) ($_POST['event_id'] ?? 0);
    $poster = null;
    if ($action === 'update_event') {
        $find = $mysqli->prepare('SELECT poster_path FROM events WHERE event_id = ?');
        $find->bind_param('i', $id);
        $find->execute();
        $poster = ($find->get_result()->fetch_assoc()['poster_path'] ?? null);
        $find->close();
    }
    $poster = savePoster($poster);
    if ($action === 'create_event') {
        $statement = $mysqli->prepare('INSERT INTO events (title, description, event_date, location, capacity, poster_path) VALUES (?, ?, ?, ?, ?, ?)');
        $statement->bind_param('ssssis', $title, $description, $date, $location, $capacity, $poster);
    } else {
        $statement = $mysqli->prepare('UPDATE events SET title = ?, description = ?, event_date = ?, location = ?, capacity = ?, poster_path = ? WHERE event_id = ?');
        $statement->bind_param('ssssisi', $title, $description, $date, $location, $capacity, $poster, $id);
    }
    if (!$statement->execute()) {
        $statement->close();
        respond(['success' => false, 'message' => 'Unable to save the event.'], 422);
    }
    $statement->close();
    respond(['success' => true, 'message' => $action === 'create_event' ? 'Event added successfully.' : 'Event updated successfully.']);
}

if ($action === 'delete_event') {
    $id = (int) ($_POST['event_id'] ?? 0);
    $statement = $mysqli->prepare('DELETE FROM events WHERE event_id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $deleted = $statement->affected_rows;
    $statement->close();
    respond(['success' => $deleted > 0, 'message' => $deleted > 0 ? 'Event deleted successfully.' : 'Event not found.'], $deleted > 0 ? 200 : 404);
}

respond(['success' => false, 'message' => 'Unsupported API request.'], 400);
