<?php
declare(strict_types=1);

// HTML escape
function h(?string $s): string {
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Redirect helper
function redirect(string $path): never {
	header('Location: ' . $path);
	exit;
}

// Require login and return current user id
function require_login(): int {
	if (!isset($_SESSION['user_id'])) {
		// Attempt auto-login via remember cookie
		if (isset($_COOKIE['remember'])) {
			[$sel, $ver] = array_pad(explode(':', $_COOKIE['remember'], 2), 2, '');
			if ($sel && $ver) {
				$pdo = db();
				$stmt = $pdo->prepare('SELECT user_id, token_hash, expires_at FROM remember_tokens WHERE selector = ? LIMIT 1');
				$stmt->execute([$sel]);
				$row = $stmt->fetch();
				if ($row) {
					if (strtotime($row['expires_at']) > time() && hash_equals($row['token_hash'], hash('sha256', $ver))) {
						$_SESSION['user_id'] = (int)$row['user_id'];
						return (int)$row['user_id'];
					} else {
						// Expired or mismatch: cleanup
						$pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$sel]);
						setcookie('remember', '', time() - 3600, '/', '', false, true);
					}
				}
			}
			redirect('/php/signup-login.php');
		}
		redirect('/php/signup-login.php');
	}
	return (int)$_SESSION['user_id'];
}

function create_remember_token(int $userId): void {
	$selector = bin2hex(random_bytes(6)); // 12 chars
	$verifier = bin2hex(random_bytes(18)); // 36 chars raw -> 72 hex, truncated client side ok
	$hash = hash('sha256', $verifier);
	$expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30); // 30 days
	$pdo = db();
	$stmt = $pdo->prepare('INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?,?,?,?)');
	$stmt->execute([$userId, $selector, $hash, $expires]);
	$cookie = $selector . ':' . $verifier;
	setcookie('remember', $cookie, [
		'expires' => time() + 60 * 60 * 24 * 30,
		'path' => '/',
		'secure' => false,
		'httponly' => true,
		'samesite' => 'Lax'
	]);
}

function clear_remember_token(?int $userId = null): void {
	if (isset($_COOKIE['remember'])) {
		[$sel] = explode(':', $_COOKIE['remember'], 2);
		$pdo = db();
		$pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$sel]);
		setcookie('remember', '', time() - 3600, '/', '', false, true);
	}
	if ($userId) {
		$pdo = db();
		$pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$userId]);
	}
}

// Fetch minimal current user info
function current_user(PDO $pdo): ?array {
	if (!isset($_SESSION['user_id'])) return null;
	$stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE id = ?');
	$stmt->execute([$_SESSION['user_id']]);
	$row = $stmt->fetch();
	return $row ?: null;
}

// Flash messages
function store_flash(string $key, string $msg): void {
	$_SESSION['flash'][$key] = $msg;
}

function get_flash(string $key): ?string {
	$v = $_SESSION['flash'][$key] ?? null;
	unset($_SESSION['flash'][$key]);
	return $v;
}

// File uploads (10MB default cap)
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
	if (!defined('UPLOADS_DIR')) {
		return ['ok' => false, 'path' => null, 'error' => 'Uploads directory not configured.'];
	}
	$basename = bin2hex(random_bytes(8)) . '.' . $ext;
	$dest = rtrim(UPLOADS_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $basename;
	if (!@is_dir(UPLOADS_DIR) && !@mkdir(UPLOADS_DIR, 0775, true)) {
		return ['ok' => false, 'path' => null, 'error' => 'Cannot create uploads directory.'];
	}
	if (!@move_uploaded_file($file['tmp_name'], $dest)) {
		return ['ok' => false, 'path' => null, 'error' => 'Failed to save file.'];
	}
	// Public path relative to site root
	$public = '/uploads/team_logos/' . $basename;
	return ['ok' => true, 'path' => $public, 'error' => null];
}

