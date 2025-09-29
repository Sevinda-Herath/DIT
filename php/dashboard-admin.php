<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pdo = db();
$user = require_role('admin'); // admin or head_admin

$errors = [];
$messages = [];
verify_csrf();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'update_message_status') {
    $mid = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'not_replied';
    if ($mid && in_array($status, ['not_replied','replied'], true)) {
      $stmt = $pdo->prepare('UPDATE contact_messages SET status=? WHERE id=?');
      $stmt->execute([$status, $mid]);
      $messages[] = 'Message status updated';
    }
  }
}

$pdoUsers = $pdo->query("SELECT u.id,u.username,u.email,u.role,p.full_name,p.nic FROM users u LEFT JOIN profiles p ON p.user_id=u.id ORDER BY u.id DESC")->fetchAll();

// (Newsletter section removed for admin view)

// Contact messages (hide IP/UA)
$messages_count = 0; $contact_messages = [];
try {
  $messages_count = (int)$pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
  $contact_messages = $pdo->query("SELECT id, full_name, email, subject, message, status, created_at FROM contact_messages ORDER BY id DESC LIMIT 50")->fetchAll();
} catch (Throwable $e) { /* table may not exist */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;500;600;700&family=Work+Sans:wght@600&display=swap" rel="stylesheet">
<link rel="shortcut icon" href="../assets/images/nebula-esports.png" type="image/png">
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
  <div class="status-bar" style="padding:10px 18px;border-radius:14px;font-size:1.25rem;font-weight:500;display:flex;flex-wrap:wrap;gap:12px;align-items:center;background:linear-gradient(100deg,rgba(140,75,255,0.35),rgba(40,30,60,0.65));border:1px solid rgba(140,75,255,0.45);box-shadow:0 8px 26px -10px rgba(140,75,255,0.55) inset,0 2px 8px -2px rgba(0,0,0,.7);margin-bottom:6px;">
    Logged in as <strong><?= h($user['username']) ?></strong> (role: <strong><?= h($user['role']) ?></strong>) | Visible users: <?= count(array_filter($pdoUsers, fn($r)=>in_array($r['role'], ['user','organizer']))) ?> | Contact messages: <?= (int)$messages_count ?>
  </div>
  <h1 class="h2 section-title">Admin <span class="span">Panel</span></h1>
  <div class="panel revealed" data-reveal="bottom">
    <h2 class="h3 title">Manage Users</h2>
    <div class="table-wrap">
    <table class="table">
      <thead><tr><th>ID</th><th>User</th><th>Email</th><th>Full Name</th><th>NIC</th><th>Role</th></tr></thead>
      <tbody>
        <?php foreach($pdoUsers as $u): ?>
          <?php if(in_array($u['role'], ['user','organizer'])): ?>
          <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?php if(in_array($u['role'], ['user','organizer'])): ?><a href="dashboard-user-edit.php?id=<?= (int)$u['id'] ?>" style="color:var(--text-purple);text-decoration:none;"><?= h($u['username']) ?></a><?php else: ?><?= h($u['username']) ?><?php endif; ?></td>
            <td><?= h($u['email']) ?></td>
            <td><?= h($u['full_name'] ?? '—') ?></td>
            <td><?= h($u['nic'] ?? '—') ?></td>
            <td><span class="badge-role"><?= h($u['role']) ?></span></td>
          </tr>
          <?php endif; ?>
        <?php endforeach; ?>
      </tbody>
  </table>
  </div>
  </div>


  <div class="panel revealed" data-reveal="bottom">
    <h2 class="h3 title">Email Messages</h2>
    <div class="msgs">
      <div class="ok">Total messages: <?= (int)$messages_count ?></div>
      <?php foreach($messages as $m): ?><div class="ok"><?= h($m) ?></div><?php endforeach; ?>
      <?php foreach($errors as $e): ?><div class="err"><?= h($e) ?></div><?php endforeach; ?>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Subject</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($contact_messages): ?>
          <?php foreach ($contact_messages as $cm): ?>
            <tr>
              <td><?= (int)$cm['id'] ?></td>
              <td>
                <a href="#" class="card-title" style="text-decoration:underline;color:var(--text-purple);"
                   data-open-message
                   data-id="<?= (int)$cm['id'] ?>"
                   data-name="<?= h($cm['full_name']) ?>"
                   data-email="<?= h($cm['email']) ?>"
                   data-subject="<?= h($cm['subject']) ?>"
                   data-status="<?= h($cm['status']) ?>"
                   data-created="<?= h($cm['created_at']) ?>"
                   data-message="<?= h($cm['message']) ?>">
                   <?= h($cm['full_name']) ?>
                </a>
              </td>
              <td><?= h($cm['email']) ?></td>
              <td><?= h($cm['subject']) ?></td>
              <td><span class="badge-role"><?= h($cm['status']) ?></span></td>
              <td><?= h($cm['created_at']) ?></td>
              <td style="white-space:nowrap;">
                <form class="inline" method="post" action="" style="margin:0;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update_message_status">
                  <input type="hidden" name="id" value="<?= (int)$cm['id'] ?>">
                  <select name="status" class="input-field small">
                    <?php foreach(['not_replied','replied'] as $st): ?>
                      <option value="<?= $st ?>" <?= $st===$cm['status']?'selected':'' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn" style="min-width:120px;">Save</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7">No messages yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Message Modal (no PII) -->
  <div id="messageModal" class="panel" style="display:none;position:fixed;inset:0;margin:auto;max-width:820px;max-height:80vh;z-index:9999;overflow:auto;">
    <h2 class="h3 title">Message Details</h2>
    <div class="grid" style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
      <div class="field"><label>ID</label><div class="value" id="mm_id"></div></div>
      <div class="field"><label>Name</label><div class="value" id="mm_name"></div></div>
      <div class="field"><label>Email</label><div class="value" id="mm_email"></div></div>
      <div class="field"><label>Subject</label><div class="value" id="mm_subject"></div></div>
      <div class="field"><label>Status</label><div class="value" id="mm_status"></div></div>
      <div class="field"><label>Created</label><div class="value" id="mm_created"></div></div>
    </div>
    <div class="field" style="margin-top:10px;">
      <label>Message</label>
      <div class="value" id="mm_message" style="white-space:pre-wrap;line-height:1.5;"></div>
    </div>
    <div style="display:flex;gap:10px;margin-top:14px;">
      <button class="btn" id="mm_close">Close</button>
    </div>
  </div>
  <div id="messageBackdrop" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(2px);z-index:9998"></div>
</main>
<script src="../assets/js/script.js"></script>
<script src="../assets/js/bg.js"></script>
<script>
  (function(){
    const modal = document.getElementById('messageModal');
    const backdrop = document.getElementById('messageBackdrop');
    const mm = {
      id: document.getElementById('mm_id'),
      name: document.getElementById('mm_name'),
      email: document.getElementById('mm_email'),
      subject: document.getElementById('mm_subject'),
      status: document.getElementById('mm_status'),
      created: document.getElementById('mm_created'),
      message: document.getElementById('mm_message'),
      closeBtn: document.getElementById('mm_close')
    };
    function openModal(data){
      mm.id.textContent = data.id || '';
      mm.name.textContent = data.name || '';
      mm.email.textContent = data.email || '';
      mm.subject.textContent = data.subject || '';
      mm.status.textContent = data.status || '';
      mm.created.textContent = data.created || '';
      mm.message.textContent = data.message || '';
      modal.style.display = 'block';
      backdrop.style.display = 'block';
    }
    function closeModal(){
      modal.style.display = 'none';
      backdrop.style.display = 'none';
    }
    document.addEventListener('click', function(e){
      const a = e.target.closest('[data-open-message]');
      if (a){
        e.preventDefault();
        openModal({
          id: a.getAttribute('data-id'),
          name: a.getAttribute('data-name'),
          email: a.getAttribute('data-email'),
          subject: a.getAttribute('data-subject'),
          status: a.getAttribute('data-status'),
          created: a.getAttribute('data-created'),
          message: a.getAttribute('data-message')
        });
        return;
      }
      if (e.target === backdrop){ closeModal(); }
    });
    mm.closeBtn && mm.closeBtn.addEventListener('click', closeModal);
  })();
  </script>
</body>
</html>
