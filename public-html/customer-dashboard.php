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
$csvFile = __DIR__ . '/data/requests.csv';
$allRequests = read_request_csv($csvFile);

// Filter requests by customer email
$customerRequests = array_filter($allRequests, function ($request) use ($customer) {
    return ($request['Email'] ?? '') === ($customer['Email'] ?? '');
});

$requestCount = count($customerRequests);

function escape_html(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="page">
        <header>
            <div>
                <h1>My Account</h1>
                <p class="intro">Welcome, <?= escape_html($customer['Name'] ?? '') ?>. View your quotes and payments below.</p>
            </div>
            <div>
                <a class="logout-link" href="customer-logout.php">Logout</a>
                <span class="tag">Customer</span>
            </div>
        </header>

        <div class="detail-card">
            <div class="detail-row">
                <span class="detail-label">Email</span>
                <span><?= escape_html($customer['Email'] ?? '') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Name</span>
                <span><?= escape_html($customer['Name'] ?? '') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone</span>
                <span><?= escape_html($customer['Phone'] ?? '') ?: '—' ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Member Since</span>
                <span><?= escape_html($customer['Created At'] ?? '') ?></span>
            </div>
        </div>

        <h2 style="margin-top: 2rem; margin-bottom: 1rem;">Your Quotes</h2>

        <?php if ($requestCount === 0): ?>
            <div class="quote-card">
                <p>You don't have any quote requests yet. <a href="index.html" style="color: var(--accent); text-decoration: none; font-weight: 600;">Request a quote</a></p>
            </div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Submitted</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Payment Status</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($customerRequests) as $request): ?>
                            <tr>
                                <td><?= escape_html($request['ID'] ?? '') ?></td>
                                <td><?= escape_html($request['Timestamp'] ?? '') ?></td>
                                <td><?= escape_html($request['Service'] ?? '') ?></td>
                                <td>
                                    <?php $amount = floatval($request['Quote Amount'] ?? 0); ?>
                                    <?php if ($amount > 0): ?>
                                        <strong>$<?= number_format($amount, 2) ?></strong>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= escape_html($request['Payment Status'] ?? 'Pending Quote') ?></td>
                                <td><?= escape_html($request['Status'] ?? '') ?></td>
                                <td>
                                    <a class="view-link" href="customer-request-detail.php?id=<?= escape_html($request['ID'] ?? '') ?>">View</a>
                                    <?php if (floatval($request['Quote Amount'] ?? 0) > 0 && ($request['Payment Status'] ?? '') === 'Awaiting Payment'): ?>
                                        <a class="view-link" href="pay.php?id=<?= escape_html($request['ID'] ?? '') ?>" style="color: #10b981;">Pay Now</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p style="margin-top: 2rem; text-align: center; color: var(--muted);">
            <a href="index.html" style="color: var(--accent); text-decoration: none; font-weight: 600;">Request a new quote</a>
        </p>
    </main>
</body>
</html>
