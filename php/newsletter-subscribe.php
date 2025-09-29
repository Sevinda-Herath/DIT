<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

// Only accept POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

verify_csrf();

$email = trim((string)($_POST['email_address'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    store_flash('newsletter', 'Please enter a valid email address.');
    redirect('/index.php#newsletter');
}

$pdo = db();

try {
    $stmt = $pdo->prepare('INSERT INTO newsletter_subscriptions (email, ip, user_agent) VALUES (?,?,?)');
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $stmt->execute([$email, $ip, $ua]);
    store_flash('newsletter', 'Thanks! You are subscribed.');
} catch (Throwable $e) {
    // Duplicate email -> update timestamp silently for freshness
    try {
        $upd = $pdo->prepare('UPDATE newsletter_subscriptions SET created_at = CURRENT_TIMESTAMP, ip = ?, user_agent = ? WHERE email = ?');
        $upd->execute([$_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255), $email]);
        store_flash('newsletter', 'You are already subscribed. Preferences updated.');
    } catch (Throwable $e2) {
        store_flash('newsletter', 'Sorry, we could not process your subscription right now.');
    }
}

redirect('/index.php#newsletter');
