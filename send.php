<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Use the contact form to send a message.']);
    exit;
}

// Honeypot — bots often fill hidden fields
if (!empty($_POST['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

function clean($key, $max = 200) {
    $value = trim((string)($_POST[$key] ?? ''));
    $value = str_replace(["\r", "\n"], ' ', $value);
    return mb_substr($value, 0, $max);
}

$name    = clean('name', 120);
$email   = clean('email', 160);
$unit    = clean('unit', 80);
$phone   = clean('phone', 40);
$subject = clean('subject', 80);
$message = trim((string)($_POST['message'] ?? ''));
$message = mb_substr($message, 0, 4000);

if ($name === '' || $subject === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please enter your name, a valid email, a subject, and a message.']);
    exit;
}

$to = 'manager@vistahomeshoa.org'; // change this
$mailSubject = 'Vista Homes HOA contact: ' . $subject;

$body  = "A message was submitted from the Vista Homes HOA website.\n\n";
$body .= "Name:    {$name}\n";
$body .= "Email:   {$email}\n";
$body .= "Unit:    {$unit}\n";
$body .= "Phone:   {$phone}\n";
$body .= "Subject: {$subject}\n\n";
$body .= $message . "\n";

$encodedName = function_exists('mb_encode_mimeheader')
    ? mb_encode_mimeheader($name, 'UTF-8')
    : $name;

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: Vista Homes HOA Website <no-reply@vistahomeshoa.org>',
    'Reply-To: ' . $encodedName . ' <' . $email . '>',
    'X-Mailer: VistaHomesHOA-ContactForm'
];

$sent = @mail($to, $mailSubject, $body, implode("\r\n", $headers));

if (!$sent) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'The server could not send email just now. Please call the office or email directly.']);
    exit;
}

echo json_encode(['ok' => true]);
