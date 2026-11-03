<?php
// Included before output only by the three existing account/password pages.
$authMessage = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        tmq_check_csrf();
        tmq_change_password($authUser, $_POST);
        $_SESSION['password_success'] = true;
        $target = ['student'=>'student/student-profile-edit.php','tutor'=>'tutor/tutor-account-edit.php','admin'=>'admin/admin-profile.php'][$authUser['role']];
        tmq_redirect($target . '#change-password');
    } catch (DomainException $error) {
        if (http_response_code() !== 403) { http_response_code(422); }
        $authMessage = $error->getMessage();
    } catch (Throwable $error) {
        http_response_code(503);
        $authMessage = 'Password changes are temporarily unavailable. Please try again later.';
    }
}
if (!empty($_SESSION['password_success'])) {
    unset($_SESSION['password_success']);
    $authSuccess = true;
    $authMessage = 'Password updated. Other signed-in sessions will need to log in again.';
}
