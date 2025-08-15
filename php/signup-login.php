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
      $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($password, $row['password_hash'])) {
                $errors[] = 'Invalid credentials.';
            } else {
                $_SESSION['user_id'] = (int)$row['id'];
        if (isset($_POST['remember'])) {
          create_remember_token((int)$row['id']);
        }
                redirect('../php/user.php');
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
                redirect('../php/signup.php');
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
  <style>
    .auth-errors { 
      border: 2px solid #ff4d4d; 
      background: rgba(255, 60, 60, 0.12); 
      padding: 14px 18px; 
      border-radius: 6px; 
      margin: 0 0 22px; 
      box-shadow: 0 4px 12px -2px rgba(255,0,0,0.15);
      position: relative;
      overflow: hidden;
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
    .auth-errors-title:before { 
      content: "⚠"; 
      font-size: 16px; 
      line-height: 1; 
    }
    .auth-errors ul { 
      margin: 0; 
      padding: 0 0 0 18px; 
      list-style: disc; 
      display: grid; 
      gap: 4px; 
    }
    .auth-errors li { 
      color: #ffd9d9; 
      font-size: 13px; 
      line-height: 1.35; 
    }
    .auth-errors li::marker { color: #ff8686; }
    @media (min-width: 600px) { 
      .auth-errors { padding: 16px 22px; }
      .auth-errors-title { font-size: 15px; }
      .auth-errors li { font-size: 13.5px; }
    }
  /* Password strength styles */
  .pw-strength { margin-top:10px; background:#101010; border:1px solid #262626; border-radius:8px; padding:12px 14px 14px; font-size:12px; line-height:1.35; position:relative; }
  .pw-strength[data-score="0"] { --pw-color:#ff4d4d; }
  .pw-strength[data-score="1"] { --pw-color:#ff784d; }
  .pw-strength[data-score="2"] { --pw-color:#ffb84d; }
  .pw-strength[data-score="3"] { --pw-color:#f2d64b; }
  .pw-strength[data-score="4"] { --pw-color:#5cc16a; }
  .pw-strength[data-score="5"] { --pw-color:#27c46a; }
  .pw-header { display:flex; justify-content:space-between; align-items:center; gap:10px; margin:0 0 8px; font-weight:600; letter-spacing:.4px; }
  .pw-header strong { color:var(--pw-color,#888); }
  .pw-meter { height:7px; background:#1d1d1d; border-radius:5px; overflow:hidden; position:relative; margin:0 0 8px; }
  .pw-meter span { display:block; height:100%; width:0; background:var(--pw-color,#444); transition:width .35s ease, background .35s ease; }
  .pw-criteria { list-style:none; margin:0; padding:0; display:grid; gap:5px; }
  .pw-criteria li { display:flex; align-items:center; gap:6px; color:#b5b5b5; }
  .pw-criteria li .bullet { width:10px; height:10px; border-radius:50%; background:#444; box-shadow:0 0 0 1px #333 inset; transition:background .3s ease, transform .3s; }
  .pw-criteria li.ok .bullet { background:#27c46a; box-shadow:0 0 0 1px #1d9a52 inset; transform:scale(1.05); }
  .pw-criteria li.ok { color:#d9f7e3; }
  .pw-disabled { opacity:.55; pointer-events:none; }
  @media (min-width:600px){ .pw-strength { font-size:12.5px; } }
  </style>
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
      <a href="../php/signup-login.php" class="btn" data-btn>LOGIN / SIGN UP</a>
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
          <div class="auth-errors" role="alert" aria-live="assertive" data-reveal="bottom">
            <div class="auth-errors-title">Please fix the following</div>
            <ul>
              <?php foreach ($errors as $e): ?>
                <li><?= h($e) ?></li>
              <?php endforeach; ?>
            </ul>
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
                  <input type="checkbox" name="remember" checked> <span>Remember me</span>
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
              <div id="password-strength" class="pw-strength" aria-live="polite" hidden></div>

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
  <script>
    (function(){
      const pw = document.getElementById('signup-password');
      const confirm = document.getElementById('signup-confirm');
      const container = document.getElementById('password-strength');
      const signupForm = document.querySelector('#panel-signup form.auth-form');
      const submitBtn = signupForm ? signupForm.querySelector('button[type="submit"]') : null;
      if(!pw || !container) return;

  // Build static structure once (lazy visible)
  container.classList.add('pw-strength');
  container.innerHTML = `
        <div class="pw-header">Password Strength: <strong class="pw-label">&nbsp;</strong><span class="pw-count" style="margin-left:auto;font-weight:500;color:#888;font-size:11px;"></span></div>
        <div class="pw-meter"><span></span></div>
        <ul class="pw-criteria">
          <li data-rule="length8"><span class="bullet"></span>8+ chars</li>
          <li data-rule="length12"><span class="bullet"></span>12+ chars (better)</li>
          <li data-rule="case"><span class="bullet"></span>Upper & lower case</li>
          <li data-rule="number"><span class="bullet"></span>Number</li>
          <li data-rule="symbol"><span class="bullet"></span>Symbol</li>
        </ul>`;

      const labelEl = container.querySelector('.pw-label');
      const countEl = container.querySelector('.pw-count');
      const meterBar = container.querySelector('.pw-meter span');
      const criteriaEls = container.querySelectorAll('.pw-criteria li');

      function assess(p){
        const tests = {
          length8: p.length >= 8,
          length12: p.length >= 12,
          lower: /[a-z]/.test(p),
          upper: /[A-Z]/.test(p),
          number: /[0-9]/.test(p),
          symbol: /[^A-Za-z0-9]/.test(p)
        };
        let score = 0;
        if (tests.length8) score++;
        if (tests.length12) score++;
        if (tests.lower && tests.upper) score++;
        if (tests.number) score++;
        if (tests.symbol) score++;
        return {tests, score};
      }
      const labels = ['Very Weak','Weak','Fair','Good','Strong','Very Strong'];

      function update(){
        const val = pw.value;
        const {tests, score} = assess(val);
        container.setAttribute('data-score', String(score));
        meterBar.style.width = ((score/5)*100) + '%';
        labelEl.textContent = labels[score];
        countEl.textContent = val.length + ' chars';
        criteriaEls.forEach(li => {
          const rule = li.getAttribute('data-rule');
          let ok = false;
            if (rule === 'length8') ok = tests.length8;
            else if (rule === 'length12') ok = tests.length12;
            else if (rule === 'case') ok = tests.lower && tests.upper;
            else if (rule === 'number') ok = tests.number;
            else if (rule === 'symbol') ok = tests.symbol;
          li.classList.toggle('ok', ok);
        });
        // No longer enforce minimum strength; just display feedback
        if(submitBtn){
          if(submitBtn.disabled){
            submitBtn.disabled = false;
            submitBtn.classList.remove('pw-disabled');
          }
        }
        if(confirm){
          if (val && confirm.value && confirm.value !== val) {
            confirm.setCustomValidity('Passwords do not match');
          } else {
            confirm.setCustomValidity('');
          }
        }
      }
  // Show only while password field focused
  pw.addEventListener('focus', () => { container.hidden = false; });
  pw.addEventListener('blur', () => { container.hidden = true; });
  pw.addEventListener('input', update);
      if(confirm) confirm.addEventListener('input', update);
  update();
    })();
  </script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
