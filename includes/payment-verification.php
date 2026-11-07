<?php
declare(strict_types=1);
require_once __DIR__ . '/payments.php';

// DECIMAL(10,2) values become integer sen. No floats, rounding or tolerances.
function tmq_payment_sen(string $amount): int
{
    if (PHP_INT_SIZE < 8 || !preg_match('/\A([0-9]{1,8})\.([0-9]{2})\z/', $amount, $parts)) {
        throw new DomainException('Payment amount cannot be calculated safely.');
    }
    return (int)$parts[1] * 100 + (int)$parts[2];
}

function tmq_payment_money(int $sen): string
{
    return intdiv($sen,100).'.'.str_pad((string)($sen%100),2,'0',STR_PAD_LEFT);
}

// Caller authorizes the booking. With locks, caller already holds booking then payment.
function tmq_payment_current(PDO $db, int $paymentId, bool $lock = false): array
{
    $q=$db->prepare('SELECT transfer_id FROM payment_transfers WHERE payment_id=? ORDER BY transfer_id'.($lock?' FOR UPDATE':''));
    $q->execute([$paymentId]);$transfers=$q->fetchAll();$current=[];
    $q=$db->prepare('SELECT * FROM payment_submissions WHERE transfer_id=? ORDER BY attempt_number DESC LIMIT 1'.($lock?' FOR UPDATE':''));
    foreach ($transfers as $transfer) {
        $q->execute([$transfer['transfer_id']]);
        // An unexpected empty transfer must block settlement, never silently disappear.
        $current[]=$q->fetch()?:['transfer_id'=>$transfer['transfer_id'],'admin_status'=>'pending','tutor_status'=>'awaiting'];
    }
    return $current;
}

function tmq_payment_settlement(array $booking, array $payment, array $current): array
{
    $expected=tmq_payment_sen($booking['expected_amount']);$total=0;
    $pending=!$current;$reupload=false;$rejected=false;
    foreach ($current as $submission) {
        $admin=$submission['admin_status'];$tutor=$submission['tutor_status'];
        if ($admin==='valid' && $tutor==='received') {
            $amount=tmq_payment_sen($submission['amount_paid']);
            if ($total>PHP_INT_MAX-$amount) { tmq_payment_conflict('Payment total exceeds the supported calculation range.'); }
            $total+=$amount;
        } elseif ($admin==='reupload_required') { $reupload=true; }
        elseif ($admin==='rejected' && in_array($tutor,['awaiting','received','issue'],true)) { $rejected=true; }
        else { $pending=true; }
    }
    $status=$reupload?'reupload_required':($pending?'pending':($rejected?'rejected':($total<$expected?'underpaid':'pending')));
    $ready=!$pending && !$reupload && $total===$expected && tmq_payment_eligible($booking) && $payment['status']!=='verified';
    $message=match(true) {
        $payment['status']==='verified'=>'Payment verified. Booking closed; history is read-only.',
        $total>$expected=>'Amount exceeds expected total. Overpayment/manual Admin review required.',
        $reupload=>'Correct / reupload evidence for the same transfer. This is not a request to send more money.',
        $pending=>'Current evidence still requires review.',
        $ready=>'Ready for final verification',
        $total<$expected=>'Valid funds are below the expected amount. Any genuine top-up must be a new transfer.',
        default=>'Booking completion must be confirmed before final verification.',
    };
    return ['status'=>$payment['status']==='verified'?'verified':$status,'valid_total'=>tmq_payment_money($total),
        'outstanding'=>tmq_payment_money(max($expected-$total,0)),'ready'=>$ready,'message'=>$message];
}

function tmq_payment_review_lock(PDO $db, array $actor, int $bookingId, string $revision): array
{
    if (!in_array($actor['role'],['tutor','admin'],true)) { http_response_code(403);throw new DomainException('Payment reviewer access required.'); }
    $q=$db->prepare('SELECT * FROM bookings WHERE booking_id=? FOR UPDATE');$q->execute([$bookingId]);$booking=$q->fetch();
    if (!$booking) { tmq_booking_not_found(); }
    // Ordinary identity reads only: never acquire participant/slot locks in this protocol.
    $user=tmq_profile_user((int)$actor['user_id']);
    if ($user['role']!==$actor['role'] || $user['account_status']!=='active') { http_response_code(403);throw new DomainException('Reviewer account unavailable.'); }
    $adminId=null;
    if ($actor['role']==='tutor') {
        $owner=tmq_profile_rows('SELECT user_id FROM tutors WHERE tutor_id=?',[$booking['tutor_id']])[0]??null;
        if (!$owner || (int)$owner['user_id']!==(int)$actor['user_id']) { tmq_booking_not_found(); }
    } else {
        $adminId=tmq_profile_rows('SELECT admin_id FROM admins WHERE user_id=?',[$actor['user_id']])[0]['admin_id']??null;
        if (!$adminId) { http_response_code(403);throw new DomainException('Admin account unavailable.'); }
    }
    $q=$db->prepare('SELECT * FROM payments WHERE booking_id=? FOR UPDATE');$q->execute([$bookingId]);$payment=$q->fetch();
    if (!$payment) { tmq_booking_not_found(); }
    if (!tmq_payment_eligible($booking) || !in_array($payment['status'],['pending','underpaid','rejected','reupload_required'],true)
        || $payment['verified_at']!==null || $payment['verified_by_admin_id']!==null || $booking['closed_at']!==null) {
        tmq_payment_conflict('This booking/payment is read-only or is not eligible for review.');
    }
    if ($revision!==(string)$payment['evidence_revision']) { tmq_payment_conflict('Payment evidence changed. Reload and review the current evidence before submitting again.'); }
    return [$booking,$payment,$adminId];
}

function tmq_review_payment(array $actor, int $bookingId, array $input): void
{
    $action=tmq_input($input,'payment_action');$decision=tmq_input($input,'decision');
    $admin=$actor['role']==='admin';
    if (($action==='tutor_review' && $actor['role']!=='tutor') || (in_array($action,['admin_review','verify'],true) && !$admin)) {
        http_response_code(403);throw new DomainException('This payment action is not available for your role.');
    }
    if (!in_array($action,['tutor_review','admin_review','verify'],true)) { throw new DomainException('Choose a payment review action.'); }
    $remark=tmq_profile_text(tmq_input($input,'remark'),4000);
    $issue=null;
    if ($action!=='verify') {
        if (!in_array($decision,$admin?['valid','rejected','reupload_required']:['received','issue'],true)) { throw new DomainException('Choose an allowed evidence decision.'); }
        if ($admin && $decision!=='valid' && $remark==='') { throw new DomainException('A reason is required for rejection or reupload.'); }
        if (!$admin && $decision==='issue') {
            $issue=tmq_input($input,'tutor_issue_type');
            if (!in_array($issue,['not_received','amount_mismatch','reference_mismatch'],true)) { throw new DomainException('Choose a valid Tutor issue type.'); }
        }
        $submissionId=tmq_booking_id(tmq_input($input,'submission_id'));
    }
    $db=tmq_db();
    try {
        $db->beginTransaction();
        [$booking,$payment,$adminId]=tmq_payment_review_lock($db,$actor,$bookingId,tmq_input($input,'evidence_revision'));
        $current=tmq_payment_current($db,(int)$payment['payment_id'],true);
        if ($action==='verify') {
            $settlement=tmq_payment_settlement($booking,$payment,$current);
            if (!$settlement['ready']) { tmq_payment_conflict($settlement['message']); }
            $now=$db->query('SELECT UTC_TIMESTAMP()')->fetchColumn();
            $q=$db->prepare("UPDATE payments SET status='verified',verified_by_admin_id=?,verified_at=?,reviewed_by_admin_id=?,reviewed_at=?,admin_remark=? WHERE payment_id=? AND status<>'verified' AND evidence_revision=?");
            $q->execute([$adminId,$now,$adminId,$now,$remark?:null,$payment['payment_id'],$payment['evidence_revision']]);
            if ($q->rowCount()!==1) { tmq_payment_conflict('Payment was just updated. Reload its history.'); }
            $q=$db->prepare("UPDATE bookings SET status='closed',closed_at=? WHERE booking_id=? AND status='completed' AND student_completed_at IS NOT NULL AND tutor_completed_at IS NOT NULL");
            $q->execute([$now,$bookingId]);
            if ($q->rowCount()!==1) { tmq_payment_conflict('Booking was just updated. Reload its history.'); }
        } else {
            if ((int)$payment['evidence_revision']>=4294967295) { tmq_payment_conflict('Payment evidence revision limit reached. Contact support.'); }
            $index=null;
            foreach ($current as $key=>$submission) { if ((int)($submission['submission_id']??0)===$submissionId) { $index=$key;break; } }
            if ($index===null) { tmq_payment_conflict('This receipt is not current. Reload the payment.'); }
            $submission=$current[$index];
            if ($submission['admin_status']!=='pending' || !in_array($submission['tutor_status'],['awaiting','received','issue'],true)) {
                tmq_payment_conflict('This evidence decision is read-only. A permitted correction requires a new receipt version.');
            }
            if ($admin) {
                if ($decision==='valid' && $submission['tutor_status']!=='received') { tmq_payment_conflict('Valid evidence requires Tutor Received acknowledgement first.'); }
                $q=$db->prepare("UPDATE payment_submissions SET admin_status=?,reviewed_by_admin_id=?,admin_reviewed_at=UTC_TIMESTAMP(),admin_remark=? WHERE submission_id=? AND admin_status='pending'");
                $q->execute([$decision,$adminId,$remark?:null,$submissionId]);
                $current[$index]['admin_status']=$decision;
            } else {
                if ($submission['tutor_status']!=='awaiting') { tmq_payment_conflict('Tutor acknowledgement is already recorded and cannot be rewritten.'); }
                $q=$db->prepare("UPDATE payment_submissions SET tutor_status=?,tutor_issue_type=?,tutor_remark=?,tutor_reviewed_at=UTC_TIMESTAMP() WHERE submission_id=? AND tutor_status='awaiting' AND admin_status='pending'");
                $q->execute([$decision,$issue,$remark?:null,$submissionId]);
            }
            if ($q->rowCount()!==1) { tmq_payment_conflict('Receipt was just reviewed. Reload the payment.'); }
            $status=$admin?tmq_payment_settlement($booking,$payment,$current)['status']:'pending';
            $q=$db->prepare("UPDATE payments SET status=?,evidence_revision=evidence_revision+1,reviewed_by_admin_id=?,reviewed_at=IF(? IS NULL,NULL,UTC_TIMESTAMP()),admin_remark=? WHERE payment_id=? AND evidence_revision=? AND status<>'verified'");
            $q->execute([$status,$adminId,$adminId,$admin?($remark?:null):null,$payment['payment_id'],$payment['evidence_revision']]);
            if ($q->rowCount()!==1) { tmq_payment_conflict('Payment evidence was just updated. Reload the payment.'); }
        }
        $db->commit();
    } catch (Throwable $error) { tmq_payment_rollback($db,$error); }
}
