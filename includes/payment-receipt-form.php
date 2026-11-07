<?php if (!isset($authUser,$payment,$receiptAction)) { http_response_code(404);exit; }
$receiptFormId=$receiptAction.($receiptCurrent['submission_id']??'new');
$receiptRetry=$_SERVER['REQUEST_METHOD']==='POST' && tmq_input($_POST,'payment_action')===$receiptAction && (!$receiptCurrent || tmq_input($_POST,'submission_id')===(string)$receiptCurrent['submission_id']);
$receiptValue=fn(string $key)=>$receiptRetry?tmq_input($_POST,$key):($receiptCurrent[$key]??'');
?>
<form method="post" enctype="multipart/form-data" class="portal-form">
<?= tmq_csrf_field() ?><input type="hidden" name="payment_action" value="<?= $receiptAction ?>"><input type="hidden" name="payment_id" value="<?= (int)$payment['payment_id'] ?>">
<?php if($receiptCurrent): ?><input type="hidden" name="transfer_id" value="<?= (int)$receiptCurrent['transfer_id'] ?>"><input type="hidden" name="submission_id" value="<?= (int)$receiptCurrent['submission_id'] ?>"><?php endif; ?>
<div class="portal-fields">
<div class="form-group"><label class="form-label" for="amount-<?= $receiptFormId ?>">Amount of this genuine transfer (RM)</label><input class="form-control" id="amount-<?= $receiptFormId ?>" name="amount_paid" type="text" inputmode="decimal" required maxlength="11" placeholder="4.00" value="<?= tmq_escape($receiptValue('amount_paid')) ?>"></div>
<div class="form-group"><label class="form-label" for="date-<?= $receiptFormId ?>">Bank transfer date (optional)</label><input class="form-control" id="date-<?= $receiptFormId ?>" name="transfer_date" type="date" value="<?= tmq_escape($receiptValue('transfer_date')) ?>"><small>Copy the date from bank evidence; upload time does not establish the bank date.</small></div>
<?php if(!$receiptCurrent): ?><div class="form-group"><label class="form-label" for="reference-<?= $receiptFormId ?>">Bank reference (optional)</label><input class="form-control" id="reference-<?= $receiptFormId ?>" name="bank_reference" maxlength="150" value="<?= tmq_escape($receiptValue('bank_reference')) ?>"></div><?php endif; ?>
<div class="form-group"><label class="form-label" for="file-<?= $receiptFormId ?>">Receipt (PDF, JPG/JPEG or PNG; up to 5 MB)</label><input class="form-control" id="file-<?= $receiptFormId ?>" name="receipt" type="file" accept=".pdf,.jpg,.jpeg,.png" required></div>
<div class="form-group"><label class="form-label" for="note-<?= $receiptFormId ?>">Receipt / correction note (optional)</label><textarea class="form-control" id="note-<?= $receiptFormId ?>" name="payment_note" maxlength="4000" rows="3"><?= tmq_escape($receiptValue('payment_note')) ?></textarea></div>
</div><button type="submit" class="btn btn-primary"><?= $receiptCurrent?'Submit corrected receipt for the same transfer':'Record a new genuine transfer' ?></button></form>
