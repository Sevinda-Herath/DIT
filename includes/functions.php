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

// Attempt remember cookie auto-login without redirect. Returns true if session set.
function attempt_auto_login(): bool {
	if (isset($_SESSION['user_id'])) return true;
	if (!isset($_COOKIE['remember'])) return false;
	[$sel, $ver] = array_pad(explode(':', $_COOKIE['remember'], 2), 2, '');
	if (!$sel || !$ver) return false;
	$pdo = db();
	$stmt = $pdo->prepare('SELECT user_id, token_hash, expires_at FROM remember_tokens WHERE selector = ? LIMIT 1');
	$stmt->execute([$sel]);
	$row = $stmt->fetch();
	if (!$row) return false;
	if (strtotime($row['expires_at']) > time() && hash_equals($row['token_hash'], hash('sha256', $ver))) {
		$_SESSION['user_id'] = (int)$row['user_id'];
		return true;
	}
	// cleanup invalid
	$pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$sel]);
	setcookie('remember', '', time() - 3600, '/', '', false, true);
	return false;
}

// Require login (redirect if not); uses attempt_auto_login first
function require_login(): int {
	if (!isset($_SESSION['user_id'])) {
		if (!attempt_auto_login()) {
			redirect('/php/signup-login.php');
		}
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
	$stmt = $pdo->prepare('SELECT id, username, email, role FROM users WHERE id = ?');
	$stmt->execute([$_SESSION['user_id']]);
	$row = $stmt->fetch();
	return $row ?: null;
}

// Require a minimum role; order: user < organizer < admin < head_admin
function require_role(string $role): array {
	$hierarchy = [ 'user' => 0, 'organizer' => 1, 'admin' => 2, 'head_admin' => 3 ];
	$pdo = db();
	$uid = require_login();
	$user = current_user($pdo);
	if (!$user) redirect('/php/signup-login.php');
	$have = $hierarchy[$user['role'] ?? 'user'] ?? 0;
	$need = $hierarchy[$role] ?? PHP_INT_MAX;
	if ($have < $need) {
		http_response_code(403);
		// Styled minimal page (avoid recursive includes)
		echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>403 Forbidden</title>'
			.'<link rel="stylesheet" href="/assets/css/style.css"></head><body style="padding:120px 20px;font-family:var(--fontFamily-oxanium,system-ui,sans-serif);">'
			.'<div style="max-width:720px;margin:0 auto;background:var(--bg-oxford-blue-alpha-90,#161b2b);padding:40px 34px;border:2px solid var(--border-purple-alpha-30,#5a2d7a);border-radius:12px;box-shadow:0 10px 40px -10px rgba(0,0,0,.6);">'
			.'<h1 class="h2" style="margin:0 0 10px;">Access <span class="span">Denied</span></h1>'
			.'<p style="margin:0 0 18px;font-size:1.5rem;line-height:1.5;color:var(--text-gainsboro);">Your account role (<strong>'.htmlspecialchars($user['role']).'</strong>) does not have permission to view this page. Required level: <strong>'.htmlspecialchars($role).'</strong>.</p>'
			.'<div style="display:flex;flex-wrap:wrap;gap:12px;">'
			.'<a class="btn" href="/php/user.php" data-btn>My Profile</a>'
			.( ($user['role'] === 'admin') ? '<a class="btn" href="/php/dashboard-admin.php" data-btn>Admin Dashboard</a>' : '' )
			.( ($user['role'] === 'organizer') ? '<a class="btn" href="/php/dashboard-organizer.php" data-btn>Organizer Dashboard</a>' : '' )
			.'</div>'
			.'</div></body></html>';
		exit;
	}
	return $user; // include id, username, email, role
}

function has_role(array $user, string $role): bool {
	$hierarchy = [ 'user' => 0, 'organizer' => 1, 'admin' => 2, 'head_admin' => 3 ];
	$have = $hierarchy[$user['role'] ?? 'user'] ?? 0;
	$need = $hierarchy[$role] ?? 99;
	return $have >= $need;
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

// Generate and store new set of 10 recovery codes (returns plain codes)
function generate_recovery_codes(int $userId): array {
	$pdo = db();
	// Remove existing unused codes (or all) then insert new set
	$pdo->prepare('DELETE FROM recovery_codes WHERE user_id = ?')->execute([$userId]);
	$codes = [];
	$ins = $pdo->prepare('INSERT INTO recovery_codes (user_id, code_hash) VALUES (?, ?)');
	for ($i=0; $i<10; $i++) {
		// human friendly: 4 groups of 3 alnum (exclude confusing chars)
		$raw = '';
		$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		for ($g=0;$g<4;$g++) {
			$seg='';
			for($c=0;$c<3;$c++) { $seg .= $alphabet[random_int(0, strlen($alphabet)-1)]; }
			$raw .= ($g?'-':'').$seg;
		}
		$codes[] = $raw;
		$ins->execute([$userId, hash('sha256', $raw)]);
	}
	return $codes;
}

// Verify and consume a recovery code for given email + nic (returns user id or null)
function consume_recovery_code(string $email, string $nic, string $code): ?int {
	$pdo = db();
	$stmt = $pdo->prepare('SELECT u.id FROM users u JOIN profiles p ON p.user_id = u.id WHERE u.email = ? AND p.nic = ? LIMIT 1');
	$stmt->execute([$email, $nic]);
	$row = $stmt->fetch();
	if(!$row) return null;
	$userId = (int)$row['id'];
	$hash = hash('sha256', $code);
	$c = $pdo->prepare('SELECT id, used_at FROM recovery_codes WHERE user_id = ? AND code_hash = ? LIMIT 1');
	$c->execute([$userId, $hash]);
	$codeRow = $c->fetch();
	if(!$codeRow || $codeRow['used_at']) return null;
	$pdo->prepare('UPDATE recovery_codes SET used_at = NOW() WHERE id = ?')->execute([$codeRow['id']]);
	return $userId;
}

