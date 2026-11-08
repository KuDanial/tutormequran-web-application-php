<?php if ($faqError !== ''): ?><p role="alert"><?= tmq_escape($faqError) ?></p><?php endif; ?>
<form method="post" class="portal-form" aria-label="FAQ Answer">
<?= tmq_csrf_field() ?>
<input type="hidden" name="faq_action" value="save">
<div class="portal-fields">
<div class="form-group">
<label class="form-label" for="question-keyword">Question / Keyword</label>
<input id="question-keyword" name="questionKeyword" class="form-control" type="text" required value="<?= tmq_escape($faq['question']) ?>" aria-describedby="faq-phrase-help">
<p id="faq-phrase-help">Maximum 500 characters. Questions of up to 100 characters after normalization also supply a matching phrase.</p>
</div>
<div class="form-group">
<label class="form-label" for="answer">Answer</label>
<textarea id="answer" name="answer" class="form-control" rows="4" required><?= tmq_escape($faq['answer']) ?></textarea>
</div>
<div class="form-group">
<label class="form-label" for="status">Status</label>
<select id="status" name="faqStatus" class="form-control">
<?php foreach (['active'=>'Active', 'inactive'=>'Inactive'] as $value=>$label): ?>
<option value="<?= $value ?>"<?= $faq['status'] === $value ? ' selected' : '' ?>><?= $label ?></option>
<?php endforeach; ?>
</select>
</div>
</div>
<div class="portal-actions"><button type="submit" class="btn btn-primary">Save<?= $faqMode === 'edit' ? ' Changes' : '' ?></button><a class="btn btn-outline btn-sm" href="manage-faq.php">Back</a></div>
</form>
