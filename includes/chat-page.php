<?php
if (!isset($authUser) || !in_array($authUser['role'],['student','tutor'],true)) { http_response_code(403); exit; }
require_once __DIR__.'/booking-chat.php';
$chatRole=$authUser['role']; $chatBooking=null; $chatError=''; $history=['messages'=>[],'attachments'=>[],'older'=>false];
try {
    if (!in_array($_SERVER['REQUEST_METHOD'],['GET','POST'],true)) { http_response_code(405); throw new DomainException('Use GET or POST.'); }
    $chatId=filter_var(tmq_input($_GET,'id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>4294967295]]);
    if (!$chatId && (isset($_GET['id']) || $_SERVER['REQUEST_METHOD']==='POST')) { tmq_booking_not_found(); }
    if ($chatId) {
        $chatBooking=tmq_chat_booking($authUser,$chatId);
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            tmq_check_csrf();
            tmq_chat_send($authUser,$chatId,$_POST);
            tmq_redirect($chatRole.'/'.$chatRole.'-chat.php?id='.$chatId);
        }
        $before=null;
        if (isset($_GET['before'])) {
            $before=filter_var(tmq_input($_GET,'before'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>4294967295]]);
            if (!$before) { tmq_booking_not_found(); }
        }
        $history=tmq_chat_history($authUser,$chatId,$before);
    }
} catch (DomainException $e) {
    if (!in_array(http_response_code(),[403,404,405,409],true)) { http_response_code(422); }
    $chatError=$e->getMessage();
    if (http_response_code()===404) { $chatBooking=null; }
    elseif ($chatBooking) {
        try { $chatBooking=tmq_chat_booking($authUser,$chatId); $history=tmq_chat_history($authUser,$chatId,null); }
        catch (Throwable $ignored) { $chatBooking=null; }
    }
} catch (Throwable $e) { http_response_code(503); $chatBooking=null; $chatError='Chat is temporarily unavailable. Please try again.'; }
