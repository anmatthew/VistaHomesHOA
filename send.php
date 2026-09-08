<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Use the contact form to send a message.']);
    exit;
}

if (!empty($_POST['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

function field($key, $max = 200) {
    $value = isset($_POST[$key]) ? trim((string) $_POST[$key]) : '';
    $value = str_replace(["\r", "\n", "\0"], ' ', $value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max);
    }
    return substr($value, 0, $max);
}

function header_safe($value) {
    return str_replace(["\r", "\n", "\0"], '', $value);
}

$name    = field('name', 120);
$email   = field('email', 160);
$unit    = field('unit', 80);
$phone   = field('phone', 40);
$subject = field('subject', 80);
$message = field('message', 4000);

if ($name === '' || $subject === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please enter your name, a valid email, a subject, and a message.']);
    exit;
}

$to          = 'directors@vistahomeshoa.net';
$fromAddress = 'no-reply@vistahomeshoa.net';
$mailSubject = 'WEB Contact - Vista Homes HOA : ' . $subject;

$body  = "A message was submitted from the Vista Homes HOA website.\n\n";
$body .= "Name:    {$name}\n";
$body .= "Email:   {$email}\n";
$body .= "Unit:    {$unit}\n";
$body .= "Phone:   {$phone}\n";
$body .= "Subject: {$subject}\n\n";
$body .= $message . "\n";

$headers = implode("\r\n", [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: Vista Homes HOA Website <' . $fromAddress . '>',
    'Reply-To: ' . header_safe($email),
    'X-Mailer: PHP/' . phpversion()
]);

// 5th argument sets the envelope sender. Many hosts require this
// and it must be an address on YOUR domain.
$sent = mail($to, $mailSubject, $body, $headers, '-f' . $fromAddress);

if (!$sent) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'The server could not send email. Ask the host to enable PHP mail() for ' . $fromAddress . ', or use SMTP.'
    ]);
    exit;
}

echo json_encode(['ok' => true]);
