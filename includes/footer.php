<footer class="footer">
    <div class="container footer-top">

      <!-- Column 1: Brand -->
      <div class="footer-brand">
        <a href="<?= tmq_escape($assetPrefix) ?>index.php" class="footer-brand-logo">
          <img src="<?= tmq_escape($assetPrefix) ?>assets/img/TutorMeQuran_LOGO_White.png" alt="TutorMeQuran Logo" class="footer-brand-img">
          <div class="footer-brand-text">
            TutorMeQuran
            <span>Verified Quran Tutors</span>
          </div>
        </a>
        <div class="footer-project-logos" aria-label="University and final year project">
          <img src="<?= tmq_escape($assetPrefix) ?>assets/img/LOGO_UiTM_OUTLINE_3(WHITE).png" alt="Universiti Teknologi MARA" class="footer-uitm-logo" loading="lazy">
          <img src="<?= tmq_escape($assetPrefix) ?>assets/img/FinalYearProject_BLACK_logo.png" alt="Final Year Project" class="footer-fyp-logo" loading="lazy">
        </div>
        <p class="footer-desc">
          A centralized, secure digital platform designed to help Muslims in Malaysia search, coordinate, and book verified Quran tutors with verified credentials.
        </p>
<?php if ($homeFooter): ?><div class="footer-socials">
          <a href="#" class="social-link flex-center" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-link flex-center" aria-label="Youtube"><i class="fab fa-youtube"></i></a>
          <a href="#" class="social-link flex-center" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        </div><?php endif; ?>
      </div>

      <!-- Column 2: Navigation links -->
      <div>
        <h4 class="footer-col-title">Quick Links</h4>
        <ul class="footer-links">
          <li><a href="<?= tmq_escape($assetPrefix) ?>index.php">Home</a>
</li>
          <li><a href="<?= tmq_escape($assetPrefix) ?>tutors.php">Browse</a>
</li>
          <li><a href="<?= tmq_escape($assetPrefix) ?>why-us.php">Why Us</a>
</li>
          <li><a href="<?= tmq_escape($assetPrefix) ?>help.php">Help</a>
</li>
        </ul>
      </div>

      <!-- Column 3: Rules & Legal -->
      <div><h4 class="footer-col-title">Support</h4><ul class="footer-links"><li><a href="<?= tmq_escape($footerHelp) ?>">Help Centre</a></li><li><a href="<?= tmq_escape($footerFaq) ?>">FAQ</a></li></ul></div>

      <!-- Column 4: Contact details -->
      <div>
        <h4 class="footer-col-title">Get In Touch</h4>
        <div class="footer-contact-info">
          <div class="contact-item">
            <i class="fas fa-map-marker-alt"></i>
            <p>UiTM Cawangan Kelantan, Kampus Machang, 18500 Machang, Kelantan, Malaysia</p>
          </div>
          <div class="contact-item">
            <i class="fas fa-phone-alt"></i>
            <p>+60 16-262 4834</p>
          </div>
          <div class="contact-item">
            <i class="fas fa-envelope"></i>
            <p>support@tutormequran.com.my</p>
          </div>
        </div>
      </div>

    </div>

    <!-- Footer Bottom -->
    <div class="container footer-bottom">
      <p>&copy; 2026 TutorMeQuran. <?= $homeFooter ? 'Built for CSP600 Project, Malaysia. All Rights Reserved. Interface prototype.' : 'CSP600 Final Year Project · Malaysia. Interface prototype.' ?></p>
    </div>
  </footer>