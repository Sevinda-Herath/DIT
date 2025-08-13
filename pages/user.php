<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pdo = db();
$userId = require_login();

// Fetch profile
$stmt = $pdo->prepare('SELECT u.username, u.email, p.full_name, p.dob, p.location, p.university, p.nic, p.mobile, p.team_name, p.team_captain, p.players_count, p.game_titles, p.team_logo_path FROM users u LEFT JOIN profiles p ON p.user_id = u.id WHERE u.id = ?');
$stmt->execute([$userId]);
$profile = $stmt->fetch() ?: [];
$profile['game_titles'] = $profile['game_titles'] ? json_decode($profile['game_titles'], true) : [];

// Fetch members
$membersStmt = $pdo->prepare('SELECT idx, name, nic, email, phone FROM members WHERE user_id = ? ORDER BY idx ASC');
$membersStmt->execute([$userId]);
$members = $membersStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profile | Nebula Esports 2025</title>
  <link rel="shortcut icon" href="../assets/images/nebula-esports.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;500;600;700&family=Work+Sans:wght@600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body id="top">
  <header class="header active" data-header>
    <div class="container">
      <a href="#" class="logo"><img src="../assets/images/nebula-esports.png" style ="width:80px; height:auto;" alt="Nebula Esports logo"></a>
      <nav class="navbar" data-navbar>
        <ul class="navbar-list">
          <li class="navbar-item"><a href="../index.html#home" class="navbar-link" data-nav-link>home</a></li>
          <li class="navbar-item"><a href="../index.html#tournament" class="navbar-link" data-nav-link>tournament</a></li>
          <li class="navbar-item"><a href="../index.html#news" class="navbar-link" data-nav-link>news</a></li>
          <li class="navbar-item"><a href="./contact.html" class="navbar-link" data-nav-link>contact</a></li>
          <li class="navbar-item"><a href="./rules.html" class="navbar-link" data-nav-link>Rules & Guidelines</a></li>
        </ul>
      </nav>
      <a href="./signup-login.php" class="btn" data-btn>ACCOUNT</a>
      <button class="nav-toggle-btn" aria-label="toggle menu" data-nav-toggler>
        <span class="line line-1"></span>
        <span class="line line-2"></span>
        <span class="line line-3"></span>
      </button>
    </div>
  </header>

  <main class="u-wrap" id="user-profile">
    <div class="container">
      <div class="u-card" data-reveal="bottom">
        <div class="u-card-header">
          <h1 class="u-title">Your <span style="color: var(--text-purple);">Profile</span></h1>
          <div class="u-actions">
            <a class="u-btn u-btn-primary" href="/pages/user.html">Switch to UI-only Version</a>
          </div>
        </div>
        <div class="u-card-body">
          <section id="u-view" aria-label="Profile viewer" class="u-view">
            <h2 class="u-section-title">Account</h2>
            <div class="u-grid cols-2 u-view-list" id="u-account-view">
              <div class="u-item"><span class="u-key">Username</span><span class="u-val"><?= h($profile['username'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">Email</span><span class="u-val"><?= h($profile['email'] ?? '—') ?></span></div>
            </div>

            <h2 class="u-section-title">Your Details</h2>
            <div class="u-grid cols-3 u-view-list" id="u-personal-view">
              <div class="u-item"><span class="u-key">Full Name</span><span class="u-val"><?= h($profile['full_name'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">Date of Birth</span><span class="u-val"><?= h($profile['dob'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">Location</span><span class="u-val"><?= h($profile['location'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">University/College</span><span class="u-val"><?= h($profile['university'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">NIC</span><span class="u-val"><?= h($profile['nic'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">Mobile</span><span class="u-val"><?= h($profile['mobile'] ?? '—') ?></span></div>
            </div>

            <h2 class="u-section-title">Game Details</h2>
            <div class="u-grid cols-3 u-view-list" id="u-game-view">
              <div class="u-item"><span class="u-key">Team Name</span><span class="u-val"><?= h($profile['team_name'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">Team Captain</span><span class="u-val"><?= h($profile['team_captain'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">Players</span><span class="u-val"><?= h((string)($profile['players_count'] ?? '—')) ?></span></div>
              <div class="u-item" style="grid-column: 1 / -1;">
                <span class="u-key">Game Titles</span>
                <div class="u-badge-wrap">
                  <?php if (!empty($profile['game_titles'])): foreach ($profile['game_titles'] as $gt): ?>
                    <span class="u-badge"><?= h(ucwords(str_replace(['_','pc'], [' ', 'PC'], $gt))) ?></span>
                  <?php endforeach; else: ?>
                    <span class="u-hint">None selected</span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="u-item" style="grid-column: 1 / -1;">
                <span class="u-key">Team Logo</span>
                <div class="u-logo">
                  <?php if (!empty($profile['team_logo_path'])): ?>
                    <img alt="Team logo" src="<?= h($profile['team_logo_path']) ?>" style="width:72px;height:72px;object-fit:cover;border-radius:5px;border:2px solid var(--border-purple-alpha-30);background:#111;">
                  <?php else: ?>
                    <span class="u-hint">No logo uploaded</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <h2 class="u-section-title">Members</h2>
            <div class="u-members" id="u-members-view">
              <?php if ($members): foreach ($members as $m): ?>
                <div class="u-item">
                  <span class="u-key">Member <?= (int)$m['idx'] ?></span>
                  <span class="u-val"><?= h($m['name'] ?: '—') ?></span>
                  <span class="u-hint">NIC: <?= h($m['nic'] ?: '—') ?> | Email: <?= h($m['email'] ?: '—') ?> | Phone: <?= h($m['phone'] ?: '—') ?></span>
                </div>
              <?php endforeach; else: ?>
                <p class="u-hint">No members added.</p>
              <?php endif; ?>
            </div>
          </section>
        </div>
      </div>
    </div>
  </main>

  <footer class="footer">
    <div class="section footer-top">
      <div class="container">
        <div class="footer-brand">
          <a href="#" class="logo"><img src="../assets/images/nebula-esports.png" style="width: 100px;" loading="lazy" alt="Nebula Esports logo"></a>
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
          <div class="contact-item"><span class="span">Location:</span>
            <address class="contact-link">Nebula Institute of Technology <br> Negombo Road, <br> Welisara.</address></div>
          <div class="contact-item"><span class="span">Join Us:</span>
            <a href="mailto:info@sevinda-herath.is-a.dev" class="contact-link">info@sevinda-herath.is-a.dev</a></div>
          <div class="contact-item"><span class="span">Phone:</span>
            <a href="tel:+12345678910" class="contact-link">+94 (011) 216-2162</a></div>
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
