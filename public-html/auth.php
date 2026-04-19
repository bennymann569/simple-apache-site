<?php
// Session hardening settings
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Lax');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', '1');
}
session_start();

const USERS_CSV = __DIR__ . '/data/users.csv';

function read_users(): array {
    $users = [];
    if (!file_exists(USERS_CSV) || !is_readable(USERS_CSV)) {
        return $users;
    }

    if (($handle = fopen(USERS_CSV, 'r')) !== false) {
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return $users;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                $row = array_pad($row, count($headers), '');
            }
            $user = array_combine($headers, $row);
            $users[$user['Username']] = $user;
        }
        fclose($handle);
    }

    return $users;
}

function write_users(array $users): bool {
    if (($handle = fopen(USERS_CSV, 'w')) === false) {
        return false;
    }

    $headers = ['ID', 'Username', 'Password Hash', 'Role', 'Created At', 'Last Login'];
    fputcsv($handle, $headers);
    foreach ($users as $user) {
        fputcsv($handle, [
            $user['ID'] ?? '',
            $user['Username'] ?? '',
            $user['Password Hash'] ?? '',
            $user['Role'] ?? 'admin',
            $user['Created At'] ?? date('Y-m-d H:i:s'),
            $user['Last Login'] ?? '',
        ]);
    }
    fclose($handle);
    return true;
}

function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_authenticated']) && !empty($_SESSION['admin_username']);
}

function get_current_admin(): ?array {
    if (!is_admin_logged_in()) {
        return null;
    }
    $users = read_users();
    return $users[$_SESSION['admin_username']] ?? null;
}

function is_super_admin(): bool {
    $admin = get_current_admin();
    return $admin && ($admin['Role'] ?? 'admin') === 'super-admin';
}

function require_admin_login(): void {
    if (is_admin_logged_in()) {
        return;
    }
    header('Location: login.php');
    exit;
}

function require_super_admin(): void {
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
    if (!is_super_admin()) {
        http_response_code(403);
        echo 'Access denied. Super-admin role required.';
        exit;
    }
}

function login_admin(string $username, string $password): bool {
    $users = read_users();
    $user = $users[$username] ?? null;

    if ($user === null) {
        return false;
    }

    if (!password_verify($password, $user['Password Hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_role'] = $user['Role'] ?? 'admin';

    // Update last login
    $user['Last Login'] = date('Y-m-d H:i:s');
    $users[$username] = $user;
    write_users($users);

    return true;
}

function logout_admin(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    header('Location: login.php');
    exit;
}
