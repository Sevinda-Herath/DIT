<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pdo = db();
verify_csrf();

// Guard: must come from step 1
if (empty($_SESSION['signup_step1'])) {
    redirect('../php/signup-login.php');
}

$base = $_SESSION['signup_step1'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect step 2 fields
    $full_name = trim($_POST['full_name'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $university = trim($_POST['university'] ?? '');
    $nic = trim($_POST['nic'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');

    $team_name = trim($_POST['team_name'] ?? '');
    $team_captain = trim($_POST['team_captain'] ?? '');
    $players_count = (int)($_POST['players_count'] ?? 1);
    $players_count = max(1, min(5, $players_count));

    $game_titles = $_POST['game_titles'] ?? '';
    if (is_array($game_titles)) { $game_titles = array_values($game_titles); }
    elseif (is_string($game_titles)) { $game_titles = array_filter(array_map('trim', explode(',', $game_titles))); }
    else { $game_titles = []; }

    // Basic validation
    foreach (['full_name','dob','location','university','nic','mobile','team_name','team_captain'] as $f) {
        if (!${$f}) $errors[] = ucfirst(str_replace('_',' ', $f)).' is required';
    }

    // Upload (optional)
    $upload = handle_upload($_FILES['team_logo'] ?? null);
    if (!$upload['ok']) {
        $errors[] = $upload['error'];
    }

    if (!$errors) {
        // Create user + profile in transaction
        $pdo->beginTransaction();
        try {
      $stmt = $pdo->prepare('INSERT INTO users (username,email,password_hash,created_at) VALUES (?,?,?,?)');
      $stmt->execute([
        $base['username'],
        $base['email'],
        $base['password_hash'],
        (new DateTime('now'))->format('Y-m-d H:i:s')
      ]);
            $userId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO profiles (user_id,full_name,dob,location,university,nic,mobile,team_name,team_captain,players_count,game_titles,team_logo_path) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $userId,
                $full_name,
                $dob,
                $location,
                $university,
                $nic,
                $mobile,
                $team_name,
                $team_captain,
                $players_count,
                json_encode($game_titles),
                $upload['path']
            ]);

            // Members
            $memStmt = $pdo->prepare('INSERT INTO members (user_id, idx, name, nic, email, phone) VALUES (?,?,?,?,?,?)');
            for ($i=1; $i <= $players_count; $i++) {
                $memStmt->execute([
                    $userId,
                    $i,
                    trim($_POST["member{$i}_name"] ?? ''),
                    trim($_POST["member{$i}_nic"] ?? ''),
                    trim($_POST["member{$i}_email"] ?? ''),
                    trim($_POST["member{$i}_phone"] ?? ''),
                ]);
            }

            $pdo->commit();
            unset($_SESSION['signup_step1']);
            $_SESSION['user_id'] = $userId;
            // Generate recovery codes and store plain list in session for one-time display
            $_SESSION['recovery_codes_plain'] = generate_recovery_codes($userId);
            redirect('../php/user.php?show_recovery=1');
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to create account. Please try again.';
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
  <title>Signup | Nebula Esports 2025</title>
  <link rel="shortcut icon" href="../assets/images/nebula-esports.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;500;600;700&family=Work+Sans:wght@600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="preload" as="image" href="../assets/images/hero-banner.png">
  <link rel="preload" as="image" href="../assets/images/hero-banner-bg.png">
  <style>
    /* Responsive tweaks for small screens */
    @media (max-width: 640px) {
      #panel-game #game-titles .game-btn-row {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 8px;
        justify-content: stretch;
      }
      #panel-game #game-titles .game-btn {
        font-size: 0.9rem;
        padding: 10px 12px;
        min-width: 0;
        text-align: center;
      }
      #panel-game .auth-row {
        align-items: stretch;
        flex-direction: row;
      }
      #panel-game .auth-row .btn {
        width: 100%;
      }
    }
  </style>
</head>
<body id="top">
  <header class="header active" data-header>
    <div class="container">
      <a href="#" class="logo"><img src="../assets/images/nebula-esports.png" style="width:80px; height:auto;" alt="logo"></a>
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

  <section class="section auth signup-wizard" aria-labelledby="signup-title">
    <div class="container">
      <p class="section-subtitle" data-reveal="bottom">&nbsp;</p>
      <p class="section-subtitle" data-reveal="bottom">&nbsp;</p>
      <p class="section-subtitle" data-reveal="bottom">Register your team</p>
      <h1 class="h2 section-title" id="signup-title" data-reveal="bottom">Tournament <span class="span">Signup</span></h1>

      <div class="auth-card" data-reveal="bottom">
        <?php if ($errors): ?>
          <div style="padding: 12px; color: #ff6b6b;">
            <?php foreach ($errors as $e): ?><p><?= h($e) ?></p><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form class="auth-form" action="" method="post" novalidate enctype="multipart/form-data" autocomplete="off">
          <?= csrf_field() ?>
          <!-- Simplified single form wizard -->

          <!-- Panels -->
          <div class="auth-panels">
            <!-- Details Panel -->
            <div id="panel-details" class="auth-panel active" role="tabpanel">
              <label for="your-name">Your Name</label>
              <input id="your-name" class="input-field" type="text" name="full_name" required placeholder="" autocomplete="off">

              <label for="your-dob">Date of Birth</label>
              <input id="your-dob" class="input-field" type="date" name="dob" placeholder="" autocomplete="off" required>

              <label for="your-location">Location</label>
              <input id="your-location" class="input-field" type="text" name="location" placeholder="" autocomplete="off" required>

              <label for="your-university">University / School</label>
              <input id="your-university" class="input-field" type="text" name="university" placeholder="" autocomplete="off" required>

              <label for="your-nic">NIC / School Index Number</label>
              <input id="your-nic" class="input-field" type="text" name="nic" placeholder="" autocomplete="off" required>

              <label for="your-mobile">Mobile Number</label>
              <input id="your-mobile" class="input-field" type="tel" name="mobile" required placeholder="" autocomplete="off">

              <div class="auth-row" style="margin-top:20px;">
                <button type="button" class="btn" data-next-tab="game">Next</button>
              </div>
            </div>

            <!-- Game Panel -->
            <div id="panel-game" class="auth-panel" role="tabpanel" hidden>
              <label for="team-name">Team Name</label>
              <input id="team-name" class="input-field" type="text" name="team_name" placeholder="" autocomplete="off" required>

              <label for="team-logo">Team Logo</label>
              <input id="team-logo" class="input-field" type="file" name="team_logo" accept="image/*">

              <label for="team-captain">Team Captain</label>
              <input id="team-captain" class="input-field" type="text" name="team_captain" placeholder="" autocomplete="off" required>

              <div id="game-titles" class="game-select-group" role="group" aria-labelledby="game-titles-label">
                <label id="game-titles-label" style="margin-bottom:8px;">Choose The Game(s)</label>
                <div class="game-btn-row">
                  <label class="game-btn"><input type="checkbox" name="game_titles[]" value="pubg_mobile" class="visually-hidden">PUBG Mobile</label>
                  <label class="game-btn"><input type="checkbox" name="game_titles[]" value="free_fire" class="visually-hidden">Free Fire</label>
                  <label class="game-btn"><input type="checkbox" name="game_titles[]" value="cod_pc" class="visually-hidden">Call of Duty (PC)</label>
                  <label class="game-btn"><input type="checkbox" name="game_titles[]" value="pubg_pc" class="visually-hidden">PUBG (PC)</label>
                </div>
              </div>

                <label for="players-count">Count of Players</label>
                <div style="width:100%;">
                <select id="players-count" class="input-field" name="players_count" required style="width:100%;">
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                  <option value="4">4</option>
                  <option value="5">5</option>
                </select>
                </div>

              <div class="auth-row" style="margin-top:24px; display:flex; gap:10px;">
                <button type="button" class="btn outline" data-prev-tab="details">Back</button>
                <button type="button" class="btn" id="to-members-btn" data-next-tab="members">Next</button>
                <button type="submit" class="btn" id="submit-from-game" hidden style="display:none;">Submit Registration</button>
              </div>
              
            </div>


            <!-- Members Panel -->
            <div id="panel-members" class="auth-panel" role="tabpanel" hidden>
              <p style="margin-bottom:12px;">Add your member details (auto-trim optional fields if not required)</p>
              <div id="members-container" class="members-grid" style="display:grid; gap:16px;">
                <?php for ($i=1;$i<=5;$i++): ?>
                  <div class="member-block" data-member="<?= $i ?>">
                    <p style="margin:0 0 6px; font-weight:600;">Member <?= str_pad((string)$i,2,'0',STR_PAD_LEFT) ?></p>
                    <input class="input-field" type="text" name="member<?= $i ?>_name" placeholder="Full Name" autocomplete="off">
                    <input class="input-field" type="text" name="member<?= $i ?>_nic" placeholder="NIC" autocomplete="off">
                    <input class="input-field" type="email" name="member<?= $i ?>_email" placeholder="Email" autocomplete="off">
                    <input class="input-field" type="tel" name="member<?= $i ?>_phone" placeholder="Phone Number" autocomplete="off">
                  </div>
                <?php endfor; ?>
              </div>
              <div class="auth-row" style="margin-top:24px; display:flex; gap:10px;">
                <button type="button" class="btn outline" data-prev-tab="game">Back</button>
                <button type="submit" class="btn" data-btn>Submit Registration</button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </section>

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
  <!-- Inline wizard styles removed; using global stylesheet definitions -->
<script>
    // Multi-step wizard without visible tabs
    (function () {
      const panels = {
        details: document.getElementById('panel-details'),
        game: document.getElementById('panel-game'),
        members: document.getElementById('panel-members'),
      };
      // Auto-fill Member 1 (account owner) from primary details
      const ownerEmail = '<?= h($base['email'] ?? '') ?>';
      const ownerSources = {
        name: document.getElementById('your-name'),
        nic: document.getElementById('your-nic'),
        phone: document.getElementById('your-mobile')
      };
      const member1 = {
        name: document.querySelector('[name="member1_name"]'),
        nic: document.querySelector('[name="member1_nic"]'),
        email: document.querySelector('[name="member1_email"]'),
        phone: document.querySelector('[name="member1_phone"]')
      };

      function syncOwnerToMember1(force = false) {
        if (member1.name && ownerSources.name) {
          if (force || !member1.name.value) member1.name.value = ownerSources.name.value;
        }
        if (member1.nic && ownerSources.nic) {
          if (force || !member1.nic.value) member1.nic.value = ownerSources.nic.value;
        }
        if (member1.phone && ownerSources.phone) {
          if (force || !member1.phone.value) member1.phone.value = ownerSources.phone.value;
        }
        if (member1.email && ownerEmail) {
          if (force || !member1.email.value) member1.email.value = ownerEmail;
        }
      }
      // Keep in sync while user edits (only if member field untouched)
      Object.values(ownerSources).forEach(src => {
        if (!src) return;
        src.addEventListener('input', () => syncOwnerToMember1(false));
      });
      const playersSelect = document.getElementById('players-count');
      const toMembersBtn = document.getElementById('to-members-btn');
      const submitFromGame = document.getElementById('submit-from-game');
      const form = document.querySelector('form.auth-form');
      // Game selection visual state
      const gameCheckboxes = document.querySelectorAll('#game-titles .game-btn input[type="checkbox"]');
      function refreshGameSelections() {
        gameCheckboxes.forEach(cb => {
          const label = cb.closest('.game-btn');
          if (!label) return;
          label.classList.toggle('selected', cb.checked);
        });
      }
      gameCheckboxes.forEach(cb => {
        cb.addEventListener('change', refreshGameSelections);
      });
      refreshGameSelections();

      function showPanel(name) {
        Object.keys(panels).forEach(key => {
          const p = panels[key];
          if (!p) return;
          if (key === name) {
            p.classList.add('active');
            p.removeAttribute('hidden');
          } else {
            p.classList.remove('active');
            p.setAttribute('hidden','hidden');
          }
        });
        if (name === 'members') {
          syncOwnerToMember1(true);
        }
      }

      // Validation helpers
      function getMessage(el) {
        if (el.validity.valueMissing) return 'This field is required.';
        if (el.validity.typeMismatch && el.type === 'email') return 'Enter a valid email address.';
        if (el.validity.patternMismatch) return 'Please match the requested format.';
        return 'Please fix this field.';
      }
      function showError(el, msg) {
        el.classList.add('invalid');
        let hint = el.nextElementSibling;
        if (!hint || !hint.classList || !hint.classList.contains('field-error')) {
          hint = document.createElement('p');
          hint.className = 'field-error';
          el.parentNode.insertBefore(hint, el.nextSibling);
        }
        hint.textContent = msg;
      }
      function clearError(el) {
        el.classList.remove('invalid');
        const hint = el.nextElementSibling;
        if (hint && hint.classList && hint.classList.contains('field-error')) hint.remove();
      }
      function validateCurrent(panelEl) {
        let firstInvalid = null;
        const fields = panelEl.querySelectorAll('input, select, textarea');
        fields.forEach(el => {
          if (!el.checkValidity()) {
            if (!firstInvalid) firstInvalid = el;
            showError(el, getMessage(el));
          } else {
            clearError(el);
          }
        });
        if (firstInvalid) { firstInvalid.focus(); return false; }
        return true;
      }

      // Buttons
      document.querySelectorAll('[data-next-tab]').forEach(btn => {
        btn.addEventListener('click', () => {
          const target = btn.getAttribute('data-next-tab');
          const current = btn.closest('.auth-panel');
            if (!validateCurrent(current)) return;
          showPanel(target);
        });
      });
      document.querySelectorAll('[data-prev-tab]').forEach(btn => {
        btn.addEventListener('click', () => {
          const target = btn.getAttribute('data-prev-tab');
          showPanel(target);
        });
      });

      // Members logic
      function setMembersRequired(count) {
        document.querySelectorAll('#panel-members .member-block').forEach(block => {
          const idx = parseInt(block.getAttribute('data-member'),10);
            const req = idx <= count;
            block.querySelectorAll('input').forEach(inp => inp.required = req);
        });
      }
      function updateMembersStep() {
        const val = playersSelect ? playersSelect.value : '';
        const isSolo = val === '1';
        if (toMembersBtn && submitFromGame) {
          toMembersBtn.style.display = isSolo ? 'none' : '';
          submitFromGame.style.display = isSolo ? '' : 'none';
        }
        const count = val && !isNaN(parseInt(val,10)) ? parseInt(val,10) : 5;
        document.querySelectorAll('#panel-members .member-block').forEach(block => {
          const idx = parseInt(block.getAttribute('data-member'),10);
          const visible = idx <= count;
          block.style.display = visible ? '' : 'none';
          if (!visible) block.querySelectorAll('input').forEach(inp => clearError(inp));
        });
        setMembersRequired(count);
        if (isSolo && panels.members && panels.members.classList.contains('active')) {
          showPanel('game');
        }
      }
      if (playersSelect) {
        playersSelect.addEventListener('change', updateMembersStep);
        updateMembersStep();
      }

      // Live validation
      document.querySelectorAll('.auth-panel input, .auth-panel select').forEach(el => {
        el.addEventListener('input', () => { if (el.checkValidity()) clearError(el); });
        el.addEventListener('blur', () => { if (!el.checkValidity()) showError(el,getMessage(el)); else clearError(el); });
      });

      // Final submit validate everything visible
      if (form) {
        form.addEventListener('submit', e => {
          updateMembersStep();
          // Validate all visible panels
          const visiblePanels = Object.values(panels).filter(p => p.classList.contains('active'));
          for (const vp of visiblePanels) {
            if (!validateCurrent(vp)) { e.preventDefault(); return; }
          }
          // Also validate required but currently hidden members if count < 5 (already trimmed)
        });
      }

      // Initial state
      showPanel('details');
    })();
   </script> 
  <script src="../assets/js/script.js"></script>
  <script src="../assets/js/bg.js"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
