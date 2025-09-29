<?php require_once __DIR__ . '/../includes/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- 
    - primary meta tags
  -->
  <title>Contact | Nebula Esports 2025</title>
  <meta name="title" content="Nebula Esports 2025 - BY Nebula Esports">
  <meta name="description" content="Nebula Esports 2025 is your gateway to the latest tournaments, gaming news, and team updates. Join our community and experience epic matches, headlines, and more.">

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
    - custom css link
  -->
  <link rel="stylesheet" href="../assets/css/style.css">

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
        <img src="../assets/images/nebula-esports.png" style ="width:80px; height:auto;" alt="unigine home">
      </a>

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
    -Contact
  -->

  <section class="section contact" aria-labelledby="contact-title">
    <div class="container">
      <p class="section-subtitle" data-reveal="bottom">&nbsp;</p>
      <p class="section-subtitle" data-reveal="bottom">&nbsp;</p>
      <p class="section-subtitle" data-reveal="bottom">Get In Touch</p>
      <h2 class="h2 section-title" id="contact-title" data-reveal="bottom">Contact <span class="span">Us</span></h2>
      <p class="section-text" data-reveal="bottom">
        Have questions about tournaments, teams, or partnerships? Drop us a message and we’ll respond ASAP.
      </p>

      <div class="contact-content">
        <div class="contact-info" data-reveal="left">
          <ul>
            <li class="contact-item">
              <span class="span">Location:</span>
              <address class="contact-link">
                Nebula Institute of Technology <br>
                Negombo Road, <br>
                Welisara.
              </address>
            </li>

            <li class="contact-item">
              <span class="span">Email:</span>
              <a class="contact-link" href="mailto:info@sevinda-herath.is-a.dev">info@sevinda-herath.is-a.dev</a>
            </li>

            <li class="contact-item">
              <span class="span">Phone:</span>
              <a class="contact-link" href="tel:+940112162162">+94 (011) 216-2162</a>
            </li>

            <li class="contact-item">
              <span class="span">Social:</span>
              <div class="social-wrapper">
                <a href="#" class="social-link" aria-label="Facebook">
                  <ion-icon name="logo-facebook"></ion-icon>
                </a>
                <a href="#" class="social-link" aria-label="Twitter">
                  <ion-icon name="logo-twitter"></ion-icon>
                </a>
                <a href="#" class="social-link" aria-label="Instagram">
                  <ion-icon name="logo-instagram"></ion-icon>
                </a>
                <a href="#" class="social-link" aria-label="YouTube">
                  <ion-icon name="logo-youtube"></ion-icon>
                </a>
              </div>
            </li>
          </ul>
        </div>

        <form class="contact-form" action="/php/contact-submit.php" method="post" data-reveal="right">
          <?= csrf_field() ?>
          <input class="input-field" type="text" name="full_name" placeholder="Your Name" autocomplete="name" required>
          <input class="input-field" type="email" name="email" placeholder="Your Email" autocomplete="email" required>
          <input class="input-field" type="text" name="subject" autocomplete="off" placeholder="Subject">
          <textarea class="input-field" name="message" placeholder="Your Message" rows="6" autocomplete="off" required></textarea>
          <button type="submit" class="btn" data-btn>Send Message</button>
          <br>
          <?php if ($msg = get_flash('contact')): ?>
            <p class="section-text" style="margin-top:10px;">
              <?= h($msg) ?>
            </p>
          <?php endif; ?>
        </form>

      </div>
    </div>
  </section>


  <!--
    - MAP
  -->

  <section class="section map" aria-labelledby="map-title">
    <div class="container">
      <p class="section-subtitle" data-reveal="bottom">Find Us</p>
      <h2 class="h2 section-title" id="map-title" data-reveal="bottom">Our <span class="span">Location</span></h2>

      <div class="map-wrapper" data-reveal="bottom">
        <iframe
          class="map-embed"
          title="Nebula Institute of Technology Location"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          src="https://www.google.com/maps?q=Nebula+Institute+of+Technology+Welisara&output=embed">
        </iframe>
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