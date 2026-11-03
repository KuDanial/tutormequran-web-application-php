<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function tmq_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) { return; }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('TMQSESSID');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => tmq_base_path() . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    if (!@session_start()) {
        http_response_code(503);
        exit('Account sessions are temporarily unavailable. Please try again later.');
    }
}

function tmq_base_path(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $path = rtrim(str_replace('\\', '/', dirname($script)), '/.');
    if (in_array(basename($path), ['student', 'tutor', 'admin', 'report'], true)) {
        $path = rtrim(str_replace('\\', '/', dirname($path)), '/.');
    }
    return str_starts_with($path, '//') || preg_match('/[\r\n]/', $path) ? '' : $path;
}

function tmq_url(string $page): string { return tmq_base_path() . '/' . ltrim($page, '/'); }
function tmq_redirect(string $page): never
{
    header('Location: ' . tmq_url($page), true, 303);
    exit;
}
function tmq_input(array $data, string $key): string
{
    return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : '';
}
function tmq_csrf_token(): string
{
    tmq_session_start();
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function tmq_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . tmq_csrf_token() . '">';
}
function tmq_check_csrf(): void
{
    $token = tmq_input($_POST, 'csrf_token');
    if ($token === '' || !hash_equals(tmq_csrf_token(), $token)) {
        http_response_code(403);
        throw new DomainException('Your form expired. Reload this page and try again.');
    }
}
function tmq_destroy_session(): void
{
    tmq_session_start();
    $_SESSION = [];
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $cookie['path'],
        'secure' => $cookie['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    session_destroy();
}
function tmq_current_user(): ?array
{
    tmq_session_start();
    if (empty($_SESSION['auth']['user_id'])) { return null; }
    static $loaded = false, $user = null;
    if ($loaded) { return $user; }
    $loaded = true;
    $query = tmq_db()->prepare('SELECT user_id, role, username, first_name, last_name, account_status, password_changed_at FROM users WHERE user_id = ?');
    $query->execute([$_SESSION['auth']['user_id']]);
    $row = $query->fetch();
    if (!$row || $row['account_status'] !== 'active'
        || $row['role'] !== ($_SESSION['auth']['role'] ?? null)
        || $row['password_changed_at'] !== ($_SESSION['auth']['password_version'] ?? null)) {
        unset($_SESSION['auth']);
        session_regenerate_id(true);
        unset($_SESSION['csrf']);
        return null;
    }
    return $user = $row;
}
function tmq_tutor_approved(int $userId): bool
{
    $query = tmq_db()->prepare("SELECT 1 FROM tutors t JOIN certificates c ON c.tutor_id=t.tutor_id WHERE t.user_id=? AND c.status='approved' LIMIT 1");
    $query->execute([$userId]);
    return (bool) $query->fetchColumn();
}
function tmq_dashboard(array $user): string
{
    return match ($user['role']) {
        'admin' => 'admin/admin-dashboard.php',
        'tutor' => tmq_tutor_approved((int)$user['user_id']) ? 'tutor/tutor-dashboard-verified.php' : 'tutor/tutor-dashboard.php',
        default => 'student/student-dashboard.php',
    };
}
function tmq_require_role(string $role): array
{
    $user = tmq_current_user();
    if (!$user) { tmq_redirect('login.php'); }
    if ($user['role'] !== $role) {
        http_response_code(403);
        exit('Access denied. This page is not available for your account role.');
    }
    header('Cache-Control: no-store');
    return $user;
}
function tmq_bootstrap_auth(): void
{
    // Use the executed file's directory, never a submitted role or query parameter.
    $path = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '';
    $root = realpath(__DIR__ . '/..');
    if (!$path || !$root || !str_starts_with($path, $root . DIRECTORY_SEPARATOR)) { return; }
    $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));
    $directory = explode('/', $relative)[0];
    $role = ['student'=>'student', 'tutor'=>'tutor', 'admin'=>'admin', 'report'=>'admin'][$directory] ?? null;
    if ($role !== null && str_ends_with($path, '.php')) {
        try { $GLOBALS['authUser'] = tmq_require_role($role); }
        catch (Throwable $error) { http_response_code(503); exit('Account access is temporarily unavailable. Please try again later.'); }
    }
}
function tmq_password_error(string $password): ?string
{
    if (strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")
        || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return 'Use 8–72 bytes for your password, including at least one letter and one number.';
    }
    return null;
}
function tmq_validate_account(array $data): array
{
    $data['username'] = strtolower(trim($data['username']));
    $data['email'] = strtolower(trim($data['email']));
    if (!preg_match('/\A[a-z0-9_.-]{3,80}\z/', $data['username'])) {
        throw new DomainException('Username must contain 3–80 letters, numbers, dots, underscores or hyphens.');
    }
    if (strlen($data['email']) > 254 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new DomainException('Enter a valid email address.');
    }
    foreach (['first_name', 'last_name'] as $key) {
        $data[$key] = trim($data[$key]);
        if (!preg_match('/\A.{1,100}\z/us', $data[$key])) { throw new DomainException('First and last names are required (maximum 100 characters each).'); }
    }
    if ($error = tmq_password_error($data['password'])) { throw new DomainException($error); }
    return $data;
}
function tmq_create_account(array $data, bool $allowAdmin = false): int
{
    $data = tmq_validate_account($data);
    $allowed = $allowAdmin && PHP_SAPI === 'cli' ? ['admin'] : ['student', 'tutor'];
    if (!in_array($data['role'], $allowed, true)) { throw new DomainException('Choose Student or Tutor registration.'); }
    $db = tmq_db();
    try {
        $db->beginTransaction();
        $query = $db->prepare('INSERT INTO users (role, username, email, password_hash, first_name, last_name, ic_number, phone, gender, date_of_birth, address, eligibility_confirmed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $query->execute([$data['role'], $data['username'], $data['email'], password_hash($data['password'], PASSWORD_DEFAULT),
            $data['first_name'], $data['last_name'], $data['ic_number'] ?? null, $data['phone'] ?? null,
            $data['gender'] ?? null, $data['date_of_birth'] ?? null, $data['address'] ?? null, $data['eligibility_confirmed_at'] ?? null]);
        $id = (int)$db->lastInsertId();
        $table = ['student'=>'students','tutor'=>'tutors','admin'=>'admins'][$data['role']];
        $db->prepare("INSERT INTO $table (user_id) VALUES (?)")->execute([$id]);
        $db->commit();
        return $id;
    } catch (Throwable $error) {
        if ($db->inTransaction()) { $db->rollBack(); }
        if ($error instanceof PDOException && (int)($error->errorInfo[1] ?? 0) === 1062) {
            throw new DomainException('That username or email is already registered. Please choose another or log in.');
        }
        throw $error;
    }
}
function tmq_register(array $post): int
{
    $role = strtolower(trim(tmq_input($post, 'accountRoleType')));
    if (!in_array($role, ['student', 'tutor'], true)) { throw new DomainException('Choose Student or Tutor registration.'); }
    $prefix = $role === 'student' ? 'stud' : 'tutor';
    $data = ['role'=>$role, 'username'=>tmq_input($post,'accountUsername'), 'password'=>tmq_input($post,'accountPassword'),
        'email'=>tmq_input($post,$prefix.'Email'), 'first_name'=>tmq_input($post,$prefix.'FName'), 'last_name'=>tmq_input($post,$prefix.'LName')];
    if ($data['password'] !== tmq_input($post,'confirmPassword')) { throw new DomainException('Passwords do not match.'); }
    $data['ic_number'] = trim(tmq_input($post,$prefix.'ICNum'));
    $data['phone'] = trim(tmq_input($post,$prefix.'Phone'));
    $data['address'] = trim(tmq_input($post,$prefix.'Address'));
    if (!preg_match('/\A[0-9]{6}-?[0-9]{2}-?[0-9]{4}\z/', $data['ic_number'])) { throw new DomainException('Enter your IC number as XXXXXX-XX-XXXX or 12 digits.'); }
    if (!preg_match('/\A[+0-9() .-]{6,32}\z/', $data['phone'])) { throw new DomainException('Enter a valid phone number.'); }
    if ($data['address'] === '' || strlen($data['address']) > 5000) { throw new DomainException('Enter your address in Malaysia (maximum 5000 bytes).'); }
    if (tmq_input($post,$prefix.'Eligibility') !== 'on') { throw new DomainException('Confirm that you are 18 or older and reside in Malaysia.'); }
    $gender = strtolower(str_replace(' ', '_', trim(tmq_input($post, $role.'Gender'))));
    if ($gender === 'select_gender') { $gender = ''; }
    if (!in_array($gender, ['', 'male','female','prefer_not_to_say'], true)) { throw new DomainException('Select a valid gender.'); }
    $data['gender'] = $gender ?: null;
    $dob = tmq_input($post,$role.'DateOfBirth');
    if ($dob !== '' && !preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $dob)) { throw new DomainException('Enter a valid date of birth.'); }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dob, new DateTimeZone('Asia/Kuala_Lumpur'));
    if ($dob !== '' && (!$date || $date->format('Y-m-d') !== $dob || $date > new DateTimeImmutable('-18 years', new DateTimeZone('Asia/Kuala_Lumpur')))) {
        throw new DomainException('Enter a valid date of birth. You must be at least 18 years old.');
    }
    $data['date_of_birth'] = $dob ?: null;
    $data['eligibility_confirmed_at'] = gmdate('Y-m-d H:i:s');
    return tmq_create_account($data);
}
function tmq_login(string $username, string $password): array
{
    tmq_session_start();
    $query = tmq_db()->prepare('SELECT user_id, role, username, password_hash, password_changed_at, account_status FROM users WHERE username = ? LIMIT 1');
    $query->execute([strtolower(trim($username))]);
    $user = $query->fetch();
    // Fixed valid dummy hash keeps unknown-account password checks on the same path.
    $hash = $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    $valid = strlen($password) <= 72 && !str_contains($password, "\0") && password_verify($password, $hash);
    if (!$user || !$valid || $user['account_status'] !== 'active') { throw new DomainException('Username or password is incorrect, or the account is inactive.'); }
    session_regenerate_id(true);
    $_SESSION = ['auth'=>['user_id'=>(int)$user['user_id'], 'role'=>$user['role'], 'username'=>$user['username'], 'password_version'=>$user['password_changed_at']]];
    return $user;
}
function tmq_change_password(array $user, array $post): void
{
    $new = tmq_input($post,'newPassword');
    if ($error = tmq_password_error($new)) { throw new DomainException($error); }
    if ($new !== tmq_input($post,'confirmNewPassword')) { throw new DomainException('New passwords do not match.'); }
    $db = tmq_db();
    try {
        $db->beginTransaction();
        $query = $db->prepare('SELECT password_hash, account_status FROM users WHERE user_id=? FOR UPDATE');
        $query->execute([$user['user_id']]);
        $row = $query->fetch();
        $current = tmq_input($post,'currentPassword');
        if (!$row || $row['account_status'] !== 'active' || strlen($current) > 72 || str_contains($current, "\0") || !password_verify($current, $row['password_hash'])) { throw new DomainException('Current password is incorrect.'); }
        if ($user['role'] !== 'admin') {
            $query = $db->prepare("SELECT is_editable FROM profile_field_settings WHERE role=? AND field_key='password'");
            $query->execute([$user['role']]);
            if ((int)$query->fetchColumn() !== 1) { throw new DomainException('Password changes are currently disabled by account settings. Contact the administrator.'); }
        }
        $query = $db->prepare('UPDATE users SET password_hash=?, password_changed_at=GREATEST(UTC_TIMESTAMP(), COALESCE(DATE_ADD(password_changed_at, INTERVAL 1 SECOND), UTC_TIMESTAMP())) WHERE user_id=?');
        $query->execute([password_hash($new,PASSWORD_DEFAULT),$user['user_id']]);
        $query = $db->prepare('SELECT password_changed_at FROM users WHERE user_id=?');
        $query->execute([$user['user_id']]);
        $version = $query->fetchColumn();
        $db->commit();
        session_regenerate_id(true);
        $_SESSION['auth']['password_version'] = $version;
        unset($_SESSION['csrf']);
    } catch (Throwable $error) { if ($db->inTransaction()) { $db->rollBack(); } throw $error; }
}
