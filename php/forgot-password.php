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
    } else { $step='reset'; }
  } else {
    if(!$email||!$nic||!$code) { $errors[]='All fields required.'; }
    else {
      $userId = consume_recovery_code($email,$nic,$code);
      if(!$userId) { $errors[]='Invalid details or code already used.'; }
      else {
        $_SESSION['pw_reset_user']=$userId;
        $_SESSION['pw_reset_code_consumed']=true;
        $step='reset';
      }
    }
  }
}
if($step==='reset' && empty($_SESSION['pw_reset_user'])) { $step='request'; }
// If user already passed code verification but hasn't finished reset (GET reload)
if($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_SESSION['pw_reset_user']) && isset($_SESSION['pw_reset_code_consumed'])) {
  $step = 'reset';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- 
    - primary meta tags
  -->
  <title>Forgot Password | Nebula Esports 2025</title>
  <meta name="title" content="Nebula Esports 2025 - Password Recovery">
  <meta name="description" content="Recover access to your Nebula Esports account using one of your saved one-time recovery codes.">

  <!-- 
    - favicon
  -->
  <link rel="shortcut icon" href="../assets/images/nebula-esports.png" type="image/png">

  <!-- 
    - google font link
  -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;500;600;700&family=Work+Sans:wght@600&display=swap" rel="stylesheet">

  <!-- 
    - custom css link
  -->
  <link rel="stylesheet" href="../assets/css/style.css">

  <!-- Inline auth error styles (mirrored from signup-login page) -->
  <style>
  .auth-form .input-field { width:100%; }
  /* Ensure full-width inputs on this page by stacking label content */
  .auth-form .auth-row { flex-direction: column; align-items: stretch; }
  .auth-form .auth-row label { width:100%; display:block; }
  .auth-form .auth-row br { display:none; }
    .auth-errors {
      border: 2px solid #ff4d4d;
      background: rgba(255, 60, 60, 0.12);
      padding: 14px 18px;
      border-radius: 6px;
      margin: 0 0 22px;
      box-shadow: 0 4px 12px -2px rgba(255,0,0,0.15);
      position: relative;
      overflow: hidden;
      animation: fadePanel .35s ease;
    }
    .auth-errors:before {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(110deg, rgba(255,0,70,0.20), rgba(255,150,0,0.08));
      pointer-events: none;
      mix-blend-mode: overlay;
    }
    .auth-errors-title {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .5px;
      font-size: 14px;
      color: #fff;
      margin: 0 0 8px;
    }
    .auth-errors-title:before { content: "⚠"; font-size: 16px; line-height: 1; }
    .auth-errors ul { margin: 0; padding: 0 0 0 18px; list-style: disc; display: grid; gap: 4px; }
    .auth-errors li { color: #ffd9d9; font-size: 13px; line-height: 1.35; }
    .auth-errors li::marker { color: #ff8686; }
    @media (min-width: 600px) {
      .auth-errors { padding: 16px 22px; }
      .auth-errors-title { font-size: 15px; }
      .auth-errors li { font-size: 13.5px; }
    }
  </style>

  <!-- 
    - preload images
  -->
  <link rel="preload" as="image" href="../assets/images/hero-banner.png">
  <link rel="preload" as="image" href="../assets/images/hero-banner-bg.png">
</head>

<body id="top">

  <!-- 
    - #HEADER
  -->

  <header class="header active" data-header>
    <div class="container">

      <a href="#" class="logo">
        <img src="../assets/images/nebula-esports.png" style="width:80px; height:auto;" alt="Nebula Esports logo">
      </a>

      <nav class="navbar" data-navbar>
        <ul class="navbar-list">
          <li class="navbar-item"><a href="../index.php#home" class="navbar-link" data-nav-link>home</a></li>
          <li class="navbar-item"><a href="../index.php#tournament" class="navbar-link" data-nav-link>tournament</a></li>
          <li class="navbar-item"><a href="../index.php#news" class="navbar-link" data-nav-link>news</a></li>
          <li class="navbar-item"><a href="./contact.php" class="navbar-link" data-nav-link>contact</a></li>
          <li class="navbar-item"><a href="./rules.php" class="navbar-link" data-nav-link>Rules & Guidelines</a></li>
        </ul>
      </nav>

      <?php if(isset($_SESSION['user_id'])): ?>
        <a href="./user.php" class="btn" data-btn>MY PROFILE</a>
      <?php else: ?>
        <a href="./signup-login.php" class="btn" data-btn>LOGIN / SIGN UP</a>
      <?php endif; ?>
      
      <button class="nav-toggle-btn" aria-label="toggle menu" data-nav-toggler>
        <span class="line line-1"></span>
        <span class="line line-2"></span>
        <span class="line line-3"></span>
      </button>

    </div>
  </header>

  <!-- 
    - #FORGOT PASSWORD
  -->

  <section class="section auth" aria-labelledby="fp-title">
    <div class="container">
      <p class="section-subtitle" data-reveal="bottom">&nbsp;</p>
      <p class="section-subtitle" data-reveal="bottom">&nbsp;</p>
      <p class="section-subtitle" data-reveal="bottom">Account recovery</p>
      <h1 class="h2 section-title" id="fp-title" data-reveal="bottom">FORGOT <span class="span">PASSWORD</span></h1>

      <div class="auth-card" data-reveal="bottom">
        <div class="auth-panels">
          <?php if($message): ?>
            <div class="auth-errors" style="border-color:#2ecc40;background:rgba(46,204,64,.12);" role="status" aria-live="polite">
              <div class="auth-errors-title" style="color:#2ecc40;">Success</div>
              <ul><li><?=h($message)?></li></ul>
            </div>
          <?php endif; ?>
          <?php if($errors): ?>
            <div class="auth-errors revealed" role="alert" aria-live="assertive" data-reveal="bottom">
              <div class="auth-errors-title">Please fix the following</div>
              <ul><?php foreach($errors as $e):?><li><?=h($e)?></li><?php endforeach;?></ul>
            </div>
          <?php endif; ?>

          <?php if($step==='request'): ?>
            <form method="post" class="auth-form" autocomplete="off" novalidate>
              <?=csrf_field()?>
              <div class="auth-row"><label>Email<br><input class="input-field" type="email" name="email" required autocomplete="email"></label></div>
              <div class="auth-row"><label>NIC Number<br><input class="input-field" type="text" name="nic" required></label></div>
              <div class="auth-row"><label>Recovery Code<br><input class="input-field" type="text" name="code" placeholder="ABC-123-XYZ-456" required></label></div>
              <p class="help-note footer-text">Enter one unused recovery code from your saved list. It will be consumed and then you'll set a new password.</p>
              <button class="btn" data-btn type="submit">Verify Code</button>
            </form>
          <?php elseif($step==='reset'): ?>
            <form method="post" class="auth-form" autocomplete="off" novalidate>
              <?=csrf_field()?>
              <input type="hidden" name="email" value="<?=h($_POST['email']??'')?>">
              <input type="hidden" name="nic" value="<?=h($_POST['nic']??'')?>">
              <input type="hidden" name="code" value="<?=h($_POST['code']??'')?>">
              <div class="auth-row"><label>New Password<br><input class="input-field" type="password" name="new_password" required autocomplete="new-password"></label></div>
              <div class="auth-row"><label>Confirm Password<br><input class="input-field" type="password" name="confirm_password" required autocomplete="new-password"></label></div>
              <button class="btn" data-btn type="submit">Reset Password</button>
            </form>
          <?php endif; ?>
          <?php if($step==='request'): ?>
            <br>
            <p class="help-note footer-text" style="margin-top:12px;">Need your codes? Log in (if possible) and regenerate them from your profile page.</p>
          <?php else: ?>
            <p class="help-note footer-text" style="margin-top:12px;">Choose a strong password. After resetting, use it to sign in normally.</p>
          <?php endif; ?>
          <p class="fp-footnote">Lost all codes and cannot log in? Contact support for manual verification.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- 
    - #FOOTER
  -->

  <footer class="footer">
    <div class="section footer-top">
      <div class="container">
        <div class="footer-brand">
          <a href="#" class="logo">
            <img src="../assets/images/nebula-esports.png" style="width: 100px;" loading="lazy" alt="Nebula Esports logo">
          </a>
          <p class="footer-text">Our success in creating business solutions is due in large part to our talented and highly committed team.</p>
          <ul class="social-list">
            <li><a href="#" class="social-link"><ion-icon name="logo-facebook"></ion-icon></a></li>
            <li><a href="#" class="social-link"><ion-icon name="logo-twitter"></ion-icon></a></li>
            <li><a href="#" class="social-link"><ion-icon name="logo-instagram"></ion-icon></a></li>
            <li><a href="#" class="social-link"><ion-icon name="logo-youtube"></ion-icon></a></li>
          </ul>
        </div>
        <div class="footer-list">
          <p class="title footer-list-title has-after">Usefull Links</p>
          <ul>
            <li><a href="../index.html#home" class="footer-link">Home</a></li>
            <li><a href="../index.html#tournament" class="footer-link">Tournaments</a></li>
            <li><a href="../index.html#news" class="footer-link">News</a></li>
            <li><a href="./contact.html" class="footer-link">Contact Us</a></li>
            <li><a href="./rules.html" class="footer-link">Rules & Guidelines</a></li>
          </ul>
        </div>
        <div class="footer-list">
          <p class="title footer-list-title has-after">Contact Us</p>
          <div class="contact-item"><span class="span">Location:</span>
            <address class="contact-link">Nebula Institute of Technology <br> Negombo Road, <br> Welisara.</address>
          </div>
          <div class="contact-item"><span class="span">Join Us:</span>
            <a href="mailto:info@sevinda-herath.is-a.dev" class="contact-link">info@sevinda-herath.is-a.dev</a>
          </div>
          <div class="contact-item"><span class="span">Phone:</span>
            <a href="tel:+12345678910" class="contact-link">+94 (011) 216-2162</a>
          </div>
        </div>
        <div class="footer-list">
          <p class="title footer-list-title has-after">Newsletter Signup</p>
          <form action="../index.html" method="get" class="footer-form">
            <input type="email" name="email_address" required placeholder="Your Email" autocomplete="off" class="input-field">
            <button type="submit" class="btn" data-btn>Subscribe Now</button>
          </form>
        </div>
      </div>
    </div>
    <div class="footer-bottom"><div class="container"><p class="copyright">&copy; 2025 Sevinda-Herath All Rights Reserved.</p></div></div>
  </footer>

  <!-- 
    - #BACK TO TOP
  -->
  <a href="#top" class="back-top-btn" aria-label="back to top" data-back-top-btn>
    <ion-icon name="arrow-up-outline" aria-hidden="true"></ion-icon>
  </a>

  <!-- 
    - #CUSTOM CURSOR
  -->
  <div class="cursor" data-cursor></div>

  <!-- 
    - custom js link
  -->
  <script src="../assets/js/script.js"></script>
  <script src="../assets/js/bg.js"></script>

  <!-- 
    - ionicon link
  -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

</body>

</html>
