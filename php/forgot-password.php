<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pdo = db();
verify_csrf();
$step = 'request';
$message = '';
$errors = [];

if($_SERVER['REQUEST_METHOD']==='POST') {
  $email = trim($_POST['email']??'');
  $nic = trim($_POST['nic']??'');
  $code = strtoupper(trim($_POST['code']??''));
  $newpw = $_POST['new_password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  // Second step submission: user + code already validated & consumed earlier
  if(isset($_SESSION['pw_reset_user']) && isset($_SESSION['pw_reset_code_consumed']) && $newpw) {
    $userId = (int)$_SESSION['pw_reset_user'];
    if(strlen($newpw)<8) $errors[]='Password too short.';
    if($newpw!==$confirm) $errors[]='Passwords do not match.';
    if(!$errors) {
      $stmt=$pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
      $stmt->execute([password_hash($newpw,PASSWORD_DEFAULT), $userId]);
      unset($_SESSION['pw_reset_user'], $_SESSION['pw_reset_code_consumed']);
      $message='Password updated. You may now log in.';
      $step='request';
    } else {
      $step='reset';
    }
  } else {
    // First step: verify & consume code, then show reset form
    if(!$email||!$nic||!$code) { $errors[]='All fields required.'; }
    else {
      $userId = consume_recovery_code($email,$nic,$code); // consumes (marks used)
      if(!$userId) { $errors[]='Invalid details or code already used.'; }
      else {
        // Move to password reset stage (do not require code again)
        $_SESSION['pw_reset_user']=$userId;
        $_SESSION['pw_reset_code_consumed']=true;
        $step='reset';
      }
    }
  }
}
if($step==='reset' && empty($_SESSION['pw_reset_user'])) { $step='request'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password | Nebula Esports 2025</title>
<link rel="shortcut icon" href="../assets/images/nebula-esports.png" type="image/png">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body id="top">
<header class="header active" data-header>
  <div class="container">
    <a href="#" class="logo"><img src="../assets/images/nebula-esports.png" style="width:80px;height:auto;" alt="logo"></a>
  </div>
</header>
<section class="section auth" aria-labelledby="fp-title">
  <div class="container">
    <h1 class="h2 section-title" id="fp-title">Forgot <span class="span">Password</span></h1>
    <?php if($message): ?><div class="auth-success" style="margin:0 0 20px;padding:14px 18px;border:1px solid #286a3d;background:rgba(40,90,70,.25);border-radius:8px;"><?=h($message)?></div><?php endif; ?>
    <?php if($errors): ?><div class="auth-errors" style="margin:0 0 20px;padding:14px 18px;border:1px solid #7a1d25;background:rgba(120,20,35,.25);border-radius:8px;"><ul style="margin:0;padding-left:20px;"><?php foreach($errors as $e):?><li><?=h($e)?></li><?php endforeach;?></ul></div><?php endif; ?>
    <?php if($step==='request'): ?>
      <form method="post" class="auth-form" autocomplete="off">
        <?=csrf_field()?>
        <div class="auth-row"><label>Email<br><input type="email" name="email" required></label></div>
        <div class="auth-row"><label>NIC Number<br><input type="text" name="nic" required></label></div>
        <div class="auth-row"><label>Recovery Code<br><input type="text" name="code" placeholder="ABC-123-XYZ-456" required></label></div>
        <p style="font-size:.8rem;color:#ccc;line-height:1.4;margin:8px 0 14px;">Enter one unused recovery code from your saved list. It will be consumed and then you'll set a new password.</p>
        <button class="btn" data-btn type="submit">Verify Code</button>
      </form>
    <?php elseif($step==='reset'): ?>
      <form method="post" class="auth-form" autocomplete="off">
        <?=csrf_field()?>
        <input type="hidden" name="email" value="<?=h($_POST['email']??'')?>">
        <input type="hidden" name="nic" value="<?=h($_POST['nic']??'')?>">
        <input type="hidden" name="code" value="<?=h($_POST['code']??'')?>">
        <div class="auth-row"><label>New Password<br><input type="password" name="new_password" required></label></div>
        <div class="auth-row"><label>Confirm Password<br><input type="password" name="confirm_password" required></label></div>
        <button class="btn" data-btn type="submit">Reset Password</button>
      </form>
    <?php endif; ?>
    <p style="margin-top:28px;font-size:.75rem;color:#aaa;max-width:640px;">Lost your codes? Log in (if still possible) and regenerate a new set from your profile. Keep them offline: print, write down, or store in an encrypted password manager. Generating new codes invalidates all unused previous codes.</p>
  </div>
</section>
<footer class="footer"><div class="footer-bottom"><div class="container"><p class="copyright">&copy; 2025 Nebula Esports</p></div></div></footer>
</body>
</html>
