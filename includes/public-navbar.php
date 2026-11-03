<nav class="navbar" id="navbar">
    <div class="container nav-container">
      <a href="<?= tmq_escape($assetPrefix) ?>index.php" class="logo-link" id="nav-logo">
        <img src="<?= tmq_escape($assetPrefix) ?>assets/img/TutorMeQuran_LOGO.png" alt="TutorMeQuran Logo" class="logo-img">
        <div class="logo-text">
          TutorMeQuran
          <span>Verified Quran Tutors</span>
        </div>
      </a>

      <!-- Desktop Links -->
      <ul class="nav-links">
        <li><a href="<?= tmq_escape($assetPrefix) ?>index.php" class="nav-link">Home</a>
</li>
        <li><a href="<?= tmq_escape($assetPrefix) ?>tutors.php" class="nav-link">Browse</a>
</li>
        <li><a href="<?= tmq_escape($assetPrefix) ?>why-us.php" class="nav-link">Why Us</a>
</li>
        <li><a href="<?= tmq_escape($assetPrefix) ?>help.php" class="nav-link">Help</a>
</li>
      </ul>

      <!-- Action Buttons -->
      <details class="portal-mobile"><summary>Menu</summary>
<ul>
<li><a href="<?= tmq_escape($assetPrefix) ?>index.php">Home</a>
</li>
<li><a href="<?= tmq_escape($assetPrefix) ?>tutors.php">Browse</a>
</li>
<li><a href="<?= tmq_escape($assetPrefix) ?>why-us.php">Why Us</a>
</li>
<li><a href="<?= tmq_escape($assetPrefix) ?>help.php">Help</a>
</li>
<li><a href="<?= tmq_escape($assetPrefix) ?>chatbot.php">FAQ Chat</a>
</li>
<li><a href="<?= tmq_escape($assetPrefix) ?>login.php">Log In</a>
</li>
<li><a href="<?= tmq_escape($assetPrefix) ?>register.php">Register</a>
</li>
</ul></details>
<div class="nav-actions">
        <a href="<?= tmq_escape($assetPrefix) ?>login.php" class="btn btn-text btn-sm" id="nav-login-btn">Log In</a>
        <a href="<?= tmq_escape($assetPrefix) ?>register.php" class="btn btn-primary btn-sm" id="nav-register-btn">Register</a>

      </div>
    </div>
  </nav>