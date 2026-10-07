<?php
session_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'db.php';

$email = $_SESSION['user']['email'] ?? '';
$registrations = [];

if ($email !== '') {
    $statement = $pdo->prepare(
        'SELECT e.title, e.event_date, e.location, r.registered_at
         FROM registrations r
         INNER JOIN students s ON s.student_id = r.student_id
         INNER JOIN events e ON e.event_id = r.event_id
         WHERE s.email = :email
         ORDER BY e.event_date'
    );
    $statement->execute(['email' => $email]);
    $registrations = $statement->fetchAll();
}

function escaped(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Event Registrations</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="container">
        <section class="card">
            <h1>My event registrations</h1>
            <?php if ($email === ''): ?>
                <p>Please log in to view your registrations.</p>
            <?php elseif (!$registrations): ?>
                <p>You have not registered for any events yet.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Event</th><th>Date</th><th>Location</th><th>Registered at</th></tr></thead>
                        <tbody>
                            <?php foreach ($registrations as $registration): ?>
                                <tr>
                                    <td><?= escaped($registration['title']) ?></td>
                                    <td><?= escaped($registration['event_date']) ?></td>
                                    <td><?= escaped($registration['location']) ?></td>
                                    <td><?= escaped($registration['registered_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <p class="links"><a href="events.php">Back to events</a> <a href="../Index.html">StudentHub home</a> <a href="../practical7/logout.php">Logout</a></p>
        </section>
    </main>
</body>
</html>
