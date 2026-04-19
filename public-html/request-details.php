<?php
require __DIR__ . '/auth.php';
require_admin_login();

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

$statusOptions = ['New', 'In Progress', 'Completed'];
$message = '';

$requestId = trim($_GET['id'] ?? '');
$csvFile = __DIR__ . '/data/requests.csv';
$requests = read_request_csv($csvFile);
$selectedRequest = null;
foreach ($requests as $request) {
    if (($request['ID'] ?? '') === $requestId) {
        $selectedRequest = $request;
        break;
    }
}

if ($selectedRequest === null) {
    $message = 'Request not found.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($selectedRequest !== null)) {
    $newStatus = trim($_POST['status'] ?? '');
    if (in_array($newStatus, $statusOptions, true)) {
        foreach ($requests as &$request) {
            if (($request['ID'] ?? '') === $requestId) {
                $request['Status'] = $newStatus;
                $request['Updated At'] = date('Y-m-d H:i:s');
                $selectedRequest = $request;
                $message = 'Request status updated.';
                break;
            }
        }
        unset($request);
        save_request_csv($csvFile, $requests);
    }
}

function escape_html(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Details</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="page">
        <header>
            <div>
                <h1>Request Details</h1>
                <p class="intro">Review the selected quote request and update its status as needed.</p>
            </div>
            <div>
                <a class="logout-link" href="logout.php">Logout</a>
                <a class="logout-link" href="admin-requests.php">Back to Dashboard</a>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="form-success"><?= escape_html($message) ?></div>
        <?php endif; ?>

        <?php if ($selectedRequest === null): ?>
            <div class="quote-card">
                <p>Unable to find the requested quote. Confirm the request ID is correct.</p>
            </div>
        <?php else: ?>
            <div class="detail-card">
                <div class="detail-row">
                    <span class="detail-label">Request ID</span>
                    <span><?= escape_html($selectedRequest['ID'] ?? '') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Submitted</span>
                    <span><?= escape_html($selectedRequest['Timestamp'] ?? '') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Name</span>
                    <span><?= escape_html($selectedRequest['Name'] ?? '') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span><?= escape_html($selectedRequest['Email'] ?? '') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone</span>
                    <span><?= escape_html($selectedRequest['Phone'] ?? '') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Service</span>
                    <span><?= escape_html($selectedRequest['Service'] ?? '') ?></span>
                </div>
                <div class="detail-row detail-full">
                    <span class="detail-label">Details</span>
                    <span><?= nl2br(escape_html($selectedRequest['Details'] ?? '')) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span><?= escape_html($selectedRequest['Status'] ?? '') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Last Updated</span>
                    <span><?= escape_html($selectedRequest['Updated At'] ?? '') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Quote Amount</span>
                    <span>
                        <?php $amount = floatval($selectedRequest['Quote Amount'] ?? 0); ?>
                        <?php if ($amount > 0): ?>
                            <strong style="color: var(--accent);">$<?= number_format($amount, 2) ?></strong>
                        <?php else: ?>
                            <span style="color: #94a3b8;">Not yet quoted</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Status</span>
                    <span><?= escape_html($selectedRequest['Payment Status'] ?? 'Pending Quote') ?></span>
                </div>
            </div>

            <?php if (floatval($selectedRequest['Quote Amount'] ?? 0) > 0 && ($selectedRequest['Payment Status'] ?? '') !== 'Completed'): ?>
                <div class="quote-card">
                    <a class="btn-primary" href="pay.php?id=<?= escape_html($selectedRequest['ID'] ?? '') ?>" style="display: inline-block; text-decoration: none;">Send Payment Link to Customer</a>
                </div>
            <?php endif; ?>

            <div class="quote-card">
                <form method="post" class="status-form">
                    <label>
                        Update status
                        <select name="status">
                            <?php foreach ($statusOptions as $statusOption): ?>
                                <option value="<?= escape_html($statusOption) ?>" <?= (($selectedRequest['Status'] ?? '') === $statusOption) ? 'selected' : '' ?>>
                                    <?= escape_html($statusOption) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="btn-primary" type="submit">Save Status</button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
