<?php
require __DIR__ . '/customer-auth.php';

$error = '';
$success = '';

if (is_customer_logged_in()) {
    header('Location: customer-dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $passwordConfirm = trim($_POST['password_confirm'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($password !== $passwordConfirm) {
        $error = 'Passwords do not match.';
    } else {
        $result = create_customer($email, $password, $name, $phone);
        if ($result['success']) {
            $success = 'Account created! Redirecting to login...';
            // Auto-login
            login_customer($email, $password);
            header('Location: customer-dashboard.php');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="page">
        <header>
            <div>
                <h1>Create Account</h1>
                <p class="intro">Sign up to view your quotes and payment history.</p>
            </div>
        </header>

        <?php if ($error): ?>
            <div class="form-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="form-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="quote-card">
            <form method="post" class="login-form">
                <label>
                    Email
                    <input type="email" name="email" required autofocus>
                </label>
                <label>
                    Full Name
                    <input type="text" name="name" required>
                </label>
                <label>
                    Phone (optional)
                    <input type="tel" name="phone">
                </label>
                <label>
                    Password
                    <div class="password-field">
                        <input id="signup-password" type="password" name="password" required minlength="8">
                        <button type="button" class="password-toggle" data-target="signup-password">Show</button>
                    </div>
                </label>
                <label>
                    Confirm Password
                    <div class="password-field">
                        <input id="signup-password-confirm" type="password" name="password_confirm" required minlength="8">
                        <button type="button" class="password-toggle" data-target="signup-password-confirm">Show</button>
                    </div>
                </label>
                <button class="btn-primary" type="submit">Create Account</button>
            </form>
            <p style="margin-top: 1.5rem; text-align: center; color: var(--muted);">
                Already have an account? <a href="customer-login.php" style="color: var(--accent); text-decoration: none; font-weight: 600;">Sign in</a>
            </p>
        </div>
    </main>
    <script src="script.js"></script>
</body>
</html>
