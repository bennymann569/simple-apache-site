<?php
require __DIR__ . '/auth.php';
require_admin_login();

$csvFile = __DIR__ . '/data/requests.csv';
$statusOptions = ['New', 'In Progress', 'Completed'];
$serviceOptions = ['All', 'Home Cleaning', 'Office Cleaning', 'Deep Cleaning', 'Move-Out Cleaning', 'Other'];
$message = '';

function read_request_csv(string $csvFile): array {
    $requests = [];
    if (!file_exists($csvFile) || !is_readable($csvFile)) {
        return $requests;
    }

    if (($handle = fopen($csvFile, 'r')) !== false) {
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return $requests;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                $row = array_pad($row, count($headers), '');
            }
            $requests[] = array_combine($headers, $row);
        }
        fclose($handle);
    }

    return $requests;
}

function save_request_csv(string $csvFile, array $requests): bool {
    if (empty($requests)) {
        return false;
    }

    if (($handle = fopen($csvFile, 'w')) === false) {
        return false;
    }

    $headers = array_keys($requests[0]);
    fputcsv($handle, $headers);
    foreach ($requests as $row) {
        fputcsv($handle, array_map(function ($value) {
            return $value === null ? '' : $value;
        }, $row));
    }
    fclose($handle);
    return true;
}

function filter_requests(array $requests, string $query, string $service, string $status): array {
    return array_values(array_filter($requests, function ($request) use ($query, $service, $status) {
        $matches = true;
        if ($query !== '') {
            $needle = mb_strtolower($query);
            $haystack = mb_strtolower(implode(' ', [
                $request['Name'] ?? '',
                $request['Email'] ?? '',
                $request['Phone'] ?? '',
                $request['Service'] ?? '',
                $request['Details'] ?? ''
            ]));
            $matches = $matches && mb_strpos($haystack, $needle) !== false;
        }

        if ($service !== '' && $service !== 'All') {
            $matches = $matches && (($request['Service'] ?? '') === $service);
        }

        if ($status !== '' && $status !== 'All') {
            $matches = $matches && (($request['Status'] ?? '') === $status);
        }

        return $matches;
    }));
}

$query = trim($_GET['q'] ?? '');
$filterService = trim($_GET['service'] ?? 'All');
$filterStatus = trim($_GET['status'] ?? 'All');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $requestId = trim($_POST['id'] ?? '');
    $newStatus = trim($_POST['status'] ?? '');

    if ($requestId !== '' && in_array($newStatus, $statusOptions, true)) {
        $requests = read_request_csv($csvFile);
        foreach ($requests as &$request) {
            if (($request['ID'] ?? '') === $requestId) {
                $request['Status'] = $newStatus;
                $request['Updated At'] = date('Y-m-d H:i:s');
                $message = 'Request status updated.';
                break;
            }
        }
        unset($request);
        save_request_csv($csvFile, $requests);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_price') {
    $requestId = trim($_POST['id'] ?? '');
    $quoteAmount = floatval($_POST['amount'] ?? 0);

    if ($requestId !== '' && $quoteAmount > 0) {
        $requests = read_request_csv($csvFile);
        foreach ($requests as &$request) {
            if (($request['ID'] ?? '') === $requestId) {
                $request['Quote Amount'] = number_format($quoteAmount, 2, '.', '');
                $request['Payment Status'] = 'Awaiting Payment';
                $request['Updated At'] = date('Y-m-d H:i:s');
                $message = 'Quote amount set to $' . number_format($quoteAmount, 2) . '.';
                break;
            }
        }
        unset($request);
        save_request_csv($csvFile, $requests);
    }
}

$requests = filter_requests(read_request_csv($csvFile), $query, $filterService, $filterStatus);
$requestCount = count($requests);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleaning Request Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="page">
        <header>
            <div>
                <h1>Cleaning Request Dashboard</h1>
                <p class="intro">View incoming quote requests and manage status from a secure admin portal.</p>
            </div>
            <div>
                <?php if (is_super_admin()): ?>
                    <a class="logout-link" href="admin-users.php">Manage Users</a>
                <?php endif; ?>
                <a class="logout-link" href="logout.php">Logout</a>
                <a class="logout-link" href="request-details.php?id=<?= htmlspecialchars($requests[0]['ID'] ?? '', ENT_QUOTES, 'UTF-8') ?>">Last request</a>
                <span class="tag"><?= is_super_admin() ? 'Super-Admin' : 'Admin' ?></span>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="form-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="filter-panel">
            <form method="get" class="filter-form">
                <label>
                    Search
                    <input type="search" name="q" placeholder="Search name, email, service, details" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label>
                    Service
                    <select name="service">
                        <?php foreach ($serviceOptions as $option): ?>
                            <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= ($filterService === $option) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Status
                    <select name="status">
                        <option <?= ($filterStatus === 'All') ? 'selected' : '' ?> value="All">All</option>
                        <?php foreach ($statusOptions as $option): ?>
                            <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= ($filterStatus === $option) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="filter-actions">
                    <button class="btn-primary" type="submit">Filter</button>
                    <a class="filter-clear" href="admin-requests.php">Clear</a>
                </div>
            </form>
        </div>

        <?php if ($requestCount === 0): ?>
            <div class="quote-card">
                <p>No quote requests match the current filters.</p>
            </div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Submitted</th>
                            <th>Name</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?= htmlspecialchars($request['ID'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($request['Timestamp'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($request['Name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($request['Service'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php $amount = floatval($request['Quote Amount'] ?? 0); ?>
                                    <?php if ($amount > 0): ?>
                                        <strong>$<?= number_format($amount, 2) ?></strong>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($request['Payment Status'] ?? 'Pending Quote', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($request['Status'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <a class="view-link" href="request-details.php?id=<?= htmlspecialchars($request['ID'] ?? '', ENT_QUOTES, 'UTF-8') ?>">View</a>
                                    <?php if (floatval($request['Quote Amount'] ?? 0) > 0 && ($request['Payment Status'] ?? '') === 'Awaiting Payment'): ?>
                                        <a class="view-link" href="pay.php?id=<?= htmlspecialchars($request['ID'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="color: #10b981;">Pay Link</a>
                                    <?php endif; ?>
                                    <form method="post" class="status-form-inline">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($request['ID'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="number" name="amount" placeholder="Price" step="0.01" min="0.01" value="<?= htmlspecialchars($request['Quote Amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="width: 100px;">
                                        <button type="submit" name="action" value="set_price" style="font-size: 0.85em; padding: 0.4rem 0.6rem;">Set Price</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
