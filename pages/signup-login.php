<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pdo = db();
verify_csrf();

$errors = [];
$activeTab = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $activeTab = 'login';
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$email || !$password) {
            $errors[] = 'Email and password are required.';
        } else {
            $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($password, $row['password_hash'])) {
                $errors[] = 'Invalid credentials.';
            } else {
                $_SESSION['user_id'] = (int)$row['id'];
                redirect('/pages/user.php');
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'signup_step1') {
        $activeTab = 'signup';
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        $terms = isset($_POST['terms']);
        if (!$username || !$email || !$password || !$confirm) {
            $errors[] = 'All fields are required.';
        } elseif ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        } elseif (!$terms) {
            $errors[] = 'You must agree to the rules & guidelines.';
        } else {
            // Check uniqueness
            $stmt = $pdo->prepare('SELECT 1 FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email already exists.';
            } else {
                // Temporarily store in session for step 2
                $_SESSION['signup_step1'] = [
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ];
                redirect('/pages/signup.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up / Login | Nebula Esports 2025</title>
  <link rel="shortcut icon" href="../assets/images/nebula-esports.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;500;600;700&family=Work+Sans:wght@600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="preload" as="image" href="../assets/images/hero-banner.png">
  <link rel="preload" as="image" href="../assets/images/hero-banner-bg.png">
</head>
<body id="top">
  <header class="header active" data-header>
    <div class="container">
      <a href="#" class="logo">
        <img src="../assets/images/nebula-esports.png" style ="width:80px; height:auto;" alt="logo">
      </a>
      <nav class="navbar" data-navbar>
        <ul class="navbar-list">
          <li class="navbar-item"><a href="../index.html#home" class="navbar-link" data-nav-link>home</a></li>
          <li class="navbar-item"><a href="../index.html#tournament" class="navbar-link" data-nav-link>tournament</a></li>
          <li class="navbar-item"><a href="../index.html#news" class="navbar-link" data-nav-link>news</a></li>
          <li class="navbar-item"><a href="./contact.html" class="navbar-link" data-nav-link>contact</a></li>
          <li class="navbar-item"><a href="./rules.html" class="navbar-link" data-nav-link>Rules & Guidelines</a></li>
        </ul>
      </nav>
      <a href="./signup-login.php" class="btn" data-btn>LOGIN / SIGN UP</a>
      <button class="nav-toggle-btn" aria-label="toggle menu" data-nav-toggler>
        <span class="line line-1"></span>
        <span class="line line-2"></span>
        <span class="line line-3"></span>
      </button>
    </div>
  </header>

  <section class="section auth" aria-labelledby="auth-title">
    <div class="container">
      <p class="section-subtitle" data-reveal="bottom">&nbsp;</p>
      <p class="section-subtitle" data-reveal="bottom">&nbsp;</p>
      <p class="section-subtitle" data-reveal="bottom">Join the community</p>
      <h1 class="h2 section-title" id="auth-title" data-reveal="bottom">LOGIN <span class="span">/</span> SIGN UP</h1>

      <div class="auth-card" data-reveal="bottom">
        <?php if ($errors): ?>
          <div class="auth-panels" style="padding: 12px; color: #ff6b6b;">
            <?php foreach ($errors as $e): ?>
              <p><?= h($e) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="auth-tabs" role="tablist">
          <button class="auth-tab <?= $activeTab==='login' ? 'active' : '' ?>" role="tab" aria-selected="<?= $activeTab==='login' ? 'true' : 'false' ?>" data-auth-tab="login">Login</button>
          <button class="auth-tab <?= $activeTab==='signup' ? 'active' : '' ?>" role="tab" aria-selected="<?= $activeTab==='signup' ? 'true' : 'false' ?>" data-auth-tab="signup">Sign Up</button>
        </div>

        <div class="auth-panels">
          <div class="auth-panel <?= $activeTab==='login' ? 'active' : '' ?>" id="panel-login" role="tabpanel" aria-labelledby="tab-login">
            <form class="auth-form" action="" method="post" novalidate>
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="login">
              <label for="login-email" class="visually-hidden">Email</label>
              <input id="login-email" class="input-field" type="email" name="email" placeholder="Email" required autocomplete="email">

              <label for="login-password" class="visually-hidden">Password</label>
              <input id="login-password" class="input-field" type="password" name="password" placeholder="Password" required autocomplete="current-password">

              <div class="auth-row">
                <label class="checkbox">
                  <input type="checkbox" name="remember"> <span>Remember me</span>
                </label>
                <a href="#" class="link-sm">Forgot password?</a>
              </div>

              <button type="submit" class="btn" data-btn>Login</button>
            </form>
          </div>

          <div class="auth-panel <?= $activeTab==='signup' ? 'active' : '' ?>" id="panel-signup" role="tabpanel" aria-labelledby="tab-signup">
            <form class="auth-form" action="" method="post" novalidate>
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="signup_step1">
              <label for="signup-username" class="visually-hidden">Username</label>
              <input id="signup-username" class="input-field" type="text" name="username" placeholder="Username" required autocomplete="username">

              <label for="signup-email" class="visually-hidden">Email</label>
              <input id="signup-email" class="input-field" type="email" name="email" placeholder="Email" required autocomplete="email">

              <label for="signup-password" class="visually-hidden">Password</label>
              <input id="signup-password" class="input-field" type="password" name="password" placeholder="Password" required autocomplete="new-password">

              <label for="signup-confirm" class="visually-hidden">Confirm Password</label>
              <input id="signup-confirm" class="input-field" type="password" name="confirm" placeholder="Confirm Password" required autocomplete="new-password">
              
              <label class="checkbox">
                <input type="checkbox" name="terms" required> <span>I agree to the rules & guidelines</span>
              </label>

              <button type="submit" class="btn" data-btn>Create Account</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <footer class="footer">
    <?php // Keep existing footer from HTML; for brevity omitted in PHP template reuse. ?>
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
            <li><a href="#" class="footer-link">Home</a></li>
            <li><a href="#" class="footer-link">Tournaments</a></li>
            <li><a href="#" class="footer-link">News</a></li>
            <li><a href="#" class="footer-link">Contact Us</a></li>
            <li><a href="#" class="footer-link">Rules & Guidelines</a></li>
          </ul>
        </div>
        <div class="footer-list">
          <p class="title footer-list-title has-after">Contact Us</p>
          <div class="contact-item">
            <span class="span">Location:</span>
            <address class="contact-link">Nebula Institute of Technology <br> Negombo Road, <br> Welisara.</address>
          </div>
          <div class="contact-item">
            <span class="span">Join Us:</span>
            <a href="mailto:info@sevinda-herath.is-a.dev" class="contact-link">info@sevinda-herath.is-a.dev</a>
          </div>
          <div class="contact-item">
            <span class="span">Phone:</span>
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

  <a href="#top" class="back-top-btn" aria-label="back to top" data-back-top-btn><ion-icon name="arrow-up-outline" aria-hidden="true"></ion-icon></a>
  <div class="cursor" data-cursor></div>
  <script src="../assets/js/script.js"></script>
  <script src="../assets/js/bg.js"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
