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

  <!-- 
    - primary meta tags
  -->
    <title>User Profile | Nebula Esports 2025</title>
    <meta name="title" content="Nebula Esports 2025 - BY Nebula Esports">
    <meta name="description" content="Manage your Nebula Esports profile. View and update your details with a clean two-mode interface.">

    <!-- 
      - favicon
    -->
    <link rel="shortcut icon" href="../assets/images/nebula-esports.png" type="image/png">


    <!-- 
      - google font link
    -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
      href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;500;600;700&family=Work+Sans:wght@600&display=swap"
      rel="stylesheet">

    <!-- 
      - site css (for layout, header/footer, variables)
    -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- 
      - page-scoped styles (custom classes; use theme variables, avoid site component classes)
    -->
    <style>

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
          <img src="../assets/images/nebula-esports.png" style ="width:80px; height:auto;" alt="Nebula Esports logo">
        </a>

        <nav class="navbar" data-navbar>
          <ul class="navbar-list">

            <li class="navbar-item">
              <a href="../index.html#home" class="navbar-link" data-nav-link>home</a>
            </li>

            <li class="navbar-item">
              <a href="../index.html#tournament" class="navbar-link" data-nav-link>tournament</a>
            </li>

            <li class="navbar-item">
              <a href="../index.html#news" class="navbar-link" data-nav-link>news</a>
            </li>

            <li class="navbar-item">
              <a href="./contact.html" class="navbar-link" data-nav-link>contact</a>
            </li>
            <li class="navbar-item">
              <a href="./rules.html" class="navbar-link" data-nav-link>Rules & Guidelines</a>
            </li>

          </ul>
        </nav>

        <?php if (isset($_SESSION['user_id'])): ?>
          <form action="../php/logout.php" method="post" style="display:inline;">
            <?= csrf_field(); ?>
            <button type="submit" class="btn" data-btn>LOGOUT</button>
          </form>
        <?php else: ?>
          <a href="../php/signup-login.php" class="btn" data-btn>LOGIN / SIGN UP</a>
        <?php endif; ?>

        <button class="nav-toggle-btn" aria-label="toggle menu" data-nav-toggler>
          <span class="line line-1"></span>
          <span class="line line-2"></span>
          <span class="line line-3"></span>
        </button>

      </div>
    </header>
    <!--
      - USER PROFILE CONTENT
    -->

    <main class="u-wrap" id="user-profile">
      <div class="container">

        <div class="u-card" data-reveal="bottom">
          <div class="u-card-header">
            <h1 class="u-title">Your <span style="color: var(--text-purple);">Profile</span></h1>
            <div class="u-actions">
              <button type="button" id="u-edit-btn" class="u-btn u-btn-primary">Edit Profile</button>
                <button type="button" id="u-save-btn" class="u-btn u-btn-primary" hidden style="display:none;">Save Changes</button>
                <button type="button" id="u-cancel-btn" class="u-btn u-btn-ghost" hidden style="display:none;">Cancel</button>
            </div>
          </div>
        <div class="u-card-body">
            <!-- VIEW MODE -->
            <section id="u-view" aria-label="Profile viewer" class="u-view">
              <!-- Account Overview -->
              <h2 class="u-section-title">Account</h2>
              <div class="u-grid cols-2 u-view-list" id="u-account-view">
                <div class="u-item"><span class="u-key">Username</span><span class="u-val" data-view="username"><?= h($profile['username'] ?? '—') ?></span></div>
                <div class="u-item"><span class="u-key">Email</span><span class="u-val" data-view="email"><?= h($profile['email'] ?? '—') ?></span></div>
              </div>

            <h2 class="u-section-title">Your Details</h2>
            <div class="u-grid cols-3 u-view-list" id="u-personal-view">
              <div class="u-item"><span class="u-key">Full Name</span><span class="u-val" data-view="full_name"><?= h($profile['full_name'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">Date of Birth</span><span class="u-val" data-view="dob"><?= h($profile['dob'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">Location</span><span class="u-val" data-view="location"><?= h($profile['location'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">University/College</span><span class="u-val" data-view="university"><?= h($profile['university'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">NIC</span><span class="u-val" data-view="nic"><?= h($profile['nic'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">Mobile</span><span class="u-val" data-view="mobile"><?= h($profile['mobile'] ?? '—') ?></span></div>
            </div>

            <h2 class="u-section-title">Game Details</h2>
            <div class="u-grid cols-3 u-view-list" id="u-game-view">
              <div class="u-item"><span class="u-key">Team Name</span><span class="u-val" data-view="team_name"><?= h($profile['team_name'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">Team Captain</span><span class="u-val" data-view="team_captain"><?= h($profile['team_captain'] ?? '—') ?></span></div>
              <div class="u-item"><span class="u-key">Players</span><span class="u-val" data-view="players_count"><?= h((string)($profile['players_count'] ?? '—')) ?></span></div>
              <div class="u-item" style="grid-column: 1 / -1;">
                <span class="u-key">Game Titles</span>
                <div class="u-badge-wrap" data-view="game_titles">
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
                    <img id="u-logo-preview" data-view-logo src="<?= h($profile['team_logo_path']) ?>" alt="Team logo" style="width:72px;height:72px;object-fit:cover;border-radius:5px;border:2px solid var(--border-purple-alpha-30);background:#111;">
                  <?php else: ?>
                    <img id="u-logo-preview" data-view-logo src="" alt="Team logo" style="display:none;width:72px;height:72px;object-fit:cover;border-radius:5px;border:2px solid var(--border-purple-alpha-30);background:#111;">
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


            <!-- EDIT MODE -->
            <form id="u-edit" aria-label="Edit profile" class="u-edit" hidden>
              <!-- Account -->
              <h2 class="u-section-title">Account</h2>
              <div class="u-grid cols-2">
                <div class="u-field">
                  <label class="u-label" for="u-username">Username</label>
                  <input id="u-username" name="username" type="text" class="u-input" autocomplete="username" required value="<?= h($profile['username'] ?? '') ?>">
                </div>
              </div>

              <!-- Personal -->
              <h2 class="u-section-title">Your Details</h2>
              <div class="u-grid cols-3">
                <div class="u-field">
                  <label class="u-label" for="u-full-name">Full Name</label>
                  <input id="u-full-name" name="full_name" type="text" class="u-input" required value="<?= h($profile['full_name'] ?? '') ?>">
                </div>
                <div class="u-field">
                  <label class="u-label" for="u-dob">Date of Birth</label>
                  <input id="u-dob" name="dob" type="date" class="u-input" required value="<?= h($profile['dob'] ?? '') ?>">
                </div>
                <div class="u-field">
                  <label class="u-label" for="u-location">Location</label>
                  <input id="u-location" name="location" type="text" class="u-input" required value="<?= h($profile['location'] ?? '') ?>">
                </div>
                <div class="u-field">
                  <label class="u-label" for="u-university">University/College</label>
                  <input id="u-university" name="university" type="text" class="u-input" required value="<?= h($profile['university'] ?? '') ?>">
                </div>
                <div class="u-field">
                  <label class="u-label" for="u-nic">NIC</label>
                  <input id="u-nic" name="nic" type="text" class="u-input" required value="<?= h($profile['nic'] ?? '') ?>">
                </div>
                <div class="u-field">
                  <label class="u-label" for="u-mobile">Mobile</label>
                  <input id="u-mobile" name="mobile" type="tel" class="u-input" required value="<?= h($profile['mobile'] ?? '') ?>">
                </div>
              </div>

              <!-- Game Details -->
              <h2 class="u-section-title">Game Details</h2>
              <div class="u-grid cols-3">
                <div class="u-field">
                  <label class="u-label" for="u-team-name">Team Name</label>
                  <input id="u-team-name" name="team_name" type="text" class="u-input" required value="<?= h($profile['team_name'] ?? '') ?>">
                </div>
                <div class="u-field">
                  <label class="u-label" for="u-team-captain">Team Captain</label>
                  <input id="u-team-captain" name="team_captain" type="text" class="u-input" required value="<?= h($profile['team_captain'] ?? '') ?>">
                </div>
                <div class="u-field">
                  <label class="u-label" for="u-players-count">Players</label>
                  <select id="u-players-count" name="players_count" class="u-select" required>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <option value="<?= $i ?>" <?= (int)($profile['players_count'] ?? 1) === $i ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div class="u-field" style="grid-column: 1 / -1;">
                  <span class="u-label">Game Titles</span>
                  <div class="u-badge-wrap" id="u-game-titles">
                    <?php
                    $gameOptions = [
                      'pubg_mobile' => 'PUBG Mobile',
                      'free_fire' => 'Free Fire',
                      'cod_pc' => 'Call of Duty (PC)',
                      'pubg_pc' => 'PUBG (PC)'
                    ];
                    $selectedGames = $profile['game_titles'] ?? [];
                    foreach ($gameOptions as $key => $label):
                      $checked = in_array($key, $selectedGames) ? 'checked' : '';
                    ?>
                      <label class="u-badge" style="cursor: pointer;">
                        <input type="checkbox" name="game_titles[]" value="<?= h($key) ?>" class="u-visually-hidden" <?= $checked ?>>
                        <?= h($label) ?>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </div>
                <div class="u-field" style="grid-column: 1 / -1;">
                  <label class="u-label" for="u-team-logo">Team Logo</label>
                  <div class="u-logo">
                    <?php if (!empty($profile['team_logo_path'])): ?>
                      <img id="u-team-logo-preview" alt="Team logo preview" src="<?= h($profile['team_logo_path']) ?>" style="width:72px;height:72px;object-fit:cover;border-radius:5px;border:2px solid var(--border-purple-alpha-30);background:#111;">
                    <?php else: ?>
                      <img id="u-team-logo-preview" alt="Team logo preview" src="" style="display:none;width:72px;height:72px;object-fit:cover;border-radius:5px;border:2px solid var(--border-purple-alpha-30);background:#111;">
                    <?php endif; ?>
                    <input id="u-team-logo" name="team_logo" type="file" class="u-file" accept="image/*">
                  </div>
                  <p class="u-hint">Optional. PNG/JPG up to ~2MB.</p>
                </div>
              </div>

              <!-- Members -->
              <h2 class="u-section-title">Members</h2>
              <div class="u-members" id="u-members-edit">
                <?php 
                $playersCount = (int)($profile['players_count'] ?? 1);
                for ($i = 1; $i <= $playersCount; $i++):
                  $member = null;
                  foreach ($members as $m) {
                    if ((int)$m['idx'] === $i) {
                      $member = $m;
                      break;
                    }
                  }
                ?>
                  <div class="u-member">
                    <div class="u-caption">Member <?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="u-grid cols-3">
                      <div class="u-field">
                        <label class="u-label" for="u-m<?= $i ?>-name">Full Name</label>
                        <input id="u-m<?= $i ?>-name" name="member<?= $i ?>_name" type="text" class="u-input" value="<?= h($member['name'] ?? '') ?>" required>
                      </div>
                      <div class="u-field">
                        <label class="u-label" for="u-m<?= $i ?>-nic">NIC</label>
                        <input id="u-m<?= $i ?>-nic" name="member<?= $i ?>_nic" type="text" class="u-input" value="<?= h($member['nic'] ?? '') ?>" required>
                      </div>
                      <div class="u-field">
                        <label class="u-label" for="u-m<?= $i ?>-email">Email</label>
                        <input id="u-m<?= $i ?>-email" name="member<?= $i ?>_email" type="email" class="u-input" value="<?= h($member['email'] ?? '') ?>" required>
                      </div>
                      <div class="u-field" style="grid-column: 1 / -1;">
                        <label class="u-label" for="u-m<?= $i ?>-phone">Phone Number</label>
                        <input id="u-m<?= $i ?>-phone" name="member<?= $i ?>_phone" type="tel" class="u-input" value="<?= h($member['phone'] ?? '') ?>" required>
                      </div>
                    </div>
                  </div>
                <?php endfor; ?>
              </div>

              <div class="u-toolbar">
                <button type="button" class="u-btn u-btn-ghost" id="u-cancel-btn-2">Cancel</button>
                <button type="submit" class="u-btn u-btn-primary">Save Changes</button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </main>

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

            <p class="footer-text">
              Our success in creating business solutions is due in large part to our talented and highly committed team.
            </p>

            <ul class="social-list">

              <li>
                <a href="#" class="social-link">
                  <ion-icon name="logo-facebook"></ion-icon>
                </a>
              </li>

              <li>
                <a href="#" class="social-link">
                  <ion-icon name="logo-twitter"></ion-icon>
                </a>
              </li>

              <li>
                <a href="#" class="social-link">
                  <ion-icon name="logo-instagram"></ion-icon>
                </a>
              </li>

              <li>
                <a href="#" class="social-link">
                  <ion-icon name="logo-youtube"></ion-icon>
                </a>
              </li>

            </ul>

          </div>

          <div class="footer-list">

            <p class="title footer-list-title has-after">Usefull Links</p>

            <ul>

              <li>
                <a href="#" class="footer-link">Home</a>
              </li>

              <li>
                <a href="#" class="footer-link">Tournaments</a>
              </li>

              <li>
                <a href="#" class="footer-link">News</a>
              </li>

              <li>
                <a href="#" class="footer-link">Contact Us</a>
              </li>

              <li>
                <a href="#" class="footer-link">Rules & Guidelines</a>
              </li>

            </ul>

          </div>

          <div class="footer-list">

            <p class="title footer-list-title has-after">Contact Us</p>

            <div class="contact-item">
              <span class="span">Location:</span>

              <address class="contact-link">
                Nebula Institute of Technology <br>   
                Negombo Road, <br>   
                Welisara.   
              </address>
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
              <input type="email" name="email_address" required placeholder="Your Email" autocomplete="off"
                class="input-field">

              <button type="submit" class="btn" data-btn>Subscribe Now</button>
            </form>

          </div>

        </div>
      </div>

      <div class="footer-bottom">
        <div class="container">

          <p class="copyright">
            &copy; 2025 Sevinda-Herath All Rights Reserved.
          </p>

        </div>
      </div>

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
      - site js
    -->
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/bg.js"></script>

    <!-- 
      - page js (no backend/localStorage; UI only, with hook points)
    -->
    <script>
      (function () {
        // Known game keys from signup page
        const GAME_OPTIONS = [
          { key: 'pubg_mobile', label: 'PUBG Mobile' },
          { key: 'free_fire', label: 'Free Fire' },
          { key: 'cod_pc', label: 'Call of Duty (PC)' },
          { key: 'pubg_pc', label: 'PUBG (PC)' }
        ];

        // Initial state injected from PHP (database)
        const INITIAL_STATE = <?php
          $membersArr = array_map(function($m){
            return [
              'name' => $m['name'] ?? '',
              'nic' => $m['nic'] ?? '',
              'email' => $m['email'] ?? '',
              'phone' => $m['phone'] ?? ''
            ];
          }, $members ?? []);
          echo json_encode([
            'username' => $profile['username'] ?? '',
            'email' => $profile['email'] ?? '',
            'full_name' => $profile['full_name'] ?? '',
            'dob' => $profile['dob'] ?? '',
            'location' => $profile['location'] ?? '',
            'university' => $profile['university'] ?? '',
            'nic' => $profile['nic'] ?? '',
            'mobile' => $profile['mobile'] ?? '',
            'team_name' => $profile['team_name'] ?? '',
            'team_captain' => $profile['team_captain'] ?? '',
            'players_count' => $profile['players_count'] ?? (count($membersArr) ?: 1),
            'game_titles' => $profile['game_titles'] ?? [],
            'team_logo_url' => $profile['team_logo_path'] ?? '',
            'members' => $membersArr
          ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ?>;

        let state = null;

        function fetchUser() {
          return Promise.resolve(INITIAL_STATE);
        }

        function saveUser(updates) {
          const fd = new FormData();
          Object.entries(updates).forEach(([k,v]) => {
            if (k === 'members' || k === 'game_titles' || k === 'email') return; // email removed
            fd.append(k, v == null ? '' : v);
          });
          (updates.game_titles || []).forEach(g => fd.append('game_titles[]', g));
          (updates.members || []).forEach((m, idx) => {
            const i = idx + 1;
            fd.append('member' + i + '_name', m.name || '');
            fd.append('member' + i + '_nic', m.nic || '');
            fd.append('member' + i + '_email', m.email || '');
            fd.append('member' + i + '_phone', m.phone || '');
          });
          if (fields.team_logo.files && fields.team_logo.files[0]) {
            fd.append('team_logo', fields.team_logo.files[0]);
          } else if (state && state.team_logo_url) {
            fd.append('existing_team_logo_path', state.team_logo_url);
          }
          // CSRF token from PHP session
          fd.append('csrf_token', '<?= h($_SESSION['csrf_token'] ?? '') ?>');

          return fetch('../php/profile-save.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(json => {
              if (!json.ok) throw new Error(json.error || 'Save failed');
              state = json.data;
              return state;
            });
        }

        const $ = (sel, root = document) => root.querySelector(sel);
        const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

        // Cache DOM
        const view = $('#u-view');
        const edit = $('#u-edit');
        const editBtn = $('#u-edit-btn');
        const saveBtn = $('#u-save-btn');
        const cancelBtn = $('#u-cancel-btn');
        const cancelBtn2 = $('#u-cancel-btn-2');

        // Edit fields
        const form = edit;
        const fields = {
          username: $('#u-username'),
          full_name: $('#u-full-name'),
          dob: $('#u-dob'),
          location: $('#u-location'),
          university: $('#u-university'),
          nic: $('#u-nic'),
          mobile: $('#u-mobile'),
          team_name: $('#u-team-name'),
          team_captain: $('#u-team-captain'),
          players_count: $('#u-players-count'),
          team_logo: $('#u-team-logo'),
          team_logo_preview: $('#u-team-logo-preview'),
          game_titles_wrap: $('#u-game-titles'),
          members_wrap: $('#u-members-edit')
        };

        // View areas
        const viewAreas = {
          username: $('[data-view="username"]'),
          email: $('[data-view="email"]'),
          full_name: $('[data-view="full_name"]'),
          dob: $('[data-view="dob"]'),
          location: $('[data-view="location"]'),
          university: $('[data-view="university"]'),
          nic: $('[data-view="nic"]'),
          mobile: $('[data-view="mobile"]'),
          team_name: $('[data-view="team_name"]'),
          team_captain: $('[data-view="team_captain"]'),
          players_count: $('[data-view="players_count"]'),
          game_titles: $('[data-view="game_titles"]'),
          team_logo_url: $('#u-logo-preview'),
          members_wrap: $('#u-members-view')
        };

        function setMode(mode) {
          const isEdit = mode === 'edit';
          view.hidden = isEdit;
          edit.hidden = !isEdit;
          editBtn.hidden = isEdit;
          saveBtn.hidden = !isEdit;
          cancelBtn.hidden = !isEdit;
        }

        function renderGameTitleCheckboxes(selected = []) {
          fields.game_titles_wrap.innerHTML = '';
          GAME_OPTIONS.forEach(opt => {
            const id = `u-game-${opt.key}`;
            const wrap = document.createElement('label');
            wrap.className = 'u-badge';
            wrap.setAttribute('for', id);
            wrap.style.cursor = 'pointer';
            wrap.innerHTML = `<input id="${id}" type="checkbox" value="${opt.key}" class="u-visually-hidden"> ${opt.label}`;
            const input = wrap.querySelector('input');
            input.checked = selected.includes(opt.key);
            fields.game_titles_wrap.appendChild(wrap);
          });
        }

        function renderMembersEdit(members = [], playersCount = 1) {
          const count = Math.max(1, Math.min(5, parseInt(playersCount || 1, 10)));
          fields.members_wrap.innerHTML = '';
          for (let i = 1; i <= count; i++) {
            const data = members[i - 1] || { name: '', nic: '', email: '', phone: '' };
            const block = document.createElement('div');
            block.className = 'u-member';
            block.innerHTML = `
              <div class="u-caption">Member ${String(i).padStart(2, '0')}</div>
              <div class="u-grid cols-3">
                <div class="u-field">
                  <label class="u-label" for="u-m${i}-name">Full Name</label>
                  <input id="u-m${i}-name" name="member${i}_name" type="text" class="u-input" value="${data.name || ''}" required>
                </div>
                <div class="u-field">
                  <label class="u-label" for="u-m${i}-nic">NIC</label>
                  <input id="u-m${i}-nic" name="member${i}_nic" type="text" class="u-input" value="${data.nic || ''}" required>
                </div>
                <div class="u-field">
                  <label class="u-label" for="u-m${i}-email">Email</label>
                  <input id="u-m${i}-email" name="member${i}_email" type="email" class="u-input" value="${data.email || ''}" required>
                </div>
                <div class="u-field" style="grid-column: 1 / -1;">
                  <label class="u-label" for="u-m${i}-phone">Phone Number</label>
                  <input id="u-m${i}-phone" name="member${i}_phone" type="tel" class="u-input" value="${data.phone || ''}" required>
                </div>
              </div>
            `;
            fields.members_wrap.appendChild(block);
          }
        }

        function renderMembersView(members = []) {
          viewAreas.members_wrap.innerHTML = '';
          if (!members || !members.length) {
            const p = document.createElement('p');
            p.className = 'u-hint';
            p.textContent = 'No members added.';
            viewAreas.members_wrap.appendChild(p);
            return;
          }
          members.forEach((m, idx) => {
            const item = document.createElement('div');
            item.className = 'u-item';
            item.innerHTML = `
              <span class="u-key">Member ${String(idx + 1).padStart(2, '0')}</span>
              <span class="u-val">${m.name || '—'}</span>
              <span class="u-hint">NIC: ${m.nic || '—'} | Email: ${m.email || '—'} | Phone: ${m.phone || '—'}</span>
            `;
            viewAreas.members_wrap.appendChild(item);
          });
        }

        function fillView(s) {
          const fmt = (v) => v && String(v).trim() ? v : '—';
          Object.entries(viewAreas).forEach(([key, el]) => {
            if (!el || key === 'members_wrap' || key === 'team_logo_url') return;
            if (key === 'game_titles') {
              el.innerHTML = '';
              (s.game_titles || []).forEach(k => {
                const opt = GAME_OPTIONS.find(o => o.key === k);
                const span = document.createElement('span');
                span.className = 'u-badge';
                span.textContent = opt ? opt.label : k;
                el.appendChild(span);
              });
              if (!(s.game_titles && s.game_titles.length)) {
                const span = document.createElement('span');
                span.className = 'u-hint';
                span.textContent = 'None selected';
                el.appendChild(span);
              }
            } else {
              el.textContent = fmt(s[key]);
            }
          });
          if (viewAreas.team_logo_url) {
            if (s.team_logo_url) {
              viewAreas.team_logo_url.style.display = '';
              viewAreas.team_logo_url.src = s.team_logo_url;
            } else {
              viewAreas.team_logo_url.style.display = 'none';
              viewAreas.team_logo_url.removeAttribute('src');
            }
          }
          renderMembersView(s.members);
        }

        function fillEdit(s) {
          fields.username.value = s.username || '';
          fields.full_name.value = s.full_name || '';
          fields.dob.value = s.dob || '';
          fields.location.value = s.location || '';
          fields.university.value = s.university || '';
          fields.nic.value = s.nic || '';
          fields.mobile.value = s.mobile || '';
          fields.team_name.value = s.team_name || '';
          fields.team_captain.value = s.team_captain || '';
          fields.players_count.value = s.players_count || 1;
          renderGameTitleCheckboxes(s.game_titles || []);
          renderMembersEdit(s.members || [], s.players_count || 1);
          // logo
          if (s.team_logo_url) {
            fields.team_logo_preview.style.display = '';
            fields.team_logo_preview.src = s.team_logo_url;
          } else {
            fields.team_logo_preview.style.display = 'none';
            fields.team_logo_preview.removeAttribute('src');
          }
        }

        function collectEdit() {
          const fd = new FormData(form);
          const players_count = parseInt(fields.players_count.value || '1', 10);
          const game_titles = $$('input[type="checkbox"]', fields.game_titles_wrap).filter(i => i.checked).map(i => i.value);
          const members = [];
          for (let i = 1; i <= players_count; i++) {
            members.push({
                name: fd.get(`member${i}_name`) || '',
                nic: fd.get(`member${i}_nic`) || '',
                email: fd.get(`member${i}_email`) || '',
                phone: fd.get(`member${i}_phone`) || ''
              });
          }
          const updates = {
            username: fields.username.value.trim(),
            // email view-only (not editable)
            full_name: fields.full_name.value.trim(),
            dob: fields.dob.value,
            location: fields.location.value.trim(),
            university: fields.university.value.trim(),
            nic: fields.nic.value.trim(),
            mobile: fields.mobile.value.trim(),
            team_name: fields.team_name.value.trim(),
            team_captain: fields.team_captain.value.trim(),
            players_count,
            game_titles,
            members
          };
          // Handle logo preview (no upload)
          const file = fields.team_logo.files && fields.team_logo.files[0];
          if (file) {
            const url = URL.createObjectURL(file);
            updates.team_logo_url = url; // preview only
          }
          return updates;
        }

        // Events
        editBtn.addEventListener('click', () => {
          setMode('edit');
          fillEdit(state || {});
        });

        [cancelBtn, cancelBtn2].forEach(btn => btn.addEventListener('click', () => {
          setMode('view');
          // reset edit form to state
          fillEdit(state || {});
        }));

        // Top bar Save button submits the form
        saveBtn.addEventListener('click', () => {
          if (!edit.hidden) {
            form.requestSubmit();
          }
        });

        fields.players_count.addEventListener('change', () => {
          renderMembersEdit(state?.members || [], fields.players_count.value);
        });

        fields.team_logo.addEventListener('change', () => {
          const file = fields.team_logo.files && fields.team_logo.files[0];
          if (file) {
            const url = URL.createObjectURL(file);
            fields.team_logo_preview.style.display = '';
            fields.team_logo_preview.src = url;
          }
        });

        form.addEventListener('submit', (e) => {
          e.preventDefault();
          const updates = collectEdit();
          saveBtn.disabled = true;
          saveUser(updates)
            .then((s) => { fillView(s); setMode('view'); })
            .catch(err => { alert(err.message || 'Failed to save'); })
            .finally(() => { saveBtn.disabled = false; });
        });

        // Init
        setMode('view');
        fetchUser().then(s => { state = s; fillView(s); fillEdit(s); });
      })();
    </script>


    <!-- 
      - ionicon link
    -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  </body>

  </html>