<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    migrate($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void {
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(64) NOT NULL UNIQUE,
        email VARCHAR(191) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
    /* Role column added separately below (ALTER TABLE) to allow existing installs to upgrade safely */
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS profiles (
        user_id INT UNSIGNED NOT NULL PRIMARY KEY,
        full_name VARCHAR(191) NULL,
        dob DATE NULL,
        location VARCHAR(191) NULL,
        university VARCHAR(191) NULL,
        nic VARCHAR(64) NULL,
        mobile VARCHAR(64) NULL,
        team_name VARCHAR(191) NULL,
        team_captain VARCHAR(191) NULL,
        players_count TINYINT UNSIGNED NULL,
        game_titles JSON NULL,
        team_logo_path VARCHAR(255) NULL,
        CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS members (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        idx TINYINT UNSIGNED NOT NULL,
        name VARCHAR(191) NULL,
        nic VARCHAR(64) NULL,
        email VARCHAR(191) NULL,
        phone VARCHAR(64) NULL,
        CONSTRAINT fk_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
        INDEX idx_user_idx (user_id, idx)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $pdo->exec('CREATE TABLE IF NOT EXISTS remember_tokens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        selector CHAR(12) NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
        UNIQUE KEY uniq_selector (selector)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    // One-time password recovery codes (10 generated after signup; user can regenerate)
    $pdo->exec('CREATE TABLE IF NOT EXISTS recovery_codes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        code_hash CHAR(64) NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_recovery_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
        INDEX idx_user_used (user_id, used_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    // Add role column if missing (MySQL 8+: IF NOT EXISTS supported)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('user','head_admin','admin','organizer') NOT NULL DEFAULT 'user' AFTER password_hash");
    } catch (Throwable $e) {
        // Fallback for older MySQL versions: check INFORMATION_SCHEMA then add
        try {
            $colCheck = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME='role'");
            $colCheck->execute([DB_NAME]);
            if (!$colCheck->fetchColumn()) {
                $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user','head_admin','admin','organizer') NOT NULL DEFAULT 'user' AFTER password_hash");
            }
        } catch (Throwable $e2) { /* ignore */ }
    }

    // Ensure at least one head admin exists; configurable via env vars
    try {
        $exists = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='head_admin'")->fetchColumn();
        if ($exists === 0) {
            $seedEmail = getenv('HEAD_ADMIN_EMAIL') ?: '';
            $seedUser  = getenv('HEAD_ADMIN_USERNAME') ?: '';
            $seedPass  = getenv('HEAD_ADMIN_PASSWORD') ?: '';
            // Only seed if all three values are provided via environment
            if ($seedEmail === '' || $seedUser === '' || $seedPass === '') {
                // Skip seeding to avoid creating accounts with weak default credentials
                return;
            }
            // Avoid collision on username/email
            $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ? OR username = ? LIMIT 1');
            $stmt->execute([$seedEmail, $seedUser]);
            if (!$stmt->fetch()) {
                $ins = $pdo->prepare('INSERT INTO users (username,email,password_hash,created_at,role) VALUES (?,?,?,?,?)');
                $ins->execute([$seedUser, $seedEmail, password_hash($seedPass, PASSWORD_DEFAULT), date('Y-m-d H:i:s'), 'head_admin']);
            }
        }
    } catch (Throwable $e) { /* silent seed failure */ }
}
