<?php
if (!isset($authUser) || !in_array($authUser['role'],['student','tutor'],true)) { http_response_code(403); exit; }
require_once __DIR__.'/support-requests.php';
$supportError=''; $supportPage=1; $supportHistory=['rows'=>[],'more'=>false];
$supportRoute=$authUser['role'].'-support-request.php';
// The existing PHP session lock serializes reuse of the same form token.
$_SESSION['support_form'] ??= bin2hex(random_bytes(32));
try {
    if (!in_array($_SERVER['REQUEST_METHOD'],['GET','POST'],true)) { http_response_code(405); throw new DomainException('Use GET or POST.'); }
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        tmq_check_csrf();
        if (tmq_input($_POST,'support_action')!=='create') { throw new DomainException('Choose a valid request action.'); }
        if (!hash_equals($_SESSION['support_form'],tmq_input($_POST,'support_form'))) { http_response_code(409); throw new DomainException('This form was already submitted or expired. Reload before submitting a new request.'); }
        $id=tmq_support_create(tmq_db(),$authUser,$_POST);
        unset($_SESSION['support_form']);
        tmq_redirect($authUser['role'].'/'.$supportRoute.'?id='.$id);
    }
    $id=null;
    if (isset($_GET['id'])) {
        $id=filter_var(tmq_input($_GET,'id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>4294967295]]);
        if (!$id) { http_response_code(404); throw new DomainException('Request not found.'); }
    } elseif (isset($_GET['page'])) {
        $supportPage=filter_var(tmq_input($_GET,'page'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000000]]);
        if (!$supportPage) { throw new DomainException('Choose a valid page.'); }
    }
    $supportHistory=tmq_support_history(tmq_db(),(int)$authUser['user_id'],$supportPage,$id);
    if ($id!==null && !$supportHistory['rows']) { http_response_code(404); throw new DomainException('Request not found.'); }
} catch (DomainException $e) {
    if (!in_array(http_response_code(),[403,404,405,409],true)) { http_response_code(422); }
    $supportError=$e->getMessage();
} catch (Throwable $e) { http_response_code(503); $supportError='Support requests are temporarily unavailable. Please try again.'; }
