<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/auth.php';

// Password input is JSON on STDIN, never a command-line argument or SQL seed.
// Pipe from a secure prompt; see docs/authentication-handoff.md for PowerShell.
try {
    $raw = stream_get_contents(STDIN, 16385);
    if (strlen($raw) > 16384) { throw new DomainException('Provisioning input is too large.'); }
    $input = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($input)) { throw new DomainException('Supply a JSON object through standard input.'); }
    $data = ['role'=>'admin'];
    foreach (['username','email','first_name','last_name','password'] as $key) { $data[$key] = tmq_input($input,$key); }
    $userId = tmq_create_account($data, true);
    fwrite(STDOUT, "Admin account created (user ID $userId). No password is printed.\n");
    if (in_array('--init-settings', $argv, true)) {
        $db = tmq_db();
        $query = $db->prepare('SELECT admin_id FROM admins WHERE user_id=?');
        $query->execute([$userId]);
        $adminId = (int)$query->fetchColumn();
        // Existing preferences are never overwritten; the seed is one atomic INSERT.
        if ((int)$db->query('SELECT COUNT(*) FROM profile_field_settings')->fetchColumn() !== 0) {
            fwrite(STDOUT, "Settings already exist; left unchanged.\n");
        } else {
            $db->exec('SET @profile_settings_admin_id=' . $adminId);
            $seed = preg_replace('/^--[^\r\n]*$/m', '', file_get_contents(__DIR__ . '/../database/seed-profile-field-settings.sql'));
            foreach (preg_split('/;\s*(?:\r?\n|$)/', $seed) as $statement) {
                if (trim($statement) !== '') { $db->exec($statement); }
            }
            fwrite(STDOUT, "Initialized the 22 approved profile field settings.\n");
        }
    }
} catch (DomainException $error) {
    fwrite(STDERR, $error->getMessage() . "\n"); exit(1);
} catch (JsonException $error) {
    fwrite(STDERR, "Invalid JSON input. No account created.\n"); exit(1);
} catch (Throwable $error) {
    fwrite(STDERR, isset($userId) ? "Admin created, but settings initialization failed. Inspect settings before retrying initialization.\n" : "Admin provisioning failed. Check local database availability/configuration.\n"); exit(1);
}
