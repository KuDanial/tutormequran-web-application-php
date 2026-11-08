<?php
declare(strict_types=1);
require_once __DIR__.'/bookings.php';
require_once __DIR__.'/chat-uploads.php';

function tmq_chat_booking(array $actor, int $id): array
{
    if (!in_array($actor['role'],['student','tutor'],true)) { http_response_code(403); throw new DomainException('Private participant conversation.'); }
    return tmq_booking_record($actor,$id);
}
function tmq_chat_text(string $value, int $limit, bool $required=false): ?string
{
    if (!mb_check_encoding($value,'UTF-8') || str_contains($value,"\0")) { throw new DomainException('Use valid UTF-8 text without null characters.'); }
    $value=trim($value);
    if (mb_strlen($value,'UTF-8')>$limit || str_contains($value,"\0")) { throw new DomainException('Text exceeds the allowed length or contains invalid characters.'); }
    $empty=$value==='' || preg_match('/\A\s*\z/u',$value);
    if ($required && $empty) { throw new DomainException('Enter a message or location.'); }
    return $empty?null:$value;
}
function tmq_chat_coordinate(string $value, int $max): ?string
{
    $value=trim($value," \t\r\n"); if ($value==='') { return null; }
    // Match the exact DECIMAL scale; reject rounding and scientific notation.
    if (!preg_match('/\A[+-]?[0-9]{1,3}(?:\.[0-9]{1,6})?\z/',$value)) { throw new DomainException('Coordinates need decimal numbers with at most six decimal places.'); }
    $unsigned=ltrim($value,'+-'); $parts=explode('.',$unsigned); $scaled=(int)$parts[0]*1000000+(int)str_pad($parts[1]??'',6,'0');
    if ($scaled>$max*1000000) { throw new DomainException('Coordinates are outside the allowed range.'); }
    return $value;
}
function tmq_chat_send(array $actor, int $id, array $input): void
{
    tmq_chat_booking($actor,$id);
    if (tmq_input($input,'chat_action')!=='send') { throw new DomainException('Messages cannot be edited or deleted.'); }
    $type=tmq_input($input,'message_type');
    if (!in_array($type,['text','attachment','location'],true)) { throw new DomainException('Choose a valid message type.'); }
    $body=tmq_chat_text(tmq_input($input,'body'),2000,$type==='text'); $label=null; $lat=null; $lng=null; $files=[];
    if ($type==='location') {
        $label=tmq_chat_text(tmq_input($input,'location_label'),500);
        $lat=tmq_chat_coordinate(tmq_input($input,'latitude'),90); $lng=tmq_chat_coordinate(tmq_input($input,'longitude'),180);
        if (($lat===null)!==($lng===null) || ($label===null && $lat===null)) { throw new DomainException('Provide a location label or both coordinates.'); }
    }
    $db=tmq_db();
    try {
        if ($type==='attachment') { $files=tmq_chat_receive_files(); }
        $db->beginTransaction();
        // Only the booking is locked. Lifecycle/payment transitions also lock this row.
        // Never acquire participant, slot or payment locks after it.
        $q=$db->prepare('SELECT status FROM bookings WHERE booking_id=? FOR UPDATE'); $q->execute([$id]); $status=$q->fetchColumn();
        tmq_chat_booking($actor,$id);
        $user=tmq_profile_user((int)$actor['user_id']);
        if ($user['account_status']!=='active' || $user['role']!==$actor['role']) { http_response_code(403); throw new DomainException('Account unavailable.'); }
        if (!in_array($status,['requested','accepted','completed'],true)) { http_response_code(409); throw new DomainException('This booking conversation is read-only.'); }
        $q=$db->prepare('INSERT INTO messages (booking_id,sender_user_id,message_type,body,location_label,latitude,longitude,sent_at) VALUES (?,?,?,?,?,?,?,UTC_TIMESTAMP())');
        $q->execute([$id,$actor['user_id'],$type,$body,$label,$lat,$lng]); $messageId=$db->lastInsertId();
        $q=$db->prepare('INSERT INTO message_attachments (message_id,file_path,original_filename,mime_type,file_size_bytes) VALUES (?,?,?,?,?)');
        foreach ($files as $file) { $q->execute([$messageId,$file['file_path'],$file['original_filename'],$file['mime_type'],$file['file_size_bytes']]); }
        tmq_notify_booking($db,$id,'chat_message',(int)$actor['user_id']);
        $db->commit(); $files=[];
    } catch (Throwable $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        foreach ($files as $file) { tmq_discard_upload($file); }
        if ($e instanceof PDOException && in_array((int)($e->errorInfo[1]??0),[1205,1213],true)) { http_response_code(409); throw new DomainException('Conversation changed. Reload and try again.'); }
        throw $e;
    }
}
function tmq_chat_history(array $actor, int $id, ?int $before): array
{
    tmq_chat_booking($actor,$id); $params=[$id]; $cursor='';
    if ($before!==null) {
        $row=tmq_profile_rows('SELECT sent_at,message_id FROM messages WHERE booking_id=? AND message_id=?',[$id,$before])[0]??null;
        if (!$row) { tmq_booking_not_found(); }
        $cursor=' AND (m.sent_at<? OR (m.sent_at=? AND m.message_id<?))'; $params=[$id,$row['sent_at'],$row['sent_at'],$before];
    }
    $rows=tmq_profile_rows('SELECT m.*,CONCAT(u.first_name,\' \',u.last_name) AS sender_name FROM messages m JOIN users u ON u.user_id=m.sender_user_id WHERE m.booking_id=?'.$cursor.' ORDER BY m.sent_at DESC,m.message_id DESC LIMIT 101',$params);
    $older=count($rows)>100; if ($older) { array_pop($rows); } $rows=array_reverse($rows); $attachments=[];
    if ($rows) {
        $ids=array_column($rows,'message_id');
        foreach (tmq_profile_rows('SELECT attachment_id,message_id,original_filename,file_size_bytes FROM message_attachments WHERE message_id IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY attachment_id',$ids) as $file) { $attachments[$file['message_id']][]=$file; }
    }
    return ['messages'=>$rows,'attachments'=>$attachments,'older'=>$older];
}
