<?php
require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'practical7' . DIRECTORY_SEPARATOR . 'auth.php';
startStudentHubSession();
requireStudentHubLogin();
require __DIR__ . DIRECTORY_SEPARATOR . 'db.php';

$userEmail = $_SESSION['user']['email'] ?? '';
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

if ($userEmail === '' || !$eventId) {
    $_SESSION['event_message'] = 'Please log in to StudentHub before registering for an event.';
    $_SESSION['event_message_type'] = 'error';
    header('Location: events.php');
    exit;
}

$studentStatement = $pdo->prepare('SELECT student_id FROM students WHERE email = :email');
$studentStatement->execute(['email' => $userEmail]);
$student = $studentStatement->fetch();

if (!$student) {
    $_SESSION['event_message'] = 'Your StudentHub account could not be found in the database.';
    $_SESSION['event_message_type'] = 'error';
    header('Location: events.php');
    exit;
}

try {
    $registration = $pdo->prepare(
        'INSERT INTO registrations (student_id, event_id)
         SELECT :student_id, event_id
         FROM events
         WHERE event_id = :event_id
           AND (SELECT COUNT(*) FROM registrations WHERE event_id = :count_event_id) < capacity'
    );
    $registration->execute([
        'student_id' => $student['student_id'],
        'event_id' => $eventId,
        'count_event_id' => $eventId,
    ]);

    if ($registration->rowCount() !== 1) {
        throw new RuntimeException('The event is unavailable or full.');
    }

    $_SESSION['event_message'] = 'You have been registered for the event.';
    $_SESSION['event_message_type'] = 'success';
} catch (PDOException $exception) {
    if ((int) $exception->errorInfo[1] === 1062) {
        $_SESSION['event_message'] = 'You are already registered for this event.';
    } else {
        error_log('Event registration failed: ' . $exception->getMessage());
        $_SESSION['event_message'] = 'Unable to register for this event.';
    }
    $_SESSION['event_message_type'] = 'error';
} catch (RuntimeException $exception) {
    $_SESSION['event_message'] = $exception->getMessage();
    $_SESSION['event_message_type'] = 'error';
}

header('Location: events.php');
exit;
