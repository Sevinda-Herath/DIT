<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

verify_csrf();

// Clear all session data
// Clear remember token(s) and session
if (isset($_SESSION['user_id'])) {
    clear_remember_token((int)$_SESSION['user_id']);
}
$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
    // Invalidate session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

header('Location: /index.php');
exit;
