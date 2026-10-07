<?php
session_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'db.php';

$message = $_SESSION['event_message'] ?? '';
$messageType = $_SESSION['event_message_type'] ?? 'success';
unset($_SESSION['event_message'], $_SESSION['event_message_type']);

$events = $pdo->query(
    'SELECT e.event_id, e.title, e.description, e.event_date, e.location, e.capacity,
            COUNT(r.registration_id) AS registered_count
     FROM events e
     LEFT JOIN registrations r ON r.event_id = e.event_id
     GROUP BY e.event_id
     ORDER BY e.event_date'
)->fetchAll();

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
    <title>StudentHub Events</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="container">
        <section class="card">
            <p class="eyebrow">StudentHub · Practical 8</p>
            <h1>Upcoming events</h1>
            <?php if ($message): ?>
                <div class="message <?= escaped($messageType) ?>" role="status"><?= escaped($message) ?></div>
            <?php endif; ?>

            <?php if (!$events): ?>
                <p>No events are available.</p>
            <?php else: ?>
                <div class="event-grid">
                    <?php foreach ($events as $event): ?>
                        <?php $available = (int) $event['registered_count'] < (int) $event['capacity']; ?>
                        <article class="event-card">
                            <h2><?= escaped($event['title']) ?></h2>
                            <p><?= escaped($event['description']) ?></p>
                            <dl>
                                <dt>Date</dt><dd><?= escaped($event['event_date']) ?></dd>
                                <dt>Location</dt><dd><?= escaped($event['location']) ?></dd>
                                <dt>Seats</dt><dd><?= (int) $event['registered_count'] ?> / <?= (int) $event['capacity'] ?></dd>
                            </dl>
                            <?php if ($available): ?>
                                <form method="POST" action="register_event.php">
                                    <input type="hidden" name="event_id" value="<?= (int) $event['event_id'] ?>">
                                    <button type="submit">Register for event</button>
                                </form>
                            <?php else: ?>
                                <p class="full">This event is full.</p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <p class="links"><a href="../Index.html">Back to StudentHub</a> <a href="my_registrations.php">My registrations</a> <a href="../practical7/logout.php">Logout</a></p>
        </section>
    </main>
</body>
</html>
