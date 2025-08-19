<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pdo = db();
$actor = require_role('organizer'); // organizer or above

$id = (int)($_GET['id'] ?? 0);
if($id < 1) redirect('dashboard-organizer.php');

// Fetch limited non-PII view: exclude NIC, DOB, location, but include mobile for organizer visibility.
$stmt = $pdo->prepare('SELECT u.id,u.username,u.email,u.role,u.created_at,p.full_name,p.team_name,p.team_captain,p.players_count,p.game_titles,p.mobile,p.team_logo_path FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if(!$user) redirect('dashboard-organizer.php');
// If actor is exactly organizer, block viewing admin or head_admin users directly
if($actor['role']==='organizer' && in_array($user['role'], ['admin','head_admin'])) {
  redirect('dashboard-organizer.php');
}

// game titles decode
$gameTitles = [];
if(!empty($user['game_titles'])){
  $decoded = json_decode($user['game_titles'], true);
  if(is_array($decoded)) $gameTitles = $decoded;
}
$gameOptions = [ 'pubg_mobile'=>'PUBG Mobile','free_fire'=>'Free Fire','cod_pc'=>'Call of Duty (PC)','pubg_pc'=>'PUBG (PC)' ];

// Team members (non-PII: exclude NIC)
$memStmt = $pdo->prepare('SELECT idx,name,email,phone FROM members WHERE user_id=? ORDER BY idx ASC');
$memStmt->execute([$id]);
$members = $memStmt->fetchAll();

?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>View User #<?= (int)$user['id'] ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;500;600;700&family=Work+Sans:wght@600&display=swap" rel="stylesheet">
<link rel="shortcut icon" href="../assets/images/nebula-esports.png" type="image/png">
<style>
.page{margin-top:140px;display:flex;flex-direction:column;gap:34px;}
.panel{position:relative;overflow:hidden;background:linear-gradient(145deg,rgba(28,20,43,.9),rgba(18,12,28,.88));padding:28px 28px 34px;border:1px solid rgba(140,90,255,.4);border-radius:22px;box-shadow:0 8px 30px -10px rgba(0,0,0,.7),0 0 0 1px rgba(255,255,255,0.05) inset;backdrop-filter:blur(4px);} 
.panel:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 76% 16%,rgba(140,75,255,.25),transparent 65%),radial-gradient(circle at 20% 86%,rgba(60,160,255,.2),transparent 70%);pointer-events:none;mix-blend-mode:screen;}
.panel h2.h3{margin:0 0 18px;position:relative;padding-left:14px;font-weight:600;letter-spacing:.6px;}
.panel h2.h3:before{content:"";position:absolute;left:0;top:6px;width:6px;height:70%;background:linear-gradient(var(--bg-purple),#4d2bff);border-radius:3px;box-shadow:0 0 0 1px rgba(255,255,255,0.15),0 0 10px -2px rgba(120,75,255,.9);} 
.grid{display:grid;gap:18px;}
.grid.cols-3{grid-template-columns:repeat(auto-fit,minmax(220px,1fr));}
.field label{display:block;font-size:1.05rem;margin:0 0 4px;font-weight:600;letter-spacing:.5px;color:#fff;opacity:.8;text-transform:uppercase;}
.field .value{font-size:1.4rem;background:#120d19;border:1px solid rgba(140,90,255,0.35);padding:12px 14px;border-radius:12px;min-height:46px;display:flex;align-items:center;}
.badge-role{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:40px;font-size:1.15rem;background:linear-gradient(120deg,rgba(140,75,255,.3),rgba(60,160,255,.25));border:1px solid rgba(140,75,255,.45);} 
h1.section-title{position:relative;margin:0 0 6px;display:inline-block;padding:0 4px 12px;}
h1.section-title:after{content:"";position:absolute;left:0;bottom:0;width:100%;height:4px;background:linear-gradient(90deg,var(--bg-purple),transparent);border-radius:3px;}
.tag-list{display:flex;flex-wrap:wrap;gap:10px;margin-top:6px;}
.tag{background:#141018;border:1px solid var(--border-space-cadet,#262a37);padding:6px 12px;border-radius:20px;font-size:1.2rem;}
@media (max-width:900px){.panel{padding:24px 20px 28px;}.grid.cols-3{grid-template-columns:repeat(auto-fit,minmax(160px,1fr));}.field .value{font-size:1.3rem;}}
</style></head><body id="top">
<?php $back = ($actor['role']==='organizer') ? 'dashboard-organizer.php' : (($actor['role']==='admin') ? 'dashboard-admin.php' : 'dashboard-head-admin.php'); ?>
<header class="header active" data-header><div class="container">
  <a href="<?= h($back) ?>" class="logo"><img src="../assets/images/nebula-esports.png" style="width:80px;" alt="logo"></a>
  <a href="<?= h($back) ?>" class="navbar-link"> <button class ="btn">Back</button> </a>
</div></header>
<main class="page container">
  <h1 class="h2 section-title">View <span class="span">User</span> #<?= (int)$user['id'] ?></h1>
  <div class="panel">
    <h2 class="h3">Public / Allowed Fields</h2>
    <div class="grid cols-3">
      <div class="field"><label>Username</label><div class="value"><?= h($user['username']) ?></div></div>
      <div class="field"><label>Email</label><div class="value"><?= h($user['email']) ?></div></div>
      <div class="field"><label>Role</label><div class="value"><span class="badge-role"><?= h($user['role']) ?></span></div></div>
      <div class="field"><label>Full Name</label><div class="value"><?= h($user['full_name'] ?? '—') ?></div></div>
      <div class="field"><label>Team Name</label><div class="value"><?= h($user['team_name'] ?? '—') ?></div></div>
      <div class="field"><label>Team Captain</label><div class="value"><?= h($user['team_captain'] ?? '—') ?></div></div>
  <div class="field"><label>Players</label><div class="value"><?= (int)($user['players_count'] ?? 0) ?></div></div>
  <div class="field"><label>Phone</label><div class="value"><?= h($user['mobile'] ?? '—') ?></div></div>
      <div class="field" style="grid-column:1 / -1;">
        <label>Team Logo</label>
        <?php if(!empty($user['team_logo_path'])): $logoPath = $user['team_logo_path']; $exists = is_file($_SERVER['DOCUMENT_ROOT'] . $logoPath); ?>
          <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
            <img src="<?= h($logoPath) ?>" alt="Team Logo" style="max-height:130px;max-width:260px;border:1px solid rgba(140,90,255,0.4);border-radius:14px;padding:6px;background:#120d19;box-shadow:0 4px 14px -6px rgba(0,0,0,.6);object-fit:contain;">
            <a href="<?= h($logoPath) ?>" download class="btn" style="text-decoration:none;">Download Logo</a>
            <?php if(!$exists): ?><span style="color:#ff8080;font-size:1.1rem;">File missing on server</span><?php endif; ?>
          </div>
        <?php else: ?>
          <div style="padding:12px 14px;border:1px dashed rgba(140,90,255,0.35);border-radius:12px;background:#120d19;font-size:1.2rem;opacity:.75;">No team logo uploaded.</div>
        <?php endif; ?>
      </div>
      <div class="field" style="grid-column:1 / -1;">
        <label>Game Titles</label>
        <div class="tag-list">
          <?php if($gameTitles): foreach($gameTitles as $gt): if(isset($gameOptions[$gt])): ?><span class="tag"><?= h($gameOptions[$gt]) ?></span><?php endif; endforeach; else: ?><span class="tag" style="opacity:.6;">None</span><?php endif; ?>
        </div>
      </div>
    </div>
  <p style="margin-top:24px;font-size:1.1rem;opacity:.7;">Note: Sensitive personal identifiers (NIC, DOB, precise location, etc.) remain hidden. Phone number is shown for coordination only.</p>
  </div>
  <div class="panel">
    <h2 class="h3">Team Members</h2>
    <?php if($members): ?>
      <div class="table-wrap" style="position:relative;overflow:auto;border-radius:14px;border:1px solid rgba(140,90,255,0.3);background:#120d19;box-shadow:0 4px 18px -4px rgba(0,0,0,.55) inset;">
        <table class="table" style="width:100%;border-collapse:separate;border-spacing:0;font-size:1.2rem;min-width:520px;">
          <thead><tr><th style="text-align:left;padding:10px 12px;background:linear-gradient(135deg,rgba(140,75,255,0.4),rgba(80,40,150,0.45));">#</th><th style="text-align:left;padding:10px 12px;background:linear-gradient(135deg,rgba(140,75,255,0.4),rgba(80,40,150,0.45));">Name</th><th style="text-align:left;padding:10px 12px;background:linear-gradient(135deg,rgba(140,75,255,0.4),rgba(80,40,150,0.45));">Email</th><th style="text-align:left;padding:10px 12px;background:linear-gradient(135deg,rgba(140,75,255,0.4),rgba(80,40,150,0.45));">Phone</th></tr></thead>
          <tbody>
            <?php foreach($members as $m): ?>
              <tr style="transition:.25s background;">
                <td style="padding:10px 12px;border-bottom:1px solid rgba(255,255,255,0.06);color:var(--text-gainsboro);"><?= (int)$m['idx'] ?></td>
                <td style="padding:10px 12px;border-bottom:1px solid rgba(255,255,255,0.06);color:var(--text-gainsboro);"><?= h($m['name'] ?: '—') ?></td>
                <td style="padding:10px 12px;border-bottom:1px solid rgba(255,255,255,0.06);color:var(--text-gainsboro);"><?= h($m['email'] ?: '—') ?></td>
                <td style="padding:10px 12px;border-bottom:1px solid rgba(255,255,255,0.06);color:var(--text-gainsboro);"><?= h($m['phone'] ?: '—') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p style="font-size:1.25rem;opacity:.7;">No team members recorded.</p>
    <?php endif; ?>
  </div>
</main>
<script src="../assets/js/script.js"></script>
<script src="../assets/js/bg.js"></script>
</body></html>
