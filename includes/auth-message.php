<?php if (!empty($authMessage)): ?>
<aside class="flow-notice <?= !empty($authSuccess) ? 'info' : 'warm' ?>" role="<?= !empty($authSuccess) ? 'status' : 'alert' ?>"><p><?= tmq_escape($authMessage) ?></p></aside>
<?php endif; ?>
