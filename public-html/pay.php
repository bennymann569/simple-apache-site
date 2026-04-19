<?php
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

$csvFile = __DIR__ . '/data/requests.csv';
$requestId = trim($_GET['id'] ?? '');
$requests = read_request_csv($csvFile);
$selectedRequest = null;

foreach ($requests as $request) {
    if (($request['ID'] ?? '') === $requestId) {
        $selectedRequest = $request;
        break;
    }
}

if ($selectedRequest === null) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>Not Found</title><link rel="stylesheet" href="styles.css"></head><body><main class="page"><h1>Quote Not Found</h1><p>Unable to find quote request. Please check the link and try again.</p></main></body></html>';
    exit;
}

$quoteAmount = floatval($selectedRequest['Quote Amount'] ?? 0);
$paymentStatus = $selectedRequest['Payment Status'] ?? 'Pending Quote';
$paymentId = $selectedRequest['Payment ID'] ?? '';

if ($quoteAmount <= 0) {
    http_response_code(400);
    echo '<!DOCTYPE html><html><head><title>Quote Not Ready</title><link rel="stylesheet" href="styles.css"></head><body><main class="page"><h1>Quote Not Ready</h1><p>This quote does not yet have a price. Please contact us.</p></main></body></html>';
    exit;
}

if ($paymentStatus === 'Completed' && $paymentId !== '') {
    echo '<!DOCTYPE html><html><head><title>Already Paid</title><link rel="stylesheet" href="styles.css"></head><body><main class="page"><h1>Payment Complete</h1><p>This quote has already been paid. Thank you!</p><p>Payment ID: ' . htmlspecialchars($paymentId, ENT_QUOTES, 'UTF-8') . '</p></main></body></html>';
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardName = trim($_POST['card_name'] ?? '');
    $cardNumber = trim($_POST['card_number'] ?? '');
    $cardExpiry = trim($_POST['card_expiry'] ?? '');
    $cardCvc = trim($_POST['card_cvc'] ?? '');

    $errors = [];
    if ($cardName === '') {
        $errors[] = 'Name is required.';
    }
    if (!preg_match('/^\d{4}\s?\d{4}\s?\d{4}\s?\d{4}$/', $cardNumber)) {
        $errors[] = 'Card number is invalid.';
    }
    if (!preg_match('/^\d{2}\/\d{2}$/', $cardExpiry)) {
        $errors[] = 'Expiry date must be MM/YY.';
    }
    if (!preg_match('/^\d{3,4}$/', $cardCvc)) {
        $errors[] = 'CVC is invalid.';
    }

    if (!empty($errors)) {
        $error = implode(' ', $errors);
    } else {
        // Process mock payment
        $paymentId = 'MOCK_' . strtoupper(bin2hex(random_bytes(6)));
        foreach ($requests as &$request) {
            if (($request['ID'] ?? '') === $requestId) {
                $request['Payment Status'] = 'Completed';
                $request['Payment ID'] = $paymentId;
                $request['Updated At'] = date('Y-m-d H:i:s');
                $selectedRequest = $request;
                break;
            }
        }
        unset($request);
        save_request_csv($csvFile, $requests);
        $message = 'Payment processed successfully!';
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
    <title>Payment</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="page">
        <header>
            <div>
                <h1>Payment</h1>
                <p class="intro">Complete your cleaning service quote payment.</p>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="form-success">
                <h2>✓ Payment Successful</h2>
                <p><?= escape_html($message) ?></p>
                <p>Payment ID: <strong><?= escape_html($selectedRequest['Payment ID']) ?></strong></p>
                <p style="margin-top: 1rem;"><a href="index.html">Return to homepage</a></p>
            </div>
        <?php else: ?>
            <div class="quote-card">
                <h2>Quote Details</h2>
                <div class="detail-row">
                    <span class="detail-label">Service</span>
                    <span><?= escape_html($selectedRequest['Service'] ?? '') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount Due</span>
                    <span style="font-size: 1.5em; font-weight: bold; color: var(--accent);">$<?= number_format($quoteAmount, 2) ?></span>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="form-error"><?= escape_html($error) ?></div>
            <?php endif; ?>

            <div class="quote-card">
                <h2>Payment Information</h2>
                <p style="color: #94a3b8; font-size: 0.9em; margin-bottom: 1.5rem;">This is a mock payment system for testing. Use any valid-looking card details.</p>
                <form method="post" class="login-form">
                    <label>
                        Cardholder Name
                        <input type="text" name="card_name" placeholder="John Doe" required>
                    </label>
                    <label>
                        Card Number
                        <input type="text" name="card_number" placeholder="1234 5678 9012 3456" required>
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <label>
                            Expiry (MM/YY)
                            <input type="text" name="card_expiry" placeholder="12/25" required>
                        </label>
                        <label>
                            CVC
                            <input type="text" name="card_cvc" placeholder="123" required>
                        </label>
                    </div>
                    <button class="btn-primary" type="submit">Pay $<?= number_format($quoteAmount, 2) ?></button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
