<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

verify_csrf();

$fullName = trim((string)($_POST['full_name'] ?? ''));
$email    = trim((string)($_POST['email'] ?? ''));
$subject  = trim((string)($_POST['subject'] ?? ''));
$message  = trim((string)($_POST['message'] ?? ''));

if ($fullName === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
    store_flash('contact', 'Please fill in your name, a valid email, and your message.');
    redirect('/php/contact.php#contact-title');
}

// Basic size limits to avoid abuse
if (strlen($fullName) > 191) $fullName = substr($fullName, 0, 191);
if (strlen($subject) > 191)  $subject  = substr($subject, 0, 191);
if (strlen($email) > 191)    $email    = substr($email, 0, 191);

$pdo = db();
$stmt = $pdo->prepare('INSERT INTO contact_messages (full_name, email, subject, message, ip, user_agent) VALUES (?,?,?,?,?,?)');
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
try {
    $stmt->execute([$fullName, $email, $subject !== '' ? $subject : null, $message, $ip, $ua]);
    store_flash('contact', 'Thanks! Your message has been sent.');
} catch (Throwable $e) {
    store_flash('contact', 'Sorry, we could not send your message right now. Please try again later.');
}

redirect('/php/contact.php#contact-title');
