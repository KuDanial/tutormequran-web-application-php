<?php if (!isset($authUser,$feedbackRows)) { http_response_code(404); exit; } ?>
<header class="portal-heading"><div><h1><?= $feedbackRole==='student'?'Your lesson feedback':'Student feedback' ?></h1><p>One Student review per lesson, after both completion confirmations. Submitted feedback cannot be edited or deleted.</p></div></header>
<?php if($feedbackMessage): ?><p role="status"><?= tmq_escape($feedbackMessage) ?></p><?php endif; ?>
<?php foreach(array_slice($feedbackRows,0,50) as $feedback): ?>
<section class="portal-card"><h2>Feedback #<?= (int)$feedback['feedback_id'] ?> &middot; <?= tmq_escape($feedbackRole==='student'?$feedback['tutor_name']:$feedback['student_name']) ?></h2>
<p>Booking #<?= (int)$feedback['booking_id'] ?> &middot; <?= tmq_escape(tmq_booking_time($feedback['created_at'])) ?></p>
<p><?= (int)$feedback['rating'] ?> / 5</p><p style="white-space: pre-wrap"><?= tmq_escape($feedback['comment']??'No comment provided.') ?></p>
<p>Recorded feedback &middot; read-only</p><a class="btn btn-outline" href="<?= $feedbackRole ?>-booking-view.php?id=<?= (int)$feedback['booking_id'] ?>">View booking</a></section>
<?php endforeach; ?>
<?php if(!$feedbackRows): ?><p>No feedback recorded<?= $feedbackBooking?' for this booking':'' ?>.</p><?php endif; ?>
<?php if($feedbackForm && $feedbackBooking && !$feedbackRows): ?>
<?php if(tmq_feedback_eligible($feedbackBooking)): ?>
<section class="portal-card"><h2>Review booking #<?= (int)$feedbackBooking['booking_id'] ?> with <?= tmq_escape($feedbackBooking['tutor_name']) ?></h2>
<p>Payment verification is not required. Your rating and optional comment will appear on this Tutor's public profile when it is public. Your name and booking details are not displayed there.</p>
<form class="portal-form" method="post" action="student-feedback-addNew.php?id=<?= (int)$feedbackBooking['booking_id'] ?>"><?= tmq_csrf_field() ?><input type="hidden" name="feedback_action" value="submit">
<label class="form-label" for="feedback-rating">Rating (1–5)</label><select class="form-control" id="feedback-rating" name="rating" required><option value="">Choose rating</option><?php foreach(range(1,5) as $rating): ?><option value="<?= $rating ?>"<?= tmq_input($_POST,'rating')===(string)$rating?' selected':'' ?>><?= $rating ?></option><?php endforeach; ?></select>
<label class="form-label" for="feedback-comment">Comment (optional)</label><textarea class="form-control" id="feedback-comment" name="comment" rows="5" maxlength="65535"><?= tmq_escape(tmq_input($_POST,'comment')) ?></textarea><p class="field-help">Plain text, up to 65,535 UTF-8 bytes. Submitted feedback is permanent.</p>
<button class="btn btn-primary" type="submit">Submit feedback</button></form></section>
<?php else: ?><p>Feedback requires both lesson-completion confirmations. Cancelled and rejected bookings cannot receive feedback.</p><?php endif; ?>
<?php endif; ?>
<?php if($feedbackAvailable): ?><section class="portal-card"><h2>Lessons available for feedback</h2><?php foreach(array_slice($feedbackAvailable,0,50) as $lesson): ?><p><a href="student-feedback-addNew.php?id=<?= (int)$lesson['booking_id'] ?>">Review booking #<?= (int)$lesson['booking_id'] ?> with <?= tmq_escape($lesson['tutor_name']) ?></a> &middot; <?= tmq_escape(tmq_booking_time($lesson['starts_at'])) ?></p><?php endforeach; ?></section><?php endif; ?>
<nav class="portal-actions"><?php if($feedbackPage>1): ?><a class="btn btn-outline" href="?page=<?= $feedbackPage-1 ?>">Previous page</a><?php endif; ?><?php if(count($feedbackRows)>50 || count($feedbackAvailable)>50): ?><a class="btn btn-outline" href="?page=<?= $feedbackPage+1 ?>">Next page</a><?php endif; ?><a class="btn btn-outline" href="<?= $feedbackRole ?>-feedbacks.php">All feedback</a><a class="btn btn-outline" href="<?= $feedbackRole ?>-bookings.php">My bookings</a></nav>
