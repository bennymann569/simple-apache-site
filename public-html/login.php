<?php
require __DIR__ . '/auth.php';

$error = '';
if (is_admin_logged_in()) {
    header('Location: admin-requests.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (login_admin($username, $password)) {
        header('Location: admin-requests.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="page">
        <header>
            <div>
                <h1>Admin Login</h1>
                <p class="intro">Sign in to manage cleaning quote requests and update request status.</p>
            </div>
        </header>
        <div class="quote-card">
            <?php if ($error): ?>
                <p class="form-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form method="post" class="login-form">
                <label>
                    Username
                    <input type="text" name="username" required autofocus>
                </label>
                <label>
                    Password
                    <input type="password" name="password" required>
                </label>
                <button class="btn-primary" type="submit">Sign In</button>
            </form>
        </div>
    </main>
</body>
</html>
