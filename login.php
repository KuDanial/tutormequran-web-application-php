<?php
require_once __DIR__ . '/includes/config.php';
tmq_session_start();
$authMessage = '';
try {
    if ($existing = tmq_current_user()) { tmq_redirect(tmq_dashboard($existing)); }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        tmq_check_csrf();
        $user = tmq_login(tmq_input($_POST, 'accountUsername'), tmq_input($_POST, 'accountPassword'));
        tmq_redirect(tmq_dashboard($user));
    }
} catch (DomainException $error) {
    if (http_response_code() !== 403) { http_response_code(422); }
    $authMessage = $error->getMessage();
} catch (Throwable $error) {
    http_response_code(503);
    $authMessage = 'Sign-in is temporarily unavailable. Please try again later.';
}
if (!empty($_SESSION['registration_success'])) {
    unset($_SESSION['registration_success']);
    $authSuccess = true;
    $authMessage = 'Your account has been created. Log in to continue.';
}
$assetPrefix = '';
$pageTitle = 'Log In - TutorMeQuran';
$pageDescription = 'Secure login to your TutorMeQuran account. Access your personalized dashboard for booking classes, verifying certificates, or supervising the platform.';
?>
<?php require __DIR__ . '/includes/header.php'; ?>
<body class="portal-page">

  <!-- Full Page Center Auth Wrapper -->
  <main class="auth-page">
    <div class="auth-card">

      <!-- Logo and Header -->
      <div class="auth-logo-wrapper">
        <a href="index.php">
          <img src="assets/img/TutorMeQuran_LOGO.png" alt="TutorMeQuran Logo" class="auth-logo">
        </a>
        <h1 class="auth-title">Welcome Back</h1>
        <p class="auth-subtitle">Sign in with your username and password</p>
      </div>

      <!-- Form -->
      <?php require __DIR__ . '/includes/auth-message.php'; ?>
      <form id="login-form" method="post" action="login.php" data-auth-form>
        <?= tmq_csrf_field() ?>
        <div class="form-group"><label class="form-label" for="login-role-choice">Account role</label><select id="login-role-choice" name="accountRoleType" class="form-control"><option value="student" selected>Student</option><option value="tutor">Tutor</option><option value="admin">Admin</option></select></div>
        <input type="hidden" id="login-role" value="student"><p class="field-help">Your account determines your dashboard and access.</p>
        <!-- Username Input -->
        <div class="form-group">
          <label for="username" class="form-label">Username</label>
          <div class="form-input-wrapper">
            <i class="fas fa-user input-icon" aria-hidden="true"></i>
            <input type="text" id="username" name="accountUsername" value="<?= tmq_escape(tmq_input($_POST, 'accountUsername')) ?>" maxlength="80" autocomplete="username" class="form-control" placeholder="Enter your username" required>
          </div>
        </div>

        <!-- Password Input -->
        <div class="form-group" style="margin-bottom: 1rem;">
          <label for="password" class="form-label">Password</label>
          <div class="form-input-wrapper">
            <i class="fas fa-lock input-icon" aria-hidden="true"></i>
            <input type="password" id="password" name="accountPassword" autocomplete="current-password" maxlength="72" class="form-control" placeholder="Enter your password" required>

          </div>
        </div>

        <!-- Checkbox & Forgot link -->
        <div class="form-group flex-between" style="margin-bottom: 2rem;">
          <div class="form-checkbox-wrapper">
            <input type="checkbox" id="remember-me" class="form-checkbox" disabled title="Persistent sign-in is not available yet">
            <label for="remember-me" class="checkbox-label">Remember me</label>
          </div>
          <a href="help.php" class="checkbox-label" style="color: var(--primary); font-weight: 600;">Forgot Password?</a>
        </div>

        <!-- Login Button -->
        <button type="submit" class="btn btn-primary btn-block" id="login-submit-btn">
          Log In <i class="fas fa-sign-in-alt"></i>
        </button>
      </form>

      <!-- Onboarding Link -->
      <p class="checkbox-label" style="text-align: center; margin-top: 2rem;">
        Don't have an account yet? <a href="register.php">Sign Up (Register)</a>
      </p>

      <!-- Back Link -->
      <a href="index.php" class="btn btn-text btn-block btn-sm" style="margin-top: 1rem;">
        <i class="fas fa-arrow-left"></i> Back to Landing Page
      </a>

    </div>
  </main>

  <!-- App Script -->
  <script>
    const roleChoice = document.getElementById('login-role-choice');
    const roleValue = document.getElementById('login-role');
    roleChoice.addEventListener('change', () => { roleValue.value = roleChoice.value; });
  </script>

</body>
</html>
