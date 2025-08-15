<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

verify_csrf();

$pdo = db();
$userId = require_login();

// Helper sanitize
function val(string $k): string { return trim((string)($_POST[$k] ?? '')); }

$allowedGames = ['pubg_mobile','free_fire','cod_pc','pubg_pc'];
$rawGames = $_POST['game_titles'] ?? [];
if (!is_array($rawGames)) { $rawGames = []; }
$gameTitles = array_values(array_intersect($allowedGames, array_map('strval', $rawGames)));

$playersCount = (int)($_POST['players_count'] ?? 1);
if ($playersCount < 1) $playersCount = 1; elseif ($playersCount > 5) $playersCount = 5;

// Handle upload (optional)
$upload = handle_upload($_FILES['team_logo'] ?? null, ['png','jpg','jpeg','gif'], 2 * 1024 * 1024); // 2MB limit
if (!$upload['ok']) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $upload['error']]);
    exit;
}
$logoPath = $upload['path'] ?? null; // null if not provided

try {
    $pdo->beginTransaction();

    // Update users table (username only; email is immutable/view-only)
    $username = val('username');
    if ($username === '') {
        throw new RuntimeException('Username is required.');
    }
    // Fetch existing email to return in response (and enforce immutability)
    $existingEmailStmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
    $existingEmailStmt->execute([$userId]);
    $existingEmail = (string)($existingEmailStmt->fetchColumn() ?: '');
    if ($existingEmail === '') {
        throw new RuntimeException('Account email missing.');
    }
    $uStmt = $pdo->prepare('UPDATE users SET username = ? WHERE id = ?');
    $uStmt->execute([$username, $userId]);

    // Upsert profile
    $profileFields = [
        'full_name' => val('full_name'),
        'dob' => val('dob') ?: null,
        'location' => val('location'),
        'university' => val('university'),
        'nic' => val('nic'),
        'mobile' => val('mobile'),
        'team_name' => val('team_name'),
        'team_captain' => val('team_captain'),
        'players_count' => $playersCount,
        'game_titles' => json_encode($gameTitles, JSON_UNESCAPED_UNICODE),
    ];
    if ($logoPath) {
        $profileFields['team_logo_path'] = $logoPath;
    }

    $columns = array_keys($profileFields);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $updates = implode(', ', array_map(fn($c) => "$c = VALUES($c)", $columns));
    $values = array_values($profileFields);
    $pSql = 'INSERT INTO profiles (user_id,' . implode(',', $columns) . ') VALUES (?, ' . $placeholders . ') ON DUPLICATE KEY UPDATE ' . $updates;
    $pdo->prepare($pSql)->execute(array_merge([$userId], $values));

    // Members: delete then insert limited to playersCount
    $pdo->prepare('DELETE FROM members WHERE user_id = ?')->execute([$userId]);
    $mIns = $pdo->prepare('INSERT INTO members (user_id, idx, name, nic, email, phone) VALUES (?,?,?,?,?,?)');
    $membersOut = [];
    for ($i = 1; $i <= $playersCount; $i++) {
        $name = val('member' . $i . '_name');
        $nic = val('member' . $i . '_nic');
        $memEmail = val('member' . $i . '_email');
        $phone = val('member' . $i . '_phone');
        if ($name || $nic || $memEmail || $phone) { // Only insert if there is some data
            $mIns->execute([$userId, $i, $name, $nic, $memEmail, $phone]);
        }
        $membersOut[] = [ 'name' => $name, 'nic' => $nic, 'email' => $memEmail, 'phone' => $phone ];
    }

    $pdo->commit();

    $response = [
        'username' => $username,
        'email' => $existingEmail,
        'full_name' => $profileFields['full_name'],
        'dob' => $profileFields['dob'],
        'location' => $profileFields['location'],
        'university' => $profileFields['university'],
        'nic' => $profileFields['nic'],
        'mobile' => $profileFields['mobile'],
        'team_name' => $profileFields['team_name'],
        'team_captain' => $profileFields['team_captain'],
        'players_count' => $playersCount,
        'game_titles' => $gameTitles,
        'team_logo_url' => $logoPath ?: (val('existing_team_logo_path') ?: ''),
        'members' => $membersOut
    ];

    echo json_encode(['ok' => true, 'data' => $response], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $msg = $e->getMessage();
    if (stripos($msg, 'Duplicate') !== false) {
        $msg = 'Username or Email already taken.';
    }
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $msg]);
}
