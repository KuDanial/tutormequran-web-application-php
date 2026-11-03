<?php
require_once __DIR__ . '/includes/config.php';
tmq_session_start();
$authMessage = '';
try {
    if ($existing = tmq_current_user()) { tmq_redirect(tmq_dashboard($existing)); }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        tmq_check_csrf();
        tmq_register($_POST);
        $_SESSION['registration_success'] = true;
        tmq_redirect('login.php');
    }
} catch (DomainException $error) {
    if (http_response_code() !== 403) { http_response_code(422); }
    $authMessage = $error->getMessage();
} catch (Throwable $error) {
    http_response_code(503);
    $authMessage = 'Registration is temporarily unavailable. Please try again later.';
}
$assetPrefix = '';
$pageTitle = 'Begin your Quran learning journey - TutorMeQuran';
$pageDescription = 'Create a student account or apply to teach. For adults aged 18 and above in Malaysia.';
$homeFooter = false;
$footerHelp = 'help.php';
$footerFaq = 'chatbot.php';
?>
<?php require __DIR__ . '/includes/header.php'; ?><body class="portal-page public-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/includes/public-navbar.php'; ?>
<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">TutorMeQuran</span>
<h1>Begin your Quran learning journey</h1>
<p>Create a student account or apply to teach. For adults aged 18 and above in Malaysia.</p>
</div><a href="login.php" class="btn btn-outline btn-sm">Already registered? Log in</a>
</header>
<?php require __DIR__ . '/includes/auth-message.php'; ?>
<div class="registration-layout">
<nav class="registration-tabs" aria-label="Registration type"><a href="#student-registration">I want to learn</a><a href="#tutor-application">I want to teach</a>
</nav>
<section id="tutor-application" class="portal-card registration-panel">
<h2>Apply to become a tutor</h2>
<section class="booking-progress application-progress" aria-label="Tutor application progress">
<ol class="booking-journey">
<li class="current" aria-current="step"><span class="booking-step"><span class="booking-step-dot" aria-hidden="true">1</span><span class="booking-step-label">Apply</span></span></li>
<li><span class="booking-step"><span class="booking-step-dot" aria-hidden="true">2</span><span class="booking-step-label">Admin verification</span></span></li>
</ol>
<p class="booking-current-label">Create your account to begin</p>
</section>
<aside class="flow-notice info"><strong>Your teaching journey starts with verification</strong>
<p>Create your account with your personal details. After logging in, submit your certificate and complete your teaching profile. Your profile stays private until the required approval and setup.</p>
</aside>
<form class="portal-form" method="post" data-auth-form>
<?= tmq_csrf_field() ?>
<input type="hidden" name="accountRoleType" value="Tutor">
<div class="portal-fields">
<div class="form-group">
<label for="f-tutorFName" class="form-label">First Name</label>
<div class="form-input-wrapper"><i class="fas fa-user input-icon" aria-hidden="true"></i>
<input id="f-tutorFName" name="tutorFName" class="form-control" placeholder="e.g. Ahmad" required type="text" value="<?= tmq_escape(strtolower(tmq_input($_POST, 'accountRoleType')) === 'tutor' ? tmq_input($_POST, 'tutorFName') : '') ?>">
</div>
</div>
<div class="form-group">
<label for="f-tutorLName" class="form-label">Last Name</label>
<div class="form-input-wrapper"><i class="fas fa-user input-icon" aria-hidden="true"></i>
<input id="f-tutorLName" name="tutorLName" class="form-control" placeholder="e.g. Hakimi" required type="text" value="<?= tmq_escape(strtolower(tmq_input($_POST, 'accountRoleType')) === 'tutor' ? tmq_input($_POST, 'tutorLName') : '') ?>">
</div>
</div>
<div class="form-group">
<label for="f-tutorICNum" class="form-label">IC Number</label>
<div class="form-input-wrapper"><i class="fas fa-id-card input-icon" aria-hidden="true"></i>
<input id="f-tutorICNum" name="tutorICNum" class="form-control" placeholder="e.g. 000101-03-1234" required type="text" value="<?= tmq_escape(strtolower(tmq_input($_POST, 'accountRoleType')) === 'tutor' ? tmq_input($_POST, 'tutorICNum') : '') ?>">
</div>
</div>
<div class="form-group">
<label for="f-tutorPhone" class="form-label">Phone Number</label>
<div class="form-input-wrapper"><i class="fas fa-phone input-icon" aria-hidden="true"></i>
<input id="f-tutorPhone" name="tutorPhone" class="form-control" placeholder="e.g. 016-262 4834" required type="tel" value="<?= tmq_escape(strtolower(tmq_input($_POST, 'accountRoleType')) === 'tutor' ? tmq_input($_POST, 'tutorPhone') : '') ?>">
</div>
</div>
<div class="form-group">
<label for="f-tutorEmail" class="form-label">Email</label>
<div class="form-input-wrapper"><i class="fas fa-envelope input-icon" aria-hidden="true"></i>
<input id="f-tutorEmail" name="tutorEmail" class="form-control" placeholder="e.g. name@example.com" required type="email" value="<?= tmq_escape(strtolower(tmq_input($_POST, 'accountRoleType')) === 'tutor' ? tmq_input($_POST, 'tutorEmail') : '') ?>">
</div>
</div>
<div class="form-group"><label class="form-label" for="x-tutorGender">Gender</label><select id="x-tutorGender" name="tutorGender" class="form-control"><option>Select gender</option><option>Male</option><option>Female</option><option>Prefer not to say</option></select></div><div class="form-group"><label class="form-label" for="x-tutorDateOfBirth">Date of Birth</label><input id="x-tutorDateOfBirth" name="tutorDateOfBirth" class="form-control" type="date" value="<?= tmq_escape(strtolower(tmq_input($_POST, 'accountRoleType')) === 'tutor' ? tmq_input($_POST, 'tutorDateOfBirth') : '') ?>"></div>
<div class="form-group">
<label for="f-tutorAddress" class="form-label">Address in Malaysia</label>
<div class="form-input-wrapper"><i class="fas fa-location-dot input-icon" aria-hidden="true"></i>
<textarea id="f-tutorAddress" name="tutorAddress" class="form-control" placeholder="House number, street, postcode, city and state" required rows="4"></textarea>
</div>
</div>
<div class="form-group">
<label for="f-tutorAccountUsername" class="form-label">Username</label>
<div class="form-input-wrapper"><i class="fas fa-user-circle input-icon" aria-hidden="true"></i>
<input id="f-tutorAccountUsername" name="accountUsername" class="form-control" placeholder="Choose a username" required type="text" value="<?= tmq_escape(strtolower(tmq_input($_POST, 'accountRoleType')) === 'tutor' ? tmq_input($_POST, 'accountUsername') : '') ?>">
</div>
</div>
<div class="form-group">
<label for="f-tutorAccountPassword" class="form-label">Password</label>
<div class="form-input-wrapper"><i class="fas fa-lock input-icon" aria-hidden="true"></i>
<input id="f-tutorAccountPassword" name="accountPassword" class="form-control" placeholder="Create a password" minlength="8" maxlength="72" autocomplete="new-password" required type="password" value=""><small>8?72 bytes, including a letter and a number.</small>
</div>
</div>
<div class="form-group"><label for="f-tutorConfirmPassword" class="form-label">Confirm Password</label><div class="form-input-wrapper"><i class="fas fa-lock input-icon" aria-hidden="true"></i><input id="f-tutorConfirmPassword" name="confirmPassword" class="form-control" type="password" autocomplete="new-password" required minlength="8" maxlength="72" placeholder="Repeat your password"></div></div>
<div class="form-group">
<label for="f-certificateName" class="form-label">Certificate Name</label>
<div class="form-input-wrapper"><i class="fas fa-certificate input-icon" aria-hidden="true"></i>
<input id="f-certificateName" name="certificateName" class="form-control" placeholder="e.g. Ijazah in Quranic Recitation" type="text" value="" disabled>
</div>
</div>
<div class="form-group">
<label for="f-certificateYear" class="form-label">Certificate Year</label>
<div class="form-input-wrapper"><i class="fas fa-calendar input-icon" aria-hidden="true"></i>
<input id="f-certificateYear" name="certificateYear" class="form-control" placeholder="e.g. 2024" type="number" value="" min="1900" max="2026" step="1" disabled>
</div>
</div>
<div class="form-group">
<label for="f-certificateDescription" class="form-label">Certificate Description</label>
<div class="form-input-wrapper"><i class="fas fa-file-lines input-icon" aria-hidden="true"></i>
<textarea id="f-certificateDescription" name="certificateDescription" class="form-control" placeholder="Describe your qualification and issuing institution" rows="4" disabled></textarea>
</div>
</div>
<div class="form-group">
<label for="f-certificateFile" class="form-label">Upload Ijazah Certificate</label>
<div class="form-input-wrapper"><i class="fas fa-file-arrow-up input-icon" aria-hidden="true"></i>
<input id="f-certificateFile" name="certificateFile" class="form-control" type="file" accept=".pdf,.png,.jpg,.jpeg" disabled>
</div>
<small>Upload your certificate from your Tutor account after registration. PDF, JPG or PNG, up to 5 MB.</small>
</div>
<div class="form-group">
<label for="f-certificateOrigin" class="form-label">Issuing State / Origin</label>
<select id="f-certificateOrigin" name="certificateOrigin" class="form-control" disabled>
<option value="" selected disabled>Select state or origin</option>
<option>Johor</option><option>Kedah</option><option>Kelantan</option><option>Melaka</option><option>Negeri Sembilan</option><option>Pahang</option><option>Penang</option><option>Perak</option><option>Perlis</option><option>Sabah</option><option>Sarawak</option><option>Selangor</option><option>Terengganu</option><option>Federal Territory of Kuala Lumpur</option><option>Federal Territory of Labuan</option><option>Federal Territory of Putrajaya</option><option>Others (Outside Malaysia)</option>
</select>
</div>
</div>
<label class="eligibility">
<input type="checkbox" name="tutorEligibility" required> I confirm that I am 18 or older and reside in Malaysia.</label>
<div class="portal-actions">
<button type="submit" class="btn btn-primary">Create Tutor Account</button>
</div>
</form>
<p class="prototype-link"><a href="tutor/tutor-dashboard.php" class="btn btn-text btn-sm">View application status</a>
</p>
</section>
<section id="student-registration" class="portal-card registration-panel">
<h2>Create a student account</h2>
<form class="portal-form" method="post" data-auth-form>
<?= tmq_csrf_field() ?>
<input type="hidden" name="accountRoleType" value="Student">
<div class="portal-fields">
<div class="form-group">
<label for="f-studFName" class="form-label">First Name</label>
<div class="form-input-wrapper"><i class="fas fa-user input-icon" aria-hidden="true"></i>
<input id="f-studFName" name="studFName" class="form-control" placeholder="e.g. Ahmad" required type="text" value="<?= tmq_escape(strtolower(tmq_input($_POST, 'accountRoleType')) === 'student' ? tmq_input($_POST, 'studFName') : '') ?>">
</div>
</div>
<div class="form-group">
<label for="f-studLName" class="form-label">Last Name</label>
<div class="form-input-wrapper"><i class="fas fa-user input-icon" aria-hidden="true"></i>
<input id="f-studLName" name="studLName" class="form-control" placeholder="e.g. Hakimi" required type="text" value="<?= tmq_escape(strtolower(tmq_input($_POST, 'accountRoleType')) === 'student' ? tmq_input($_POST, 'studLName') : '') ?>">
</div>
</div>
<div class="form-group">
<label for="f-studICNum" class="form-label">IC Number</label>
<div class="form-input-wrapper"><i class="fas fa-id-card input-icon" aria-hidden="true"></i>
<input id="f-studICNum" name="studICNum" class="form-control" placeholder="e.g. 000101-03-1234" required type="text" value="<?= tmq_escape(strtolower(tmq_input($_POST, 'accountRoleType')) === 'student' ? tmq_input($_POST, 'studICNum') : '') ?>">
</div>
</div>
<div class="form-group">
<label for="f-studPhone" class="form-label">Phone Number</label>
<div class="form-input-wrapper"><i class="fas fa-phone input-icon" aria-hidden="true"></i>
<input id="f-studPhone" name="studPhone" class="form-control" placeholder="e.g. 016-262 4834" required type="tel" value="<?= tmq_escape(strtolower(tmq_input($_POST, 'accountRoleType')) === 'student' ? tmq_input($_POST, 'studPhone') : '') ?>">
</div>
</div>
<div class="form-group">
<label for="f-studEmail" class="form-label">Email</label>
<div class="form-input-wrapper"><i class="fas fa-envelope input-icon" aria-hidden="true"></i>
<input id="f-studEmail" name="studEmail" class="form-control" placeholder="e.g. name@example.com" required type="email" value="<?= tmq_escape(strtolower(tmq_input($_POST, 'accountRoleType')) === 'student' ? tmq_input($_POST, 'studEmail') : '') ?>">
</div>
</div>
<div class="form-group"><label class="form-label" for="x-studentGender">Gender</label><select id="x-studentGender" name="studentGender" class="form-control"><option>Select gender</option><option>Male</option><option>Female</option><option>Prefer not to say</option></select></div><div class="form-group"><label class="form-label" for="x-studentDateOfBirth">Date of Birth</label><input id="x-studentDateOfBirth" name="studentDateOfBirth" class="form-control" type="date" value="<?= tmq_escape(strtolower(tmq_input($_POST, 'accountRoleType')) === 'student' ? tmq_input($_POST, 'studentDateOfBirth') : '') ?>"></div>
<div class="form-group">
<label for="f-studAddress" class="form-label">Address in Malaysia</label>
<div class="form-input-wrapper"><i class="fas fa-location-dot input-icon" aria-hidden="true"></i>
<textarea id="f-studAddress" name="studAddress" class="form-control" placeholder="House number, street, postcode, city and state" required rows="4"></textarea>
</div>
</div>
<div class="form-group">
<label for="f-studAccountUsername" class="form-label">Username</label>
<div class="form-input-wrapper"><i class="fas fa-user-circle input-icon" aria-hidden="true"></i>
<input id="f-studAccountUsername" name="accountUsername" class="form-control" placeholder="Choose a username" required type="text" value="<?= tmq_escape(strtolower(tmq_input($_POST, 'accountRoleType')) === 'student' ? tmq_input($_POST, 'accountUsername') : '') ?>">
</div>
</div>
<div class="form-group">
<label for="f-studAccountPassword" class="form-label">Password</label>
<div class="form-input-wrapper"><i class="fas fa-lock input-icon" aria-hidden="true"></i>
<input id="f-studAccountPassword" name="accountPassword" class="form-control" placeholder="Create a password" minlength="8" maxlength="72" autocomplete="new-password" required type="password" value=""><small>8?72 bytes, including a letter and a number.</small>
</div>
</div>
<div class="form-group"><label for="f-studConfirmPassword" class="form-label">Confirm Password</label><div class="form-input-wrapper"><i class="fas fa-lock input-icon" aria-hidden="true"></i><input id="f-studConfirmPassword" name="confirmPassword" class="form-control" type="password" autocomplete="new-password" required minlength="8" maxlength="72" placeholder="Repeat your password"></div></div>
</div>
<label class="eligibility">
<input type="checkbox" name="studEligibility" required> I confirm that I am 18 or older and reside in Malaysia.</label>
<div class="portal-actions">
<button type="submit" class="btn btn-primary">Create Student Account</button>
</div>
</form>
</section>
</div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/registration.js"></script>
</body></html>
