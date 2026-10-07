<?php
require __DIR__ . DIRECTORY_SEPARATOR . 'db.php';

$statement = $pdo->query('SELECT DATABASE() AS database_name');
$database = $statement->fetch();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudentHub Database Connection</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="container">
        <section class="card">
            <p class="eyebrow">Practical 8</p>
            <h1>Database connection successful</h1>
            <p>PDO connected to the <strong><?= htmlspecialchars($database['database_name'], ENT_QUOTES, 'UTF-8') ?></strong> database.</p>
            <p><a href="events.php">Open StudentHub events</a></p>
        </section>
    </main>
</body>
</html>
