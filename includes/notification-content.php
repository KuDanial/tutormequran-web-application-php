<?php if (!isset($authUser,$notificationHistory)) { http_response_code(404); exit; } ?>
<header class="portal-heading"><div><span class="section-subtitle"><?= $authUser['role']==='student'?'Your learning journey':'Tutor studio' ?></span><h1>Notifications</h1><p><?= $authUser['role']==='student'?'Updates about your lessons and payments.':'Updates about your bookings, payments and qualifications.' ?></p></div></header>
<?php if ($notificationError!==''): ?>
<div class="portal-card" role="alert"><?= tmq_escape($notificationError) ?></div>
<?php else: ?>
<section class="portal-card" aria-label="Notification controls">
<p><strong><?= $notificationUnread ?> unread</strong></p>
<?php if ($notificationUnread>0): ?><form method="post" action="<?= tmq_escape($notificationRoute) ?>"><?= tmq_csrf_field() ?><input type="hidden" name="notification_action" value="read_all"><button class="btn btn-outline btn-sm" type="submit">Mark all as read</button></form><?php endif; ?>
</section>
<section class="portal-card student-list" aria-label="Recent updates">
<?php if (!$notificationHistory['rows']): ?><p>No notifications on this page.</p><?php endif; ?>
<?php foreach ($notificationHistory['rows'] as $notice): ?>
<article class="student-list-item" data-notification-id="<?= (int)$notice['notification_id'] ?>">
<div><h2><?= tmq_escape($notice['title']) ?></h2><p><?= tmq_escape($notice['message']) ?></p><p class="field-help"><time datetime="<?= tmq_escape(str_replace(' ','T',$notice['created_at']).'Z') ?>"><?= tmq_escape(tmq_booking_time($notice['created_at'])) ?></time></p></div>
<div class="student-list-action"><span class="portal-badge <?= $notice['read_at']===null?'pending':'muted' ?>"><?= $notice['read_at']===null?'Unread':'Read' ?></span>
<?php if ($notice['link']!==null): ?><a class="btn btn-outline btn-sm" href="<?= tmq_escape($notice['link']) ?>">View details</a><?php endif; ?>
<?php if ($notice['read_at']===null): ?><form method="post" action="<?= tmq_escape($notificationRoute) ?>"><?= tmq_csrf_field() ?><input type="hidden" name="notification_action" value="read"><input type="hidden" name="notification_id" value="<?= (int)$notice['notification_id'] ?>"><button type="submit" class="btn btn-outline btn-sm">Mark as read</button></form><?php endif; ?>
</div></article>
<?php endforeach; ?>
</section>
<nav class="portal-actions" aria-label="Notification pages">
<?php if ($notificationPage>1): ?><a class="btn btn-outline btn-sm" href="<?= tmq_escape($notificationRoute) ?>?page=<?= $notificationPage-1 ?>">Newer notifications</a><?php endif; ?>
<?php if ($notificationHistory['more']): ?><a class="btn btn-outline btn-sm" href="<?= tmq_escape($notificationRoute) ?>?page=<?= $notificationPage+1 ?>">Older notifications</a><?php endif; ?>
</nav>
<?php endif; ?>
