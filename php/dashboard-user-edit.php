<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pdo = db();
// Allow both admin and head_admin; only head_admin can change roles
$actor = require_role('admin');
$isHead = $actor['role'] === 'head_admin';
$isAdminOnly = $actor['role'] === 'admin';
verify_csrf();

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) redirect($isHead ? 'dashboard-head-admin.php' : 'dashboard-admin.php');

$stmt = $pdo->prepare('SELECT u.id,u.username,u.email,u.role,u.created_at,p.full_name,p.dob,p.location,p.university,p.nic,p.mobile,p.team_name,p.team_captain,p.players_count,p.game_titles,p.team_logo_path FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if(!$user) redirect($isHead ? 'dashboard-head-admin.php' : 'dashboard-admin.php');
// Admins (non head) may only edit users & organizers
if($isAdminOnly && in_array($user['role'], ['admin','head_admin'])) {
  redirect('dashboard-admin.php');
}
$user['game_titles'] = $user['game_titles'] ? json_decode($user['game_titles'], true) : [];

$membersStmt = $pdo->prepare('SELECT id,idx,name,nic,email,phone FROM members WHERE user_id=? ORDER BY idx ASC');
$membersStmt->execute([$id]);
$members = $membersStmt->fetchAll();

$errors = [];$messages=[];

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'save_user') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = $_POST['role'] ?? $user['role'];
    if($isAdminOnly) { // lock role for normal admins
      $role = $user['role'];
    }
    $full_name = trim($_POST['full_name'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $university = trim($_POST['university'] ?? '');
    $nic = trim($_POST['nic'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $team_name = trim($_POST['team_name'] ?? '');
    $team_captain = trim($_POST['team_captain'] ?? '');
    $players_count = max(1,min(5,(int)($_POST['players_count'] ?? 1)));
    $game_titles = $_POST['game_titles'] ?? [];
    if(!is_array($game_titles)) $game_titles = [];

    if(!$username||!$email) $errors[]='Username & email required';
    if(!$errors){
      $pdo->beginTransaction();
      try {
  $pdo->prepare('UPDATE users SET username=?, email=?, role=? WHERE id=?')->execute([$username,$email,$role,$id]);
        $exists = $pdo->prepare('SELECT 1 FROM profiles WHERE user_id=?');
        $exists->execute([$id]);
        $profileSql = $exists->fetch() ? 'UPDATE profiles SET full_name=?,dob=?,location=?,university=?,nic=?,mobile=?,team_name=?,team_captain=?,players_count=?,game_titles=?,team_logo_path=? WHERE user_id=?' : 'INSERT INTO profiles(full_name,dob,location,university,nic,mobile,team_name,team_captain,players_count,game_titles,team_logo_path,user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
        $params = [$full_name,$dob,$location,$university,$nic,$mobile,$team_name,$team_captain,$players_count,json_encode($game_titles),$user['team_logo_path'],$id];
        if($exists->fetch()){
          $pdo->prepare($profileSql)->execute($params);
        } else {
          $pdo->prepare($profileSql)->execute($params);
        }
        // Members replace
        $pdo->prepare('DELETE FROM members WHERE user_id=?')->execute([$id]);
        $memIns = $pdo->prepare('INSERT INTO members (user_id,idx,name,nic,email,phone) VALUES (?,?,?,?,?,?)');
        for($i=1;$i<=$players_count;$i++){
          $memIns->execute([$id,$i,trim($_POST['member'.$i.'_name']??''),trim($_POST['member'.$i.'_nic']??''),trim($_POST['member'.$i.'_email']??''),trim($_POST['member'.$i.'_phone']??'')]);
        }
        $pdo->commit();
        $messages[]='Saved';
      } catch(Throwable $e){
        $pdo->rollBack();
        $errors[]='Save failed';
      }
    }
  } elseif ($action === 'reset_password') {
    $new = $_POST['new_password'] ?? '';
    if(strlen($new) < 8){ $errors[]='Password too short'; }
    else {
      $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),$id]);
      $messages[]='Password reset';
    }
  }
  // reload data after save
  $stmt->execute([$id]);
  $user = $stmt->fetch();
  $user['game_titles'] = $user['game_titles'] ? json_decode($user['game_titles'], true) : [];
  $membersStmt->execute([$id]);
  $members = $membersStmt->fetchAll();
}

$gameOptions = [ 'pubg_mobile'=>'PUBG Mobile','free_fire'=>'Free Fire','cod_pc'=>'Call of Duty (PC)','pubg_pc'=>'PUBG (PC)' ];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Edit User #<?= (int)$user['id'] ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;500;600;700&family=Work+Sans:wght@600&display=swap" rel="stylesheet">
<link rel="shortcut icon" href="../assets/images/nebula-esports.png" type="image/png">
<style>
.page{margin-top:140px;display:flex;flex-direction:column;gap:34px;}
.grid{display:grid;gap:18px;}
.grid.cols-3{grid-template-columns:repeat(auto-fit,minmax(220px,1fr));}

/* Panels */
.panel{position:relative;overflow:hidden;background:linear-gradient(145deg,rgba(28,20,43,.9),rgba(18,12,28,.88));padding:28px 28px 34px;border:1px solid rgba(140,90,255,.4);border-radius:22px;box-shadow:0 8px 30px -10px rgba(0,0,0,.7),0 0 0 1px rgba(255,255,255,0.05) inset;backdrop-filter:blur(4px);}
.panel:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 78% 18%,rgba(140,75,255,.25),transparent 65%),radial-gradient(circle at 18% 85%,rgba(60,160,255,.2),transparent 70%);pointer-events:none;mix-blend-mode:screen;}
.panel h2.h3.title, .panel h2.h3{margin:0 0 18px;position:relative;padding-left:14px;font-weight:600;letter-spacing:.6px;}
.panel h2.h3.title:before, .panel h2.h3:before{content:"";position:absolute;left:0;top:6px;width:6px;height:70%;background:linear-gradient(var(--bg-purple),#4d2bff);border-radius:3px;box-shadow:0 0 0 1px rgba(255,255,255,0.15),0 0 10px -2px rgba(120,75,255,.9);}

/* Fields */
.field label{display:block;font-size:1.1rem;margin:0 0 6px;font-weight:600;letter-spacing:.6px;text-transform:uppercase;color:#fff;opacity:.85;}
.field input,.field select{width:100%;padding:12px 14px;border:1px solid rgba(140,90,255,0.35);background:#120d19;color:var(--text-white);font-size:1.35rem;border-radius:12px;transition:.25s border,.25s box-shadow,.25s background;}
.field input:focus,.field select:focus{outline:none;border-color:var(--text-purple);box-shadow:0 0 0 2px rgba(140,75,255,.35),0 4px 14px -4px rgba(140,75,255,.4);} 

/* Members */
.member-block{border:1px solid rgba(140,90,255,.35);padding:16px 16px 18px;border-radius:14px;background:#150f21;box-shadow:0 4px 14px -6px rgba(0,0,0,.6) inset;position:relative;}
.member-block strong{font-size:1.3rem;letter-spacing:.5px;}
.member-block:before{content:"";position:absolute;inset:0;border-radius:inherit;background:linear-gradient(140deg,rgba(140,75,255,0.15),rgba(60,160,255,0.1));mix-blend-mode:overlay;pointer-events:none;opacity:.65;}

/* Messages */
.messages{display:grid;gap:10px;}
.messages .ok,.messages .err{font-size:1.3rem;padding:12px 16px;border-radius:12px;line-height:1.4;font-weight:500;position:relative;}
.messages .ok{background:linear-gradient(135deg,#14331c,#0e2415);border:1px solid #215a35;color:#d4f7e2;}
.messages .ok:before{content:"✔";margin-right:6px;color:#35c46a;}
.messages .err{background:linear-gradient(135deg,#401a1f,#2a1215);border:1px solid #70323a;color:#ffd6d9;}
.messages .err:before{content:"⚠";margin-right:6px;color:#ff6b6b;}

/* Buttons */
button.btn{clip-path:var(--clip-path-3);}
button.btn:hover{filter:brightness(1.12);} 

/* Page title */
h1.section-title{position:relative;margin:0 0 6px;display:inline-block;padding:0 4px 12px;}
h1.section-title:after{content:"";position:absolute;left:0;bottom:0;width:100%;height:4px;background:linear-gradient(90deg,var(--bg-purple),transparent);border-radius:3px;}

@media (max-width:900px){
  .grid.cols-3{grid-template-columns:repeat(auto-fit,minmax(160px,1fr));}
  .panel{padding:24px 20px 28px;}
  .field input,.field select{font-size:1.25rem;padding:10px 12px;}
}
@media (max-width:600px){
  .member-block{padding:14px 14px 16px;}
  .grid{gap:14px;}
}
</style></head><body id="top">
<?php $back = $isHead ? 'dashboard-head-admin.php' : 'dashboard-admin.php'; ?>
<header class="header active" data-header><div class="container">
  <a href="<?= h($back) ?>" class="logo"><img src="../assets/images/nebula-esports.png" style="width:80px;" alt="logo"></a>
  <a href="<?= h($back) ?>" class="navbar-link"> <button class = "btn">Back</button> </a>
</div></header>
<main class="page container">
  <h1 class="h2 section-title">Edit <span class="span">User</span> #<?= (int)$user['id'] ?></h1>
  <div class="messages">
    <?php foreach($messages as $m): ?><div class="ok"><?= h($m) ?></div><?php endforeach; ?>
    <?php foreach($errors as $e): ?><div class="err"><?= h($e) ?></div><?php endforeach; ?>
  </div>
  <form method="post" action="" class="panel" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_user">
    <div class="grid cols-3">
      <div class="field"><label>Username</label><input name="username" value="<?= h($user['username']) ?>" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" value="<?= h($user['email']) ?>" required></div>
      <?php if($isHead): ?>
        <div class="field"><label>Role</label><select name="role"><?php foreach(['user','organizer','admin','head_admin'] as $r): ?><option value="<?= $r ?>" <?= $r===$user['role']?'selected':'' ?>><?= $r ?></option><?php endforeach; ?></select></div>
      <?php else: ?>
        <div class="field"><label>Role</label>
          <div style="width:100%;padding:12px 14px;border:1px solid rgba(140,90,255,0.35);background:#120d19;color:var(--text-white);font-size:1.35rem;border-radius:12px;opacity:.85;">
            <?= h($user['role']) ?> (locked)
          </div>
          <input type="hidden" name="role" value="<?= h($user['role']) ?>">
        </div>
      <?php endif; ?>
      <div class="field"><label>Full Name</label><input name="full_name" value="<?= h($user['full_name'] ?? '') ?>"></div>
      <div class="field"><label>DOB</label><input type="date" name="dob" value="<?= h($user['dob'] ?? '') ?>"></div>
      <div class="field"><label>Location</label><input name="location" value="<?= h($user['location'] ?? '') ?>"></div>
      <div class="field"><label>University</label><input name="university" value="<?= h($user['university'] ?? '') ?>"></div>
      <div class="field"><label>NIC</label><input name="nic" value="<?= h($user['nic'] ?? '') ?>"></div>
      <div class="field"><label>Mobile</label><input name="mobile" value="<?= h($user['mobile'] ?? '') ?>"></div>
      <div class="field"><label>Team Name</label><input name="team_name" value="<?= h($user['team_name'] ?? '') ?>"></div>
      <div class="field"><label>Team Captain</label><input name="team_captain" value="<?= h($user['team_captain'] ?? '') ?>"></div>
      <div class="field"><label>Players</label><select name="players_count"><?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>" <?= (int)($user['players_count']??1)===$i?'selected':'' ?>><?= $i ?></option><?php endfor; ?></select></div>
      <div class="field" style="grid-column:1 / -1;">
        <label>Team Logo</label>
        <?php if(!empty($user['team_logo_path'])): $logoPath = $user['team_logo_path']; $exists = is_file($_SERVER['DOCUMENT_ROOT'] . $logoPath); ?>
          <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
            <img src="<?= h($logoPath) ?>" alt="Team Logo" style="max-height:120px;max-width:240px;border:1px solid rgba(140,90,255,0.4);border-radius:14px;padding:6px;background:#120d19;box-shadow:0 4px 14px -6px rgba(0,0,0,.6);object-fit:contain;">
            <a href="<?= h($logoPath) ?>" download class="btn" style="text-decoration:none;">Download Logo</a>
            <?php if(!$exists): ?><span style="color:#ff8080;font-size:1.1rem;">File missing on server</span><?php endif; ?>
          </div>
        <?php else: ?>
          <div style="padding:12px 14px;border:1px dashed rgba(140,90,255,0.35);border-radius:12px;background:#120d19;font-size:1.2rem;opacity:.75;">No team logo uploaded.</div>
        <?php endif; ?>
      </div>
      <div class="field" style="grid-column:1 / -1;">
        <label>Game Titles</label>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
          <?php foreach($gameOptions as $k=>$label): $sel=in_array($k,$user['game_titles'])?'selected':''; ?>
            <label style="background:#141018;padding:6px 12px;border:1px solid var(--border-space-cadet);border-radius:20px;cursor:pointer;">
              <input type="checkbox" name="game_titles[]" value="<?= h($k) ?>" <?= $sel?'checked':'' ?> style="margin-right:4px;"> <?= h($label) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <h2 class="h3 title" style="margin-top:20px;">Members</h2>
    <div class="grid" style="gap:12px;">
      <?php $players = (int)($user['players_count'] ?? 1); for($i=1;$i<=$players;$i++): $mem=null; foreach($members as $m){ if((int)$m['idx']===$i){$mem=$m;break;} } ?>
        <div class="member-block">
          <strong style="display:block;margin-bottom:6px;">Member <?= $i ?></strong>
          <div class="grid cols-3" style="gap:8px;">
            <div class="field"><label>Name</label><input name="member<?= $i ?>_name" value="<?= h($mem['name'] ?? '') ?>"></div>
            <div class="field"><label>NIC</label><input name="member<?= $i ?>_nic" value="<?= h($mem['nic'] ?? '') ?>"></div>
            <div class="field"><label>Email</label><input type="email" name="member<?= $i ?>_email" value="<?= h($mem['email'] ?? '') ?>"></div>
            <div class="field" style="grid-column:1 / -1;"><label>Phone</label><input name="member<?= $i ?>_phone" value="<?= h($mem['phone'] ?? '') ?>"></div>
          </div>
        </div>
      <?php endfor; ?>
    </div>
    <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
      <button class="btn">Save Changes</button>
    </div>
  </form>
  <form method="post" action="" class="panel" style="max-width:420px;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="reset_password">
    <h2 class="h3 title">Reset Password</h2>
    <input type="password" name="new_password" class="input-field" placeholder="New Password (min 8)" required style="margin:10px 0;">
    <button class="btn">Set New Password</button>
  </form>
</main>
<script src="../assets/js/script.js"></script>
<script src="../assets/js/bg.js"></script>
</body></html>
