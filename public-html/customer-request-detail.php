<?php
require __DIR__ . '/customer-auth.php';
require_customer_login();

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

$customer = get_current_customer();
$requestId = trim($_GET['id'] ?? '');
$csvFile = __DIR__ . '/data/requests.csv';
$requests = read_request_csv($csvFile);

$selectedRequest = null;
foreach ($requests as $request) {
    if (($request['ID'] ?? '') === $requestId && ($request['Email'] ?? '') === ($customer['Email'] ?? '')) {
        $selectedRequest = $request;
        break;
    }
}

if ($selectedRequest === null) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>Not Found</title><link rel="stylesheet" href="styles.css"></head><body><main class="page"><h1>Quote Not Found</h1><p>Unable to find this quote. <a href="customer-dashboard.php">Back to dashboard</a></p></main></body></html>';
    exit;
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
    <title>Quote Details</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="page">
        <header>
            <div>
                <h1>Quote Details</h1>
                <p class="intro">View your cleaning quote information and payment status.</p>
            </div>
            <div>
                <a class="logout-link" href="customer-dashboard.php">Back</a>
                <a class="logout-link" href="customer-logout.php">Logout</a>
            </div>
        </header>

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
                <span class="detail-label">Quote Amount</span>
                <span>
                    <?php $amount = floatval($selectedRequest['Quote Amount'] ?? 0); ?>
                    <?php if ($amount > 0): ?>
                        <strong style="color: var(--accent); font-size: 1.2em;">$<?= number_format($amount, 2) ?></strong>
                    <?php else: ?>
                        <span style="color: #94a3b8;">Not yet quoted</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Status</span>
                <span><?= escape_html($selectedRequest['Payment Status'] ?? 'Pending Quote') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Last Updated</span>
                <span><?= escape_html($selectedRequest['Updated At'] ?? '') ?></span>
            </div>
        </div>

        <?php if (floatval($selectedRequest['Quote Amount'] ?? 0) > 0 && ($selectedRequest['Payment Status'] ?? '') === 'Awaiting Payment'): ?>
            <div class="quote-card">
                <h2>Ready to Pay?</h2>
                <p>Your quote is ready. Click below to complete the payment.</p>
                <a class="btn-primary" href="pay.php?id=<?= escape_html($selectedRequest['ID'] ?? '') ?>" style="display: inline-block; text-decoration: none;">Pay Now</a>
            </div>
        <?php elseif (($selectedRequest['Payment Status'] ?? '') === 'Completed'): ?>
            <div class="form-success">
                <h2>✓ Payment Complete</h2>
                <p>Payment ID: <strong><?= escape_html($selectedRequest['Payment ID'] ?? '') ?></strong></p>
            </div>
        <?php endif; ?>

        <p style="margin-top: 1.5rem; text-align: center;">
            <a href="customer-dashboard.php" style="color: var(--accent); text-decoration: none; font-weight: 600;">Back to dashboard</a>
        </p>
    </main>
</body>
</html>
