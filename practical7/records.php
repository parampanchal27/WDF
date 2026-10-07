<?php
$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'submissions.json';
$rows = [];
$readError = null;

if (is_readable($filePath)) {
    $contents = file_get_contents($filePath);
    $decoded = $contents === false ? null : json_decode($contents, true);

    if ($contents === false || json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        $readError = 'Unable to read valid JSON from the storage file.';
    } else {
        $rows = $decoded;
    }
}

function escaped(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stored Records</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="container">
        <section class="card records-card">
            <p class="eyebrow">JSON storage</p>
            <h1>Stored records</h1>

            <?php if ($readError): ?>
                <div class="message error" role="alert"><?= escaped($readError) ?></div>
            <?php elseif (!$rows): ?>
                <p>No records have been submitted yet.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= escaped((string) ($row['date'] ?? '')) ?></td>
                                    <td><?= escaped((string) ($row['name'] ?? '')) ?></td>
                                    <td><?= escaped((string) ($row['email'] ?? '')) ?></td>
                                    <td><?= escaped((string) ($row['phone'] ?? '')) ?></td>
                                    <td><?= escaped((string) ($row['message'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <p class="links"><a href="index.php">Back to form</a></p>
        </section>
    </main>
</body>
</html>
