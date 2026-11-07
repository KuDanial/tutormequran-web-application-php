<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/payments.php';
try {
    if ($_SERVER['REQUEST_METHOD']!=='GET') { http_response_code(405);exit('Use GET to download a receipt.'); }
    $id=filter_var(tmq_input($_GET,'id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>4294967295]]);
    $kind=tmq_input($_GET,'kind')?:'receipt';
    if (!$id || !in_array($kind,['receipt','qr'],true)) { http_response_code(404);exit('File unavailable.'); }
    $sql=$kind==='receipt'
        ? 'SELECT r.file_path,r.mime_type FROM payment_submissions r JOIN payment_transfers t ON t.transfer_id=r.transfer_id JOIN payments p ON p.payment_id=t.payment_id JOIN bookings b ON b.booking_id=p.booking_id JOIN students s ON s.student_id=b.student_id WHERE r.submission_id=? AND s.user_id=?'
        : 'SELECT p.qr_path_snapshot AS file_path,NULL AS mime_type FROM payments p JOIN bookings b ON b.booking_id=p.booking_id JOIN students s ON s.student_id=b.student_id WHERE p.payment_id=? AND s.user_id=?';
    $record=tmq_profile_rows($sql,[$id,$authUser['user_id']])[0]??null;
    $key=$record['file_path']??null;
    if (!$key || !str_starts_with($key,$kind==='receipt'?'receipts/':'duitnow/')) { http_response_code(404);exit('File unavailable.'); }
    $file=tmq_stored_file($key);
    if (!is_file($file)) { http_response_code(404);exit('File unavailable.'); }
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file);
    if (!in_array($mime,$kind==='receipt'?['application/pdf','image/jpeg','image/png']:['image/jpeg','image/png'],true)
        || ($record['mime_type']!==null && $record['mime_type']!==$mime)) { http_response_code(404);exit('File unavailable.'); }
    $stream=@fopen($file,'rb');if (!$stream) { throw new RuntimeException('File unavailable.'); }
    header('Content-Type: '.$mime);header('X-Content-Type-Options: nosniff');header("Content-Security-Policy: default-src 'none'; sandbox");header('Cache-Control: private, no-store');
    header('Content-Disposition: '.($kind==='receipt'?'attachment':'inline').'; filename="'.$kind.'-'.$id.'.'.pathinfo($key,PATHINFO_EXTENSION).'"');header('Content-Length: '.filesize($file));
    session_write_close();fpassthru($stream);fclose($stream);
} catch (Throwable $error) { http_response_code(503);echo 'File temporarily unavailable.'; }
