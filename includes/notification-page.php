<?php
if (!isset($authUser) || !in_array($authUser['role'],['student','tutor'],true)) { http_response_code(403); exit; }
require_once __DIR__.'/notifications.php';
require_once __DIR__.'/bookings.php';
$notificationError=''; $notificationHistory=['rows'=>[],'more'=>false]; $notificationUnread=0; $notificationPage=1;
$notificationRoute=$authUser['role'].'-notifications.php';
try {
    if (!in_array($_SERVER['REQUEST_METHOD'],['GET','POST'],true)) { http_response_code(405); throw new DomainException('Use GET or POST.'); }
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        tmq_check_csrf();
        $action=tmq_input($_POST,'notification_action');
        if (!in_array($action,['read','read_all'],true)) { throw new DomainException('Choose a mark-read action.'); }
        $id=null;
        if ($action==='read') {
            $id=filter_var(tmq_input($_POST,'notification_id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>4294967295]]);
            if (!$id) { throw new DomainException('Choose a valid notification.'); }
        }
        tmq_notification_mark_read(tmq_db(),(int)$authUser['user_id'],$id);
        tmq_redirect($authUser['role'].'/'.$notificationRoute);
    }
    if (isset($_GET['page'])) {
        $notificationPage=filter_var(tmq_input($_GET,'page'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000000]]);
        if (!$notificationPage) { $notificationPage=1; throw new DomainException('Choose a valid page.'); }
    }
    $notificationHistory=tmq_notification_page(tmq_db(),$authUser,$notificationPage);
    $notificationUnread=tmq_notification_unread_count(tmq_db(),(int)$authUser['user_id']);
} catch (DomainException $e) {
    if (!in_array(http_response_code(),[403,405],true)) { http_response_code(422); }
    $notificationError=$e->getMessage();
} catch (Throwable $e) { http_response_code(503); $notificationError='Notifications are temporarily unavailable. Please try again.'; }
