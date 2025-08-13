<?php
declare(strict_types=1);

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function redirect(string $path): never {
    header('Location: ' . $path);
    exit;
}

function require_login(): int {
    if (!isset($_SESSION['user_id'])) {
        redirect('/pages/signup-login.php');
    }
    return (int)$_SESSION['user_id'];
}

function current_user(PDO $pdo): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function store_flash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function get_flash(string $key): ?string {
    $v = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $v;
}

function handle_upload(?array $file, array $allowExt = ['png','jpg','jpeg','gif'], int $maxBytes = 10485760): array {
    // Returns [ 'ok' => bool, 'path' => string|null, 'error' => string|null ]
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => null, 'error' => null];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => null, 'error' => 'Upload error.'];
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        return ['ok' => false, 'path' => null, 'error' => 'File too large. Max 10MB.'];
    }
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, $allowExt, true)) {
        return ['ok' => false, 'path' => null, 'error' => 'Invalid file type.'];
    }
    $basename = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOADS_DIR . '/' . $basename;
    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'path' => null, 'error' => 'Failed to save file.'];
    }
    // Public path relative to site root
    $public = '/uploads/team_logos/' . $basename;
    return ['ok' => true, 'path' => $public, 'error' => null];
}
