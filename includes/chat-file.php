<?php
if (!isset($authUser) || !in_array($authUser['role'],['student','tutor'],true)) { http_response_code(403); exit; }
require_once __DIR__.'/booking-chat.php';
try {
    if ($_SERVER['REQUEST_METHOD']!=='GET') { http_response_code(405); exit('Use GET to download an attachment.'); }
    $id=filter_var(tmq_input($_GET,'id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>4294967295]]);
    if (!$id) { http_response_code(404); exit('File unavailable.'); }
    $scope=$authUser['role']==='student'?'s.user_id':'t.user_id';
    $file=tmq_profile_rows('SELECT a.* FROM message_attachments a JOIN messages m ON m.message_id=a.message_id JOIN bookings b ON b.booking_id=m.booking_id JOIN students s ON s.student_id=b.student_id JOIN tutors t ON t.tutor_id=b.tutor_id WHERE a.attachment_id=? AND '.$scope.'=?',[$id,$authUser['user_id']])[0]??null;
    if (!$file || !str_starts_with($file['file_path'],'chat/')) { http_response_code(404); exit('File unavailable.'); }
    $path=tmq_stored_file($file['file_path']); $ext=pathinfo($path,PATHINFO_EXTENSION);
    if (!is_file($path) || filesize($path)!==(int)$file['file_size_bytes'] || tmq_chat_file_type($path,$ext)!==$file['mime_type']) { http_response_code(404); exit('File unavailable.'); }
    $stream=@fopen($path,'rb'); if (!$stream) { throw new RuntimeException('File unavailable.'); }
    header('Content-Type: '.$file['mime_type']); header('Content-Disposition: attachment; filename="attachment-'.$id.'.'.$ext.'"');
    header('Content-Length: '.$file['file_size_bytes']); header('Cache-Control: private, no-store'); header('X-Content-Type-Options: nosniff'); header("Content-Security-Policy: default-src 'none'; sandbox");
    session_write_close(); fpassthru($stream); fclose($stream);
} catch (DomainException $e) { http_response_code(404); echo 'File unavailable.'; }
catch (Throwable $e) { http_response_code(503); echo 'File temporarily unavailable.'; }
