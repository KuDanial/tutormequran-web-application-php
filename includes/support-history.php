<?php if (!isset($authUser,$supportHistory)) { http_response_code(404); exit; } ?>
<section class="portal-card student-list" aria-label="Your support requests">
<h2>Your support requests</h2>
<?php if (!$supportHistory['rows']): ?><p>No requests on this page.</p><?php endif; ?>
<?php foreach ($supportHistory['rows'] as $request): ?>
<article class="student-list-item" data-request-id="<?= (int)$request['request_id'] ?>"><div>
<h3>Request #<?= (int)$request['request_id'] ?> — <?= tmq_escape(ucfirst($request['category'])) ?></h3>
<span class="portal-badge"><?= tmq_escape(ucfirst($request['status'])) ?></span>
<p style="white-space: pre-wrap"><?= tmq_escape($request['description']) ?></p>
<?php if ($request['booking_id']!==null): ?><p>Booking #<?= (int)$request['booking_id'] ?></p><?php endif; ?>
<p>Created: <?= tmq_escape((new DateTimeImmutable($request['created_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))->format('d M Y, H:i')) ?> (Malaysia)</p>
<?php if ($request['resolution_note']!==null): ?><p style="white-space: pre-wrap">Resolution: <?= tmq_escape($request['resolution_note']) ?></p><?php endif; ?>
<?php if ($request['resolved_at']!==null): ?><p>Resolved: <?= tmq_escape((new DateTimeImmutable($request['resolved_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))->format('d M Y, H:i')) ?> (Malaysia)</p><?php endif; ?>
<?php if ($request['has_photo']): ?><a class="btn btn-outline btn-sm" href="../support-file.php?id=<?= (int)$request['request_id'] ?>">Download screenshot</a><?php endif; ?>
</div></article>
<?php endforeach; ?>
</section>
<nav class="portal-actions" aria-label="Support history pages">
<a class="btn btn-outline btn-sm" href="<?= tmq_escape($supportRoute) ?>">Latest requests</a>
<?php if ($supportPage>1): ?><a class="btn btn-outline btn-sm" href="?page=<?= $supportPage-1 ?>">Newer requests</a><?php endif; ?>
<?php if ($supportHistory['more']): ?><a class="btn btn-outline btn-sm" href="?page=<?= $supportPage+1 ?>">Older requests</a><?php endif; ?>
</nav>
