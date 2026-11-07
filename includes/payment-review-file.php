<?php
if (!isset($authUser) || !in_array($authUser['role'],['tutor','admin'],true)) { http_response_code(403);exit; }
require_once __DIR__.'/payments.php';
try {
    if ($_SERVER['REQUEST_METHOD']!=='GET') { http_response_code(405);exit('Use GET to download a receipt.'); }
    $id=filter_var(tmq_input($_GET,'id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>4294967295]]);
    if (!$id) { http_response_code(404);exit('File unavailable.'); }
    $params=[$id];$scope='';
    if ($authUser['role']==='tutor') { $scope=' AND t.user_id=?';$params[]=$authUser['user_id']; }
    $record=tmq_profile_rows('SELECT s.file_path,s.mime_type FROM payment_submissions s
        JOIN payment_transfers tr ON tr.transfer_id=s.transfer_id JOIN payments p ON p.payment_id=tr.payment_id
        JOIN bookings b ON b.booking_id=p.booking_id JOIN tutors t ON t.tutor_id=b.tutor_id
        WHERE s.submission_id=?'.$scope,$params)[0]??null;
    $key=$record['file_path']??null;
    if (!$key || !str_starts_with($key,'receipts/')) { http_response_code(404);exit('File unavailable.'); }
    $file=tmq_stored_file($key);
    if (!is_file($file)) { http_response_code(404);exit('File unavailable.'); }
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file);
    if (!in_array($mime,['application/pdf','image/jpeg','image/png'],true) || $record['mime_type']!==$mime) { http_response_code(404);exit('File unavailable.'); }
    $stream=@fopen($file,'rb');if (!$stream) { throw new RuntimeException('File unavailable.'); }
    header('Content-Type: '.$mime);header('X-Content-Type-Options: nosniff');header("Content-Security-Policy: default-src 'none'; sandbox");header('Cache-Control: private, no-store');
    header('Content-Disposition: attachment; filename="receipt-'.$id.'.'.pathinfo($key,PATHINFO_EXTENSION).'"');header('Content-Length: '.filesize($file));
    session_write_close();fpassthru($stream);fclose($stream);
} catch (Throwable $error) { http_response_code(503);echo 'File temporarily unavailable.'; }
