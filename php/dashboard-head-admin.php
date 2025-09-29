<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pdo = db();
$user = require_role('head_admin');

// Simple actions (create/update/delete users & role assignment)
$errors = [];
$messages = [];
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
  if ($action === 'export_newsletter') {
    // Stream CSV export of newsletter subscriptions
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=newsletter_subscriptions.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','email','ip','user_agent','created_at']);
    $stmt = $pdo->query('SELECT id, email, ip, user_agent, created_at FROM newsletter_subscriptions ORDER BY id DESC');
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
      fputcsv($out, $row);
    }
    fclose($out);
    exit;
  }
    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        if (!$username || !$email || !$password) {
            $errors[] = 'All create fields required';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO users (username,email,password_hash,created_at,role) VALUES (?,?,?,?,?)');
                $stmt->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT),date('Y-m-d H:i:s'),$role]);
                $messages[] = 'User created';
            } catch (Throwable $e) {
                $errors[] = 'Failed creating user';
            }
        }
    } elseif ($action === 'update_role') {
        $uid = (int)($_POST['id'] ?? 0);
        $role = $_POST['role'] ?? 'user';
        if ($uid && $uid !== (int)$user['id']) { // head admin can change others
            $stmt = $pdo->prepare('UPDATE users SET role=? WHERE id=?');
            $stmt->execute([$role,$uid]);
            $messages[] = 'Role updated';
        }
    } elseif ($action === 'delete') {
        $uid = (int)($_POST['id'] ?? 0);
        if ($uid && $uid !== (int)$user['id']) {
            $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);
            $messages[] = 'User deleted';
        }
    }
}

$users = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY id DESC")->fetchAll();
// Newsletter data (count + latest)
$newsletter_count = 0;
$newsletter = [];
try {
  $newsletter_count = (int)$pdo->query('SELECT COUNT(*) FROM newsletter_subscriptions')->fetchColumn();
  $newsletter = $pdo->query('SELECT id, email, ip, user_agent, created_at FROM newsletter_subscriptions ORDER BY id DESC LIMIT 50')->fetchAll();
} catch (Throwable $e) {
  // table might not exist yet; ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Head Admin Dashboard</title>
<link rel="shortcut icon" href="../assets/images/nebula-esports.png" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;500;600;700&family=Work+Sans:wght@600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
/* Layout spacing */
.dashboard {margin-top:140px;display:flex;flex-direction:column;gap:32px;}

/* Fancy panel */
.panel {position:relative;overflow:hidden;background:linear-gradient(145deg, rgba(28,20,43,.85), rgba(20,14,30,.85));padding:28px 26px 30px;border:1px solid var(--border-purple-alpha-30);border-radius:18px;box-shadow:0 8px 28px -6px rgba(0,0,0,.55), 0 0 0 1px rgba(140,90,255,0.08) inset;backdrop-filter:blur(4px);}
.panel:before {content:"";position:absolute;inset:0;background:radial-gradient(circle at 85% 15%, rgba(140,75,255,0.25), transparent 60%), radial-gradient(circle at 15% 85%, rgba(60,160,255,0.18), transparent 65%);mix-blend-mode:screen;pointer-events:none;opacity:.9;}
.panel h2.title, .panel h2.h3 {position:relative;padding-left:14px;margin:0 0 18px;font-weight:600;letter-spacing:.6px;}
.panel h2.title:before, .panel h2.h3:before {content:"";position:absolute;left:0;top:6px;width:6px;height:70%;background:linear-gradient(var(--bg-purple), #4d2bff);border-radius:3px;box-shadow:0 0 0 1px rgba(255,255,255,0.15),0 0 10px -2px rgba(120,75,255,.9);} 

/* Messages */
.msgs {display:grid;gap:8px;margin:0 0 18px;}
.msgs .ok, .msgs .err {font-size:1.25rem;padding:10px 14px;border-radius:10px;line-height:1.35;font-weight:500;position:relative;}
.msgs .ok {background:linear-gradient(135deg,#14331c,#0e2415);border:1px solid #215a35;color:#d4f7e2;}
.msgs .ok:before {content:"✔";margin-right:6px;color:#35c46a;}
.msgs .err {background:linear-gradient(135deg,#401a1f,#2a1215);border:1px solid #70323a;color:#ffd6d9;}
.msgs .err:before {content:"⚠";margin-right:6px;color:#ff6b6b;}

/* Create form layout */
.create-form .input-field, .create-form select {background:#120d19;border:1px solid rgba(140,90,255,0.35);color:var(--text-white);padding:10px 14px;font-size:1.4rem;border-radius:10px;transition:.25s border, .25s box-shadow, .25s background;}
.create-form .input-field:focus, .create-form select:focus {outline:none;border-color:var(--text-purple);box-shadow:0 0 0 2px rgba(140,75,255,.35);} 
.create-form button.btn {clip-path:var(--clip-path-3);}

/* Table styling */
.table-wrap {position:relative;overflow:auto;border-radius:14px;border:1px solid rgba(140,90,255,0.25);background:#120d19;box-shadow:0 4px 18px -4px rgba(0,0,0,.55) inset;} 
.table {width:100%;border-collapse:separate;border-spacing:0;font-size:1.25rem;min-width:900px;}
.table thead th {background:linear-gradient(135deg,rgba(140,75,255,0.4),rgba(80,40,150,0.45));color:#fff;font-weight:600;text-transform:uppercase;font-size:1.15rem;letter-spacing:.5px;position:sticky;top:0;backdrop-filter:blur(3px);padding:12px 14px;border-bottom:1px solid rgba(255,255,255,0.08);} 
.table tbody td {padding:12px 14px;border-bottom:1px solid rgba(255,255,255,0.06);color:var(--text-gainsboro);} 
.table tbody tr:last-child td {border-bottom:none;}
.table tbody tr {transition:.25s background, .25s transform;}
.table tbody tr:hover {background:rgba(140,75,255,0.08);} 
.table a {font-weight:600;}

/* Role badge */
.badge-role {display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:30px;font-size:1.15rem;line-height:1.1;background:linear-gradient(120deg,rgba(140,75,255,.3),rgba(60,160,255,.25));border:1px solid rgba(140,75,255,.45);box-shadow:0 0 0 1px rgba(255,255,255,0.07) inset,0 4px 10px -4px rgba(0,0,0,.6);} 
.badge-role:before {content:"★";font-size:1.1rem;color:var(--text-purple);}

/* Inline forms */
form.inline {display:inline-flex;align-items:center;gap:6px;}
.input-field.small, select.input-field.small {max-width:160px;min-width:140px;padding:8px 10px;font-size:1.25rem;} 
select.input-field.small {background:#1a1324;border:1px solid rgba(140,90,255,.4);border-radius:8px;color:var(--text-white);}
select.input-field.small:focus {outline:none;border-color:var(--text-purple);}

/* Action buttons */
td form.inline button.btn {min-width:100px;height:42px;font-size:1.2rem;padding:0 18px;background-image:var(--gradient-1);}
td form.inline button.btn:hover {filter:brightness(1.15);} 
td form.inline button.btn[style*='background:#842e3a'] {background:#842e3a;}
td form.inline button.btn[style*='background:#842e3a']:hover {background:#a94050;}

/* Heading flair */
h1.section-title {position:relative;display:inline-block;padding:0 6px 10px;margin:0 0 14px;}
h1.section-title:after {content:"";position:absolute;left:0;bottom:0;width:100%;height:4px;background:linear-gradient(90deg,var(--bg-purple),transparent);border-radius:3px;}

/* Top status bar */
.status-bar {background:linear-gradient(100deg,rgba(140,75,255,0.35),rgba(40,30,60,0.65));border:1px solid rgba(140,75,255,0.45);box-shadow:0 8px 26px -10px rgba(140,75,255,0.55) inset,0 2px 8px -2px rgba(0,0,0,.7);}

/* Scrollbars inside table */
.table-wrap::-webkit-scrollbar {height:10px;}
.table-wrap::-webkit-scrollbar-track {background:#0f0b15;}
.table-wrap::-webkit-scrollbar-thumb {background:var(--bg-purple);border-radius:20px;}

/* Responsive */
@media (max-width: 900px) {
  .create-form {grid-template-columns:1fr;} 
  .table {font-size:1.2rem;}
  .panel {padding:22px 18px;}
}
@media (max-width: 600px){
  .badge-role {padding:3px 10px;font-size:1.05rem;}
  td form.inline {flex-wrap:wrap;}
}
</style>
</head>
<body id="top">
<header class="header active" data-header>
  <div class="container">
    <a href="#" class="logo"><img src="../assets/images/nebula-esports.png" style="width:80px;" alt="logo"></a>
        <nav class="navbar" data-navbar>
          <ul class="navbar-list">

            <li class="navbar-item">
              <a href="../index.php#home" class="navbar-link" data-nav-link>home</a>
            </li>

            <li class="navbar-item">
              <a href="../index.php#tournament" class="navbar-link" data-nav-link>tournament</a>
            </li>

            <li class="navbar-item">
              <a href="../index.php#news" class="navbar-link" data-nav-link>news</a>
            </li>

            <li class="navbar-item">
              <a href="./contact.php" class="navbar-link" data-nav-link>contact</a>
            </li>
            <li class="navbar-item">
              <a href="./rules.php" class="navbar-link" data-nav-link>Rules & Guidelines</a>
            </li>

          </ul>
        </nav>
    <a href="./user.php"><button class="btn">USER</button></a>
    <form action="../php/logout.php" method="post" style="display:inline;"><?= csrf_field(); ?><button class="btn">LOGOUT</button></form>
          <button class="nav-toggle-btn" aria-label="toggle menu" data-nav-toggler>
          <span class="line line-1"></span>
          <span class="line line-2"></span>
          <span class="line line-3"></span>
        </button>
  </div>
</header>
<main class="dashboard container">
  <div class="status-bar" style="padding:10px 18px;border-radius:14px;font-size:1.25rem;font-weight:500;display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
    Logged in as <strong><?= h($user['username']) ?></strong> (role: <strong><?= h($user['role']) ?></strong>) | Users found: <?= count($users) ?>
  </div>
  <h1 class="h2 section-title">Head <span class="span">Admin</span> Panel</h1>
  <div class="panel revealed" data-reveal="bottom">
    <h2 class="h3 title">Create User / Admin / Organizer</h2>
    <form action="" method="post" class="create-form" autocomplete="off" style="display:grid;gap:10px;max-width:600px;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">
      <input name="username" class="input-field" placeholder="Username" required>
      <input name="email" type="email" class="input-field" placeholder="Email" required>
      <input name="password" type="password" class="input-field" placeholder="Temp Password" required>
      <select name="role" class="input-field" required>
        <option value="user">User</option>
        <option value="organizer">Event Organizer</option>
        <option value="admin">Admin</option>
        <option value="head_admin">Head Admin</option>
      </select>
      <button class="btn" style="max-width:240px;">Create</button>
    </form>
  </div>
  <div class="panel revealed" data-reveal="bottom">
    <h2 class="h3 title">All Accounts</h2>
    <div class="msgs">
      <?php foreach($messages as $m): ?><div class="ok"><?= h($m) ?></div><?php endforeach; ?>
      <?php foreach($errors as $e): ?><div class="err"><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <div class="table-wrap">
  <table class="table">
      <thead><tr><th>ID</th><th>User</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($users as $u): ?>
          <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><a href="dashboard-user-edit.php?id=<?= (int)$u['id'] ?>" style="text-decoration:underline; color: var(--text-purple);"><?= h($u['username']) ?></a></td>
            <td><?= h($u['email']) ?></td>
            <td><span class="badge-role"><?= h($u['role']) ?></span></td>
            <td><?= h($u['created_at']) ?></td>
            <td style="white-space:nowrap;">
              <?php if ((int)$u['id'] !== (int)$user['id']): ?>
              <form class="inline" method="post" action="" style="margin:0 4px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <select name="role" class="input-field small">
                  <?php foreach(['user','organizer','admin','head_admin'] as $r): ?>
                    <option value="<?= $r ?>" <?= $r===$u['role']?'selected':'' ?>><?= $r ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn" style="min-width:120px;">Update</button>
              </form>
              <form class="inline" method="post" action="" onsubmit="return confirm('Delete user?');" style="margin:4px 0;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <button class="btn" style="background:#842e3a;">Delete</button>
              </form>
              <?php else: ?>
                <em>Self</em>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
  </table>
  </div>
  </div>

  <div class="panel revealed" data-reveal="bottom">
    <h2 class="h3 title">Newsletter Subscribers</h2>
    <div class="msgs">
      <div class="ok">Total subscribers: <?= (int)$newsletter_count ?></div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;margin:0 0 12px;flex-wrap:wrap;">
      <form action="" method="post" class="inline" style="margin:0;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="export_newsletter">
        <button class="btn" style="min-width:180px;">Download CSV</button>
      </form>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Email</th>
            <th>IP</th>
            <th>User Agent</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($newsletter): ?>
          <?php foreach ($newsletter as $n): ?>
            <tr>
              <td><?= (int)$n['id'] ?></td>
              <td><?= h($n['email']) ?></td>
              <td><?= h($n['ip']) ?></td>
              <td><?= h($n['user_agent']) ?></td>
              <td><?= h($n['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5">No subscribers yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<script src="../assets/js/script.js"></script>
<script src="../assets/js/bg.js"></script>
</body>
</html>
