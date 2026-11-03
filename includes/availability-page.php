<?php
declare(strict_types=1);
if (!isset($authUser) || $authUser['role'] !== 'tutor') { http_response_code(404); exit; }
require_once __DIR__ . '/availability.php';
$slotPage = basename($_SERVER['SCRIPT_FILENAME']);
$slot = null; $slots = []; $slotMessage = ''; $slotReady = false;
$slotId = null;
$slotValues = ['availabilityDate'=>(new DateTimeImmutable('now',new DateTimeZone('Asia/Kuala_Lumpur')))->format('Y-m-d'), 'startTime'=>'13:00', 'durationMinutes'=>'60', 'classType'=>'online', 'availabilityStatus'=>'available'];
try {
    $db = tmq_db();
    $q = $db->prepare('SELECT tutor_id FROM tutors WHERE user_id=?');
    $q->execute([$authUser['user_id']]);
    $slotTutorId = (int)$q->fetchColumn();
    if (!$slotTutorId) { throw new RuntimeException('Tutor missing'); }
    if (in_array($slotPage, ['tutor-availability-edit.php','tutor-availability-view.php'], true)) {
        if (!isset($_GET['slot']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $slotMessage = 'Choose a slot from My Availability first.';
        } else {
            $slotId = filter_var(tmq_input($_GET,'slot'), FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: null;
            $q = $db->prepare('SELECT * FROM availability_slots WHERE slot_id=? AND tutor_id=?');
            $q->execute([$slotId, $slotTutorId]);
            $slot = $q->fetch() ?: null;
            if (!$slot) { http_response_code(404); throw new DomainException('Availability slot not found.'); }
            $start = tmq_slot_local($slot['starts_at']); $end = tmq_slot_local($slot['ends_at']);
            $slotValues = ['availabilityDate'=>$start->format('Y-m-d'),'startTime'=>$start->format('H:i'),'durationMinutes'=>(string)(($end->getTimestamp()-$start->getTimestamp())/60),'classType'=>$slot['mode'],'availabilityStatus'=>$slot['availability_status']];
            $slotReady = true;
        }
    } else { $slotReady = true; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        tmq_check_csrf();
        if (!in_array($slotPage,['tutor-availability-addNew.php','tutor-availability-edit.php'],true)) {
            http_response_code(405); throw new DomainException('Use the availability edit form.');
        }
        foreach (array_keys($slotValues) as $key) { $slotValues[$key] = tmq_input($_POST,$key); }
        $savedId = tmq_save_slot($authUser, $slotId, $_POST);
        tmq_redirect('tutor/tutor-availability-view.php?slot='.$savedId);
    }
    if ($slotPage === 'tutor-availability.php') {
        $q = $db->prepare('SELECT * FROM availability_slots WHERE tutor_id=? ORDER BY (starts_at < UTC_TIMESTAMP()), starts_at, slot_id');
        $q->execute([$slotTutorId]); $slots = $q->fetchAll();
    }
} catch (DomainException $error) {
    if (!in_array(http_response_code(),[403,404,405],true)) { http_response_code(422); }
    $slotMessage = $error->getMessage();
} catch (Throwable $error) {
    http_response_code(503); $slotReady = false;
    $slotMessage = 'Availability is temporarily unavailable. Please try again later.';
}
function tmq_slot_options(array $options, string $selected): string
{
    $html = '';
    foreach ($options as $value=>$label) { $html .= '<option value="'.tmq_escape((string)$value).'"'.((string)$value===$selected?' selected':'').'>'.tmq_escape($label).'</option>'; }
    return $html;
}
function tmq_slot_labels(array $slot): array
{
    $start = tmq_slot_local($slot['starts_at']); $end = tmq_slot_local($slot['ends_at']);
    $hours = (int)(($end->getTimestamp()-$start->getTimestamp())/3600);
    return [$start->format('d M Y'),$start->format('g:i A'),$end->format('g:i A').($end->format('Y-m-d')!==$start->format('Y-m-d')?' (+1 day)':''),$hours.($hours===1?' hour':' hours'),['online'=>'Online','physical'=>'In person','both'=>'Online / In person'][$slot['mode']],ucfirst($slot['availability_status'])];
}
