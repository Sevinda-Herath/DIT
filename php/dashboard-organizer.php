<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pdo = db();
$user = require_role('organizer'); // organizer or above

$rows = $pdo->query("SELECT u.id,u.username,u.email,p.full_name,p.team_name FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.role IN ('user','organizer') ORDER BY u.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Organizer Dashboard</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;500;600;700&family=Work+Sans:wght@600&display=swap" rel="stylesheet">
<link rel="shortcut icon" href="../assets/images/nebula-esports.png" type="image/png">
<style>
.dashboard {margin-top:140px;display:flex;flex-direction:column;gap:34px;}
.panel {position:relative;overflow:hidden;background:linear-gradient(150deg,rgba(28,20,43,.88),rgba(18,12,28,.9));padding:26px 24px 30px;border:1px solid rgba(140,90,255,.4);border-radius:20px;box-shadow:0 8px 28px -8px rgba(0,0,0,.65),0 0 0 1px rgba(255,255,255,0.05) inset;backdrop-filter:blur(4px);}
.panel:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 80% 20%,rgba(140,75,255,.25),transparent 60%),radial-gradient(circle at 18% 84%,rgba(60,160,255,.2),transparent 70%);mix-blend-mode:screen;pointer-events:none;}
.panel h2.h3.title, .panel h2.h3 {margin:0 0 18px;position:relative;padding-left:14px;font-weight:600;letter-spacing:.6px;}
.panel h2.h3.title:before, .panel h2.h3:before {content:"";position:absolute;left:0;top:6px;width:6px;height:70%;background:linear-gradient(var(--bg-purple),#4d2bff);border-radius:3px;box-shadow:0 0 0 1px rgba(255,255,255,0.15),0 0 10px -2px rgba(120,75,255,.9);} 

.table-wrap {position:relative;overflow:auto;border-radius:16px;border:1px solid rgba(140,90,255,0.3);background:#120d19;box-shadow:0 4px 18px -4px rgba(0,0,0,.55) inset;}
.table {width:100%;border-collapse:separate;border-spacing:0;font-size:1.25rem;min-width:760px;}
.table thead th {background:linear-gradient(135deg,rgba(140,75,255,0.4),rgba(80,40,150,0.45));color:#fff;font-weight:600;text-transform:uppercase;font-size:1.05rem;letter-spacing:.5px;position:sticky;top:0;backdrop-filter:blur(3px);padding:12px 14px;border-bottom:1px solid rgba(255,255,255,0.08);} 
.table tbody td {padding:12px 14px;border-bottom:1px solid rgba(255,255,255,0.06);color:var(--text-gainsboro);} 
.table tbody tr:last-child td {border-bottom:none;}
.table tbody tr {transition:.25s background, .25s transform;}
.table tbody tr:hover {background:rgba(140,75,255,0.09);} 

h1.section-title {position:relative;display:inline-block;padding:0 6px 12px;margin:0 0 6px;}
h1.section-title:after {content:"";position:absolute;left:0;bottom:0;width:100%;height:4px;background:linear-gradient(90deg,var(--bg-purple),transparent);border-radius:3px;}

.table-wrap::-webkit-scrollbar {height:10px;}
.table-wrap::-webkit-scrollbar-track {background:#0f0b15;}
.table-wrap::-webkit-scrollbar-thumb {background:var(--bg-purple);border-radius:20px;}

@media (max-width:900px){
  .panel {padding:22px 18px;}
  .table {font-size:1.15rem;}
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
  <div class="status-bar" style="padding:10px 18px;border-radius:14px;font-size:1.2rem;font-weight:500;display:flex;flex-wrap:wrap;gap:12px;align-items:center;background:linear-gradient(100deg,rgba(140,75,255,0.35),rgba(40,30,60,0.65));border:1px solid rgba(140,75,255,0.45);box-shadow:0 8px 26px -10px rgba(140,75,255,0.55) inset,0 2px 8px -2px rgba(0,0,0,.7);margin-bottom:6px;">
    Logged in as <strong><?= h($user['username']) ?></strong> (role: <strong><?= h($user['role']) ?></strong>) | Participants: <?= count($rows) ?>
  </div>
  <h1 class="h2 section-title">Event <span class="span">Organizer</span> Panel</h1>
  <div class="panel revealed" data-reveal="bottom">
    <h2 class="h3 title">Participants (PII Hidden)</h2>
    <div class="table-wrap">
    <table class="table">
      <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Team</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><a href="dashboard-user-view.php?id=<?= (int)$r['id'] ?>" style="color:var(--text-purple);text-decoration:none;"><?= h($r['username']) ?></a></td>
            <td><?= h($r['email']) ?></td>
            <td><?= h($r['full_name'] ?? '—') ?></td>
            <td><?= h($r['team_name'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
  </table>
  </div>
  </div>
</main>
<script src="../assets/js/script.js"></script>
<script src="../assets/js/bg.js"></script>
</body>
</html>
