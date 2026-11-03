<?php if(!isset($actor)){http_response_code(404);exit;} tmq_profile_notice(); ?>
<?php if ($actor['role']==='admin' && $profile): ?>
<section class="portal-card"><h2>Applicant information</h2><dl class="portal-details">
<?php foreach(['Tutor ID','First Name','Last Name','IC Number','Phone','Email','Address','Username'] as $label): ?><div><dt><?= tmq_escape($label) ?></dt><dd><?= tmq_escape(tmq_profile_detail($label)) ?></dd></div><?php endforeach; ?>
</dl></section>
<?php endif; ?>
<?php if ($certificate): ?>
<section class="portal-card"><h2>Certificate CERT-<?= (int)$certificate['certificate_id'] ?></h2><dl class="portal-details">
<?php foreach(['Certificate Name'=>'certificate_name','Type'=>'certificate_type','Qualification level'=>'qualification_level','Certificate Year'=>'certificate_year','Origin'=>'origin','Description'=>'description','Status'=>'status','Uploaded Date'=>'submitted_at','Filename'=>'original_filename','Reviewed By'=>'reviewer','Review Date'=>'reviewed_at','Admin remark'=>'admin_remark'] as $label=>$key): ?>
<div><dt><?= tmq_escape($label) ?></dt><dd><?= tmq_escape((string)($certificate[$key]??'Not recorded')) ?></dd></div><?php endforeach; ?>
</dl><a class="btn btn-outline btn-sm" href="<?= tmq_escape(tmq_url('profile-file.php?kind=certificate&id='.(int)$certificate['certificate_id'])) ?>">View / download certificate</a></section>
<?php if ($actor['role']==='admin' && $certificate['status']==='pending'): ?>
<section class="portal-card"><h2>Review decision</h2><form method="post" class="portal-form"><?= tmq_csrf_field() ?><input type="hidden" name="profile_action" value="review"><div class="form-group"><label class="form-label" for="certificateAdminRemark">Admin Remark (required for rejection)</label><textarea class="form-control" id="certificateAdminRemark" name="certificateAdminRemark" rows="4"></textarea></div><label><input type="checkbox" name="makeDisplayed" value="1"> Use this as the displayed qualification if approved</label><div class="portal-actions"><button class="btn btn-primary" type="submit" name="decision" value="approved">Approve Certificate</button><button class="btn btn-danger action-danger" type="submit" name="decision" value="rejected" data-confirm="Reject this certificate?">Reject Certificate</button></div></form></section>
<?php endif; ?>
<?php else: ?><aside class="flow-notice info"><p><?= $actor['role']==='admin'?'Select a certificate from the application list.':'No certificate submission selected. Submit your qualification to begin review.' ?></p></aside><?php endif; ?>
<?php if ($actor['role']==='tutor'): ?>
<section class="portal-card"><h2>Certificate history</h2><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Certificate</th><th>Purpose</th><th>Status</th><th>Submitted</th><th>Action</th></tr></thead><tbody>
<?php foreach ($certificateHistory as $item): ?><tr><td><?= tmq_escape($item['certificate_name']) ?></td><td><?= tmq_escape($item['submission_purpose']) ?></td><td><?= tmq_escape($item['status']) ?></td><td><?= tmq_escape($item['submitted_at']) ?></td><td><a href="tutor-certificate-view.php?certificate_id=<?= (int)$item['certificate_id'] ?>">View</a></td></tr><?php endforeach; ?>
<?php if (!$certificateHistory): ?><tr><td colspan="5">No certificates submitted.</td></tr><?php endif; ?></tbody></table></div>
<div class="portal-actions"><a class="btn btn-primary btn-sm" href="tutor-certificate-upload.php">Submit certificate</a><a class="btn btn-outline btn-sm" href="tutor-certificate-replace.php">Correct a submission</a></div></section>
<section class="portal-card"><h2>Displayed qualification</h2><form method="post" class="portal-form"><?= tmq_csrf_field() ?><input type="hidden" name="profile_action" value="displayed"><label for="displayed-certificate">Choose an approved qualification</label><select id="displayed-certificate" name="displayed_certificate_id" class="form-control" required><option value="">Select certificate</option>
<?php foreach($certificateHistory as $item): if($item['status']!=='approved'){continue;} ?><option value="<?= (int)$item['certificate_id'] ?>" <?= (int)$profile['displayed_certificate_id']===(int)$item['certificate_id']?'selected':'' ?>><?= tmq_escape($item['certificate_name']) ?></option><?php endforeach; ?></select><button type="submit" class="btn btn-primary">Save displayed qualification</button></form><p class="field-help">Earlier approved qualifications remain in history. No automatic ranking is applied.</p></section>
<?php endif; ?>
