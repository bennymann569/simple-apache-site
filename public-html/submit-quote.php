<?php
function sanitize($value) {
    return trim(filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
}

function smtp_command($socket, string $command, array $expectedCodes): array {
    if ($command !== '') {
        fputs($socket, $command . "\r\n");
    }
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    $code = intval(substr($response, 0, 3));
    return [$code, $response];
}

function smtp_send(string $host, int $port, string $username, string $password, string $from, string $to, string $message, bool $startTls = true): bool {
    $socket = fsockopen($host, $port, $errno, $errstr, 10);
    if (!$socket) {
        return false;
    }

    [$code] = smtp_command($socket, '', []);
    if ($code !== 220) {
        fclose($socket);
        return false;
    }

    [$code, $response] = smtp_command($socket, 'EHLO ' . gethostname(), [250]);
    if ($startTls && stripos($response, 'STARTTLS') !== false) {
        [$code] = smtp_command($socket, 'STARTTLS', [220]);
        if ($code !== 220) {
            fclose($socket);
            return false;
        }
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        smtp_command($socket, 'EHLO ' . gethostname(), [250]);
    }

    if ($username !== '') {
        smtp_command($socket, 'AUTH LOGIN', [334]);
        smtp_command($socket, base64_encode($username), [334]);
        smtp_command($socket, base64_encode($password), [235]);
    }

    smtp_command($socket, "MAIL FROM:<$from>", [250]);
    smtp_command($socket, "RCPT TO:<$to>", [250, 251]);
    smtp_command($socket, 'DATA', [354]);

    $data = $message;
    if (substr($data, -2) !== "\r\n") {
        $data .= "\r\n";
    }
    $data .= ".\r\n";
    smtp_command($socket, $data, [250]);
    smtp_command($socket, 'QUIT', [221]);
    fclose($socket);
    return true;
}

function write_csv(string $csvFile, array $fields, array $rows): bool {
    if (($handle = fopen($csvFile, 'w')) === false) {
        return false;
    }

    fputcsv($handle, $fields);
    foreach ($rows as $row) {
        $record = [];
        foreach ($fields as $field) {
            $record[] = $row[$field] ?? '';
        }
        fputcsv($handle, $record);
    }

    fclose($handle);
    return true;
}

function read_csv(string $csvFile): array {
    $rows = [];
    if (!file_exists($csvFile) || !is_readable($csvFile)) {
        return $rows;
    }

    if (($handle = fopen($csvFile, 'r')) === false) {
        return $rows;
    }

    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        return $rows;
    }

    while (($data = fgetcsv($handle)) !== false) {
        $data = array_pad($data, count($headers), '');
        $rows[] = array_combine($headers, $data);
    }
    fclose($handle);
    return $rows;
}

function ensure_csv_schema(string $csvFile, array $fields): array {
    $rows = read_csv($csvFile);
    if (!file_exists($csvFile)) {
        return $rows;
    }

    $currentHeaders = [];
    if (($handle = fopen($csvFile, 'r')) !== false) {
        $currentHeaders = fgetcsv($handle) ?: [];
        fclose($handle);
    }

    if ($currentHeaders === $fields) {
        return $rows;
    }

    $updatedRows = [];
    foreach ($rows as $row) {
        $updatedRow = array_fill_keys($fields, '');
        foreach ($fields as $field) {
            if (isset($row[$field])) {
                $updatedRow[$field] = $row[$field];
            }
        }
        if (empty($updatedRow['ID'])) {
            $updatedRow['ID'] = uniqid('req_', true);
        }
        if (empty($updatedRow['Status'])) {
            $updatedRow['Status'] = 'New';
        }
        if (empty($updatedRow['Updated At'])) {
            $updatedRow['Updated At'] = $updatedRow['Timestamp'] ?: date('Y-m-d H:i:s');
        }
        $updatedRows[] = $updatedRow;
    }

    write_csv($csvFile, $fields, $updatedRows);
    return $updatedRows;
}

function format_email_text(array $data): string {
    return sprintf(
        "New Cleaning Quote Request\n\nName: %s\nEmail: %s\nPhone: %s\nService: %s\nDetails: %s\nSubmitted: %s\n",
        $data['Name'],
        $data['Email'],
        $data['Phone'],
        $data['Service'],
        $data['Details'],
        $data['Timestamp']
    );
}

function format_email_html(array $data): string {
    return sprintf(
        '<html><body><h2>New Cleaning Quote Request</h2><p><strong>Name:</strong> %s</p><p><strong>Email:</strong> %s</p><p><strong>Phone:</strong> %s</p><p><strong>Service:</strong> %s</p><p><strong>Details:</strong><br>%s</p><p><strong>Submitted:</strong> %s</p><hr><p><em>Payment pending quote amount. Visit the admin dashboard to set a price and send the payment link to the customer.</em></p></body></html>',
        htmlspecialchars($data['Name'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($data['Email'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($data['Phone'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($data['Service'], ENT_QUOTES, 'UTF-8'),
        nl2br(htmlspecialchars($data['Details'], ENT_QUOTES, 'UTF-8')),
        $data['Timestamp']
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

$name = sanitize($_POST['name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$service = sanitize($_POST['service'] ?? '');
$details = sanitize($_POST['details'] ?? '');
$timestamp = date('Y-m-d H:i:s');

$errors = [];
if ($name === '') {
    $errors[] = 'Name is required.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}
if ($details === '') {
    $errors[] = 'Project details are required.';
}
if ($phone !== '' && !preg_match('/^[0-9\+\-\(\)\s]+$/', $phone)) {
    $errors[] = 'Phone number contains invalid characters.';
}

$allowedServices = ['Home Cleaning', 'Office Cleaning', 'Deep Cleaning', 'Move-Out Cleaning'];
if (!in_array($service, $allowedServices, true)) {
    $service = 'Other';
}

if (!empty($errors)) {
    $message = implode('<br>', $errors);
    echo "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n<meta charset=\"UTF-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<title>Request Error</title>\n<link rel=\"stylesheet\" href=\"styles.css\">\n</head>\n<body>\n<main class=\"page\">\n<h1>Submission Error</h1>\n<p>The form could not be submitted for the following reason(s):</p>\n<p>$message</p>\n<p><a href=\"index.html\">Return to quote form</a></p>\n</main>\n</body>\n</html>";
    exit;
}

$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$csvFile = $dataDir . '/requests.csv';
$fields = ['ID', 'Timestamp', 'Name', 'Email', 'Phone', 'Service', 'Details', 'Status', 'Updated At', 'Quote Amount', 'Payment Status', 'Payment ID'];
$rows = ensure_csv_schema($csvFile, $fields);

$id = uniqid('req_', true);
$status = 'New';

$rows[] = [
    'ID' => $id,
    'Timestamp' => $timestamp,
    'Name' => $name,
    'Email' => $email,
    'Phone' => $phone,
    'Service' => $service,
    'Details' => $details,
    'Status' => $status,
    'Updated At' => $timestamp,
    'Quote Amount' => '',
    'Payment Status' => 'Pending Quote',
    'Payment ID' => '',
];

if (!write_csv($csvFile, $fields, $rows)) {
    http_response_code(500);
    echo 'Unable to store request. Please try again later.';
    exit;
}

// SMTP configuration: fill these values to enable reliable SMTP emailing.
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_SECURE', 'tls');
define('SMTP_FROM_EMAIL', 'no-reply@your-cleaning-business.com');
define('SMTP_TO_EMAIL', 'info@your-cleaning-business.com');

define('SMTP_USE_PHP_MAIL', true);

if (filter_var(SMTP_TO_EMAIL, FILTER_VALIDATE_EMAIL)) {
    $emailData = [
        'Name' => $name,
        'Email' => $email,
        'Phone' => $phone,
        'Service' => $service,
        'Details' => $details,
        'Timestamp' => $timestamp,
    ];
    $subject = "New Cleaning Quote Request from $name";
    $bodyText = format_email_text($emailData);
    $bodyHtml = format_email_html($emailData);
    $headers = "From: " . SMTP_FROM_EMAIL . "\r\n" .
        "Reply-To: $email\r\n" .
        "MIME-Version: 1.0\r\n" .
        "Content-Type: text/html; charset=UTF-8\r\n";

    if (SMTP_HOST !== '') {
        $smtpBody = "Subject: $subject\r\n" .
            "From: " . SMTP_FROM_EMAIL . "\r\n" .
            "To: " . SMTP_TO_EMAIL . "\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n\r\n" .
            $bodyHtml;
        smtp_send(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM_EMAIL, SMTP_TO_EMAIL, $smtpBody, SMTP_SECURE === 'tls');
    } elseif (SMTP_USE_PHP_MAIL) {
        @mail(SMTP_TO_EMAIL, $subject, $bodyHtml, $headers);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Requested</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="page">
        <header>
            <div>
                <h1>Quote Requested</h1>
                <p class="intro">Thanks, <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>. Your request has been received.</p>
            </div>
            <span class="tag">Request Received</span>
        </header>

        <div class="quote-card">
            <p>We have recorded your quote request for <strong><?= htmlspecialchars($service, ENT_QUOTES, 'UTF-8') ?></strong>. Our team will follow up shortly at <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>.</p>
            <p class="footer"><a href="index.html">Return to homepage</a></p>
        </div>
    </main>
</body>
</html>
