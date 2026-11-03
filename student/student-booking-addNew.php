<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/booking-creation.php';
$bookingError=''; $bookingTutor=null; $bookingTutors=[]; $bookingSlots=[];
$bookingSuccess=$_SESSION['booking_created']??null; unset($_SESSION['booking_created']);
$selectedTutor=tmq_input($_SERVER['REQUEST_METHOD']==='POST'?$_POST:$_GET,'tutor');
try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        tmq_check_csrf();
        $created=tmq_create_booking($authUser,$_POST);
        $_SESSION['booking_created']=$created;
        tmq_redirect('student/student-booking-addNew.php?tutor='.tmq_booking_id($selectedTutor));
    }
} catch (DomainException $e) { if(http_response_code()!==403){http_response_code(422);} $bookingError=$e->getMessage(); }
catch (Throwable $e) { http_response_code(503); $bookingError='Booking is temporarily unavailable. Please try again later.'; }
try {
    foreach(tmq_profile_rows("SELECT tutor_id FROM tutors WHERE profile_visibility='public' ORDER BY tutor_id") as $r) {
        $t=tmq_profile_tutor((int)$r['tutor_id']); if(tmq_public_tutor($t)){$bookingTutors[]=$t;}
    }
    if($selectedTutor!=='') {
        $id=tmq_booking_id($selectedTutor);
        foreach($bookingTutors as $t){if((int)$t['tutor_id']===$id){$bookingTutor=$t;break;}}
        if(!$bookingTutor){throw new DomainException('This tutor is unavailable for booking.');}
        $bookingSlots=tmq_booking_slots($bookingTutor);
    }
} catch (DomainException $e) { if(!$bookingError){http_response_code(422);$bookingError=$e->getMessage();} }
catch (Throwable $e) { http_response_code(503); $bookingError='Lessons are temporarily unavailable. Please try again later.'; }
$assetPrefix = '../';
$pageTitle = 'Reserve your Quran lesson - TutorMeQuran';
$pageDescription = 'Choose a listed tutor’s available slot. All times are Malaysia time (MYT).';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Your learning journey</span>
<h1>Reserve your Quran lesson</h1>
<p>Choose a listed tutor’s available slot. All times are Malaysia time (MYT).</p>
</div>
</header>
<?php if ($bookingError): ?><p role="alert"><?= tmq_escape($bookingError) ?></p><?php endif; ?>
<?php if ($bookingSuccess): ?><p role="status">Booking #<?= (int)$bookingSuccess ?> created with status Requested. Your lesson slot is reserved pending the tutor's decision.</p><?php endif; ?>
<section class="portal-card"><form method="get" class="portal-form">
<label for="tutor" class="form-label">Choose a tutor</label><select id="tutor" name="tutor" class="form-control" required><option value="">Select a public tutor</option>
<?php foreach($bookingTutors as $t): ?><option value="<?= (int)$t['tutor_id'] ?>" <?= $selectedTutor===(string)$t['tutor_id']?'selected':'' ?>><?= tmq_escape($t['display_name']?:$t['first_name'].' '.$t['last_name']) ?></option><?php endforeach; ?>
</select><div class="portal-actions"><button type="submit" class="btn btn-outline">Show available lessons</button></div>
<?php if(!$bookingTutors): ?><p>No eligible tutors are currently available.</p><?php endif; ?></form></section>
<?php if($bookingTutor): $t=$bookingTutor; ?>
<div class="portal-columns">
<section class="portal-card"><h2>Your selected tutor</h2><div class="portal-person"><span class="portal-avatar"><?= tmq_escape(mb_substr($t['first_name'],0,1)) ?></span><div><h3><?= tmq_escape($t['display_name']?:$t['first_name'].' '.$t['last_name']) ?></h3><p>Verified &middot; <?= tmq_escape($t['teaching_state']) ?></p></div></div>
<dl class="portal-details"><div><dt>Hourly Rate</dt><dd>RM <?= tmq_escape($t['hourly_rate']) ?></dd></div><div><dt>Duration</dt><dd data-booking-duration>Choose a lesson</dd></div><div><dt>Total Lesson Fee</dt><dd data-booking-fee>Shown beside each available lesson</dd></div><div><dt>Class Options</dt><dd><?= tmq_escape(implode(' / ',tmq_booking_modes($t['teaching_mode'],'both'))) ?></dd></div></dl></section>
<section class="portal-card"><h2>Before you book</h2><ul class="clean-list"><li>Only an available slot can be reserved.</li><li>Your request reserves the slot immediately.</li><li>The selected lesson's rate and amount are saved with your booking.</li></ul></section></div>
<section class="portal-card"><div class="lesson-card-heading"><h2>Choose your lesson</h2></div>
<?php if(!$bookingSlots): ?><p>No available future lessons for this tutor.</p><?php else: ?>
<form method="post" class="portal-form" data-booking-create>
<?= tmq_csrf_field() ?><input type="hidden" name="tutor" value="<?= (int)$t['tutor_id'] ?>">
<fieldset class="lesson-slot-picker" aria-describedby="lesson-slot-help"><legend class="form-label">Tutor's available lessons</legend><p id="lesson-slot-help" class="field-help">Dates and times are fixed, in Malaysia time (MYT). Choose a mode shown for your slot.</p><div class="slot-options">
<?php foreach($bookingSlots as $slot): $start=tmq_slot_local($slot['starts_at']); $end=tmq_slot_local($slot['ends_at']); $hours=intdiv($end->getTimestamp()-$start->getTimestamp(),3600); $amount=tmq_booking_amount($t['hourly_rate'],$slot); $modes=tmq_booking_modes($t['teaching_mode'],$slot['mode']); ?>
<label class="slot-btn lesson-slot-option"><input type="radio" name="slotId" value="<?= (int)$slot['slot_id'] ?>" data-modes="<?= tmq_escape(implode(',',$modes)) ?>" data-hours="<?= $hours ?>" data-amount="<?= tmq_escape($amount) ?>" required <?= tmq_input($_POST,'slotId')===(string)$slot['slot_id']?'checked':'' ?>><span class="slot-day"><?= tmq_escape($start->format('l, d F Y')) ?></span><span class="slot-time"><?= tmq_escape($start->format('g:i A').' - '.$end->format('d M g:i A')) ?></span><span><?= $hours ?> hour(s) &middot; RM <?= tmq_escape($amount) ?> &middot; <?= tmq_escape(implode(' / ',$modes)) ?></span><span class="slot-status-badge vacant">Available</span></label>
<?php endforeach; ?></div></fieldset>
<div class="portal-fields"><div class="form-group"><label for="f-classType" class="form-label">Class option</label><select id="f-classType" name="classType" class="form-control" required>
<?php foreach(tmq_booking_modes($t['teaching_mode'],'both') as $mode): ?><option value="<?= $mode ?>" <?= tmq_input($_POST,'classType')===$mode?'selected':'' ?>><?= ucfirst($mode) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label for="f-bookingNotes" class="form-label">Learning Notes</label><textarea id="f-bookingNotes" name="bookingNotes" class="form-control" rows="4" maxlength="4000"><?= tmq_escape(tmq_input($_POST,'bookingNotes')) ?></textarea></div></div>
<div class="portal-actions"><button type="submit" class="btn btn-primary btn-sm">Reserve booking</button><a href="../tutors.php" class="btn btn-outline btn-sm">Back to tutors</a></div></form><?php endif; ?></section><?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-creation.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
