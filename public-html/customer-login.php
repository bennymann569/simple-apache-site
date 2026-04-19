<?php
require __DIR__ . '/customer-auth.php';

$error = '';

if (is_customer_logged_in()) {
    header('Location: customer-dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (login_customer($email, $password)) {
        header('Location: customer-dashboard.php');
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="page">
        <header>
            <div>
                <h1>Customer Login</h1>
                <p class="intro">Sign in to view your cleaning quotes and payment status.</p>
            </div>
        </header>

        <div class="quote-card">
            <?php if ($error): ?>
                <p class="form-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form method="post" class="login-form">
                <label>
                    Email
                    <input type="email" name="email" required autofocus>
                </label>
                <label>
                    Password
                    <input type="password" name="password" required>
                </label>
                <button class="btn-primary" type="submit">Sign In</button>
            </form>

            <p style="margin-top: 1.5rem; text-align: center; color: var(--muted);">
                Don't have an account? <a href="customer-signup.php" style="color: var(--accent); text-decoration: none; font-weight: 600;">Create one</a>
            </p>
        </div>
    </main>
</body>
</html>
