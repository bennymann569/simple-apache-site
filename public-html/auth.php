<?php
session_start();

const ADMIN_USERNAME = 'admin';
const ADMIN_PASSWORD = 'Clean@123';

function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_authenticated']);
}

function require_admin_login(): void {
    if (is_admin_logged_in()) {
        return;
    }
    header('Location: login.php');
    exit;
}

function login_admin(string $username, string $password): bool {
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_authenticated'] = true;
        return true;
    }
    return false;
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
