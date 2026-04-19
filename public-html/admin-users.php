<?php
require __DIR__ . '/auth.php';
require_super_admin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'create_user') {
        $newUsername = trim($_POST['username'] ?? '');
        $newPassword = trim($_POST['password'] ?? '');
        $newRole = trim($_POST['role'] ?? 'admin');

        $errors = [];
        if (strlen($newUsername) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }
        if (strlen($newPassword) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!in_array($newRole, ['admin', 'super-admin'], true)) {
            $errors[] = 'Invalid role selected.';
        }

        $users = read_users();
        if (isset($users[$newUsername])) {
            $errors[] = 'Username already exists.';
        }

        if (empty($errors)) {
            $newId = max(array_map(fn($u) => intval($u['ID'] ?? 0), $users)) + 1;
            $users[$newUsername] = [
                'ID' => (string) $newId,
                'Username' => $newUsername,
                'Password Hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'Role' => $newRole,
                'Created At' => date('Y-m-d H:i:s'),
                'Last Login' => '',
            ];
            if (write_users($users)) {
                $message = "User '$newUsername' created successfully.";
            } else {
                $error = 'Failed to create user.';
            }
        } else {
            $error = implode(' ', $errors);
        }
    } elseif ($action === 'delete_user') {
        $deleteUsername = trim($_POST['username'] ?? '');
        $currentUser = get_current_admin();

        if ($deleteUsername === ($currentUser['Username'] ?? '')) {
            $error = 'Cannot delete your own account.';
        } else {
            $users = read_users();
            if (isset($users[$deleteUsername])) {
                unset($users[$deleteUsername]);
                if (write_users($users)) {
                    $message = "User '$deleteUsername' deleted.";
                } else {
                    $error = 'Failed to delete user.';
                }
            } else {
                $error = 'User not found.';
            }
        }
    }
}

$users = read_users();
$currentUser = get_current_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin User Management</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="page">
        <header>
            <div>
                <h1>Admin User Management</h1>
                <p class="intro">Create and manage admin user accounts. Super-admin only.</p>
            </div>
            <div>
                <a class="logout-link" href="admin-requests.php">Dashboard</a>
                <a class="logout-link" href="logout.php">Logout</a>
                <span class="tag">Super-Admin</span>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="form-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="form-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="quote-card">
            <h2>Create New Admin User</h2>
            <form method="post" class="login-form">
                <label>
                    Username
                    <input type="text" name="username" required minlength="3">
                </label>
                <label>
                    Password
                    <input type="password" name="password" required minlength="8">
                </label>
                <label>
                    Role
                    <select name="role">
                        <option value="admin">Admin</option>
                        <option value="super-admin">Super-Admin</option>
                    </select>
                </label>
                <button class="btn-primary" type="submit" name="action" value="create_user">Create User</button>
            </form>
        </div>

        <div class="admin-table-wrap">
            <h2>Current Admin Users</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['ID'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($user['Username'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($user['Username'] === ($currentUser['Username'] ?? '')): ?>
                                    <span style="font-size: 0.85em; color: #94a3b8;">(You)</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['Role'] ?? 'admin', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($user['Created At'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($user['Last Login'] ?: 'Never', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($user['Username'] !== ($currentUser['Username'] ?? '')): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="username" value="<?= htmlspecialchars($user['Username'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button class="btn-danger" type="submit" name="action" value="delete_user" onclick="return confirm('Delete user: <?= htmlspecialchars($user['Username'], ENT_QUOTES, 'UTF-8') ?>?');">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #64748b;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
