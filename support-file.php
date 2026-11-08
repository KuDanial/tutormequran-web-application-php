<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/uploads.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
try {
    $user=tmq_current_user();
    if (!$user) { tmq_redirect('login.php'); }
    if ($_SERVER['REQUEST_METHOD']!=='GET') { http_response_code(405); exit('Use GET.'); }
    $id=filter_var(tmq_input($_GET,'id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>4294967295]]);
    if (!$id) { http_response_code(404); exit('Attachment not found.'); }
    $admin=$user['role']==='admin';
    if ($admin) { tmq_require_role('admin'); }
    $query=tmq_db()->prepare('SELECT photo_path FROM support_requests WHERE request_id=? AND '.
        ($admin?'EXISTS (SELECT 1 FROM admins WHERE user_id=?)':'requester_user_id=?'));
    $query->execute([$id,$user['user_id']]);
    $key=$query->fetchColumn();
    if (!$key || !preg_match('~\Asupport/[a-f0-9]{40}\.(jpg|png)\z~',$key,$match)) { http_response_code(404); exit('Attachment not found.'); }
    $path=tmq_stored_file($key);
    if (!is_file($path) || ($handle=@fopen($path,'rb'))===false) { http_response_code(404); exit('Attachment not found.'); }
    header('Content-Type: '.($match[1]==='jpg'?'image/jpeg':'image/png'));
    header('Content-Disposition: attachment; filename="support-image.'.$match[1].'"');
    header("Content-Security-Policy: default-src 'none'; sandbox");
    header('Content-Length: '.fstat($handle)['size']);
    @fpassthru($handle);
    fclose($handle);
} catch (Throwable $e) { http_response_code(503); echo 'Attachment temporarily unavailable.'; }
