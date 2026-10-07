<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$old = $_SESSION['form_data'] ?? [];
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

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
    <title>Practical 7 - Contact Form</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="container">
        <section class="card">
            <p class="eyebrow">WDF Practical 7</p>
            <h1>Registration / Contact Form</h1>
            <p class="intro">Submit your details. The server validates the data and stores valid records in a CSV file.</p>

            <?php if ($errors): ?>
                <div class="message error" role="alert">
                    <strong>Please correct the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= escaped($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="process.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= escaped($_SESSION['csrf_token']) ?>">

                <div class="form-grid">
                    <div class="field">
                        <label for="name">Full name <span aria-hidden="true">*</span></label>
                        <input type="text" id="name" name="name" value="<?= escaped($old['name'] ?? '') ?>" maxlength="80" required autocomplete="name">
                    </div>

                    <div class="field">
                        <label for="email">Email address <span aria-hidden="true">*</span></label>
                        <input type="email" id="email" name="email" value="<?= escaped($old['email'] ?? '') ?>" maxlength="150" required autocomplete="email">
                    </div>

                    <div class="field">
                        <label for="phone">Phone number</label>
                        <input type="tel" id="phone" name="phone" value="<?= escaped($old['phone'] ?? '') ?>" maxlength="20" autocomplete="tel">
                    </div>

                    <div class="field field-wide">
                        <label for="message">Message <span aria-hidden="true">*</span></label>
                        <textarea id="message" name="message" rows="6" maxlength="1000" required><?= escaped($old['message'] ?? '') ?></textarea>
                        <small>Maximum 1000 characters.</small>
                    </div>
                </div>

                <button type="submit">Submit form</button>
            </form>

            <p class="links"><a href="records.php">View stored records</a></p>
        </section>
    </main>
</body>
</html>
