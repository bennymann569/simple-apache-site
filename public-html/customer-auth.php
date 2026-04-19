<?php
require_once __DIR__ . '/config.php';
configure_session();
session_start();

const CUSTOMERS_CSV = __DIR__ . '/data/customers.csv';

function read_customers(): array {
    $customers = [];
    if (!file_exists(CUSTOMERS_CSV) || !is_readable(CUSTOMERS_CSV)) {
        return $customers;
    }

    if (($handle = fopen(CUSTOMERS_CSV, 'r')) !== false) {
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return $customers;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                $row = array_pad($row, count($headers), '');
            }
            $customer = array_combine($headers, $row);
            $customers[$customer['Email']] = $customer;
        }
        fclose($handle);
    }

    return $customers;
}

function write_customers(array $customers): bool {
    if (($handle = fopen(CUSTOMERS_CSV, 'w')) === false) {
        return false;
    }

    $headers = ['ID', 'Email', 'Password Hash', 'Name', 'Phone', 'Created At', 'Last Login'];
    fputcsv($handle, $headers);
    foreach ($customers as $customer) {
        fputcsv($handle, [
            $customer['ID'] ?? '',
            $customer['Email'] ?? '',
            $customer['Password Hash'] ?? '',
            $customer['Name'] ?? '',
            $customer['Phone'] ?? '',
            $customer['Created At'] ?? date('Y-m-d H:i:s'),
            $customer['Last Login'] ?? '',
        ]);
    }
    fclose($handle);
    return true;
}

function is_customer_logged_in(): bool {
    return !empty($_SESSION['customer_authenticated']) && !empty($_SESSION['customer_email']);
}

function get_current_customer(): ?array {
    if (!is_customer_logged_in()) {
        return null;
    }
    $customers = read_customers();
    return $customers[$_SESSION['customer_email']] ?? null;
}

function require_customer_login(): void {
    if (is_customer_logged_in()) {
        return;
    }
    header('Location: customer-login.php');
    exit;
}

function login_customer(string $email, string $password): bool {
    $customers = read_customers();
    $customer = $customers[$email] ?? null;

    if ($customer === null) {
        return false;
    }

    if (!password_verify($password, $customer['Password Hash'])) {
        app_log("Customer login failed for email={$email} from ip=" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['customer_authenticated'] = true;
    $_SESSION['customer_email'] = $email;
    $_SESSION['customer_name'] = $customer['Name'] ?? '';

    // Update last login
    $customer['Last Login'] = date('Y-m-d H:i:s');
    $customers[$email] = $customer;
    write_customers($customers);

    return true;
}

function logout_customer(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    header('Location: index.html');
    exit;
}

function create_customer(string $email, string $password, string $name, string $phone = ''): array {
    $result = ['success' => false, 'error' => ''];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result['error'] = 'Invalid email address.';
        return $result;
    }

    if (strlen($password) < 8) {
        $result['error'] = 'Password must be at least 8 characters.';
        return $result;
    }

    if (strlen($name) < 2) {
        $result['error'] = 'Name must be at least 2 characters.';
        return $result;
    }

    $customers = read_customers();
    if (isset($customers[$email])) {
        $result['error'] = 'Email already registered.';
        return $result;
    }

    $newId = max(array_map(fn($c) => intval($c['ID'] ?? 0), $customers)) + 1;
    $customers[$email] = [
        'ID' => (string) $newId,
        'Email' => $email,
        'Password Hash' => password_hash($password, PASSWORD_DEFAULT),
        'Name' => $name,
        'Phone' => $phone,
        'Created At' => date('Y-m-d H:i:s'),
        'Last Login' => '',
    ];

    if (!write_customers($customers)) {
        $result['error'] = 'Failed to create account.';
        return $result;
    }

    $result['success'] = true;
    return $result;
}
