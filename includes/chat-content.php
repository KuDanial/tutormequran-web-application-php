<?php if (!isset($chatRole,$history)) { http_response_code(404); exit; } ?>
<header class="portal-heading"><div><h1>Booking conversation<?= $chatBooking?' #'.(int)$chatBooking['booking_id']:'' ?></h1><p>Private messages between the booking Student and Tutor.</p></div></header>
<?php if ($chatError): ?><p role="alert"><?= tmq_escape($chatError) ?></p><?php endif; ?>
<?php if (!$chatBooking): ?><p>Choose a booking to open its conversation.</p>
<?php else: ?>
<section class="portal-card"><h2><?= tmq_escape($chatBooking['student_name'].' and '.$chatBooking['tutor_name']) ?></h2><?= tmq_booking_badge($chatBooking['status']) ?>
<p><?= tmq_escape(tmq_booking_time($chatBooking['starts_at'])) ?></p><a href="<?= $chatRole ?>-booking-view.php?id=<?= $chatId ?>">View booking and agreed arrangement</a></section>
<section class="portal-card booking-chat-card"><h2>Messages</h2>
<div class="booking-messages" aria-label="Conversation">
<?php foreach ($history['messages'] as $message): ?>
<article class="booking-message<?= (int)$message['sender_user_id']===(int)$authUser['user_id']?' chat-outgoing':'' ?>" data-message-id="<?= (int)$message['message_id'] ?>"><strong><?= tmq_escape($message['sender_name']) ?></strong>
<?php if ($message['body']!==null): ?><p><?= tmq_escape($message['body']) ?></p><?php endif; ?>
<?php if ($message['message_type']==='location'): ?><p>Shared location: <?= tmq_escape($message['location_label']??'Coordinates') ?></p><?php if ($message['latitude']!==null): ?><p><?= tmq_escape($message['latitude'].', '.$message['longitude']) ?></p><?php endif; ?><?php endif; ?>
<?php foreach ($history['attachments'][$message['message_id']]??[] as $file): ?><a class="chat-file-card" href="<?= $chatRole ?>-chat-file.php?id=<?= (int)$file['attachment_id'] ?>"><?= tmq_escape($file['original_filename']) ?> (<?= (int)$file['file_size_bytes'] ?> bytes)</a><?php endforeach; ?>
<small><?= tmq_escape(tmq_booking_time($message['sent_at'])) ?></small></article>
<?php endforeach; ?>
<?php if (!$history['messages']): ?><p>No messages yet.</p><?php endif; ?></div>
<div class="portal-actions"><?php if ($history['older']): ?><a href="?id=<?= $chatId ?>&amp;before=<?= (int)$history['messages'][0]['message_id'] ?>">Older messages</a><?php endif; ?><a href="?id=<?= $chatId ?>">Latest messages / Refresh</a></div>
<?php if (in_array($chatBooking['status'],['requested','accepted','completed'],true)): ?>
<form method="post" action="?id=<?= $chatId ?>" class="portal-chat-compose"><?= tmq_csrf_field() ?><input type="hidden" name="chat_action" value="send"><input type="hidden" name="message_type" value="text"><label for="chat-body">Your message (up to 2,000 characters)</label><textarea id="chat-body" class="form-control" name="body" rows="3" maxlength="2000" required><?= tmq_escape(tmq_input($_POST,'body')) ?></textarea><button class="btn btn-primary" type="submit">Send message</button></form>
<details><summary>Send attachments</summary><form method="post" action="?id=<?= $chatId ?>" enctype="multipart/form-data"><?= tmq_csrf_field() ?><input type="hidden" name="chat_action" value="send"><input type="hidden" name="message_type" value="attachment"><label for="chat-files">Choose up to five files, maximum 10 MB each</label><input id="chat-files" type="file" name="attachments[]" multiple required accept=".pdf,.txt,.docx,.xlsx,.pptx,.jpg,.jpeg,.png,.mp4,.webm"><p>PDF, UTF-8 TXT, DOCX, XLSX, PPTX, JPEG, PNG, MP4 or WebM. Your server's upload/request limits also apply.</p><button class="btn btn-primary" type="submit">Send attachments</button></form></details>
<details><summary>Share a location</summary><p>Sharing a location does not change the agreed lesson venue. Use the booking arrangement or change-request workflow to agree a venue.</p><form method="post" action="?id=<?= $chatId ?>"><?= tmq_csrf_field() ?><input type="hidden" name="chat_action" value="send"><input type="hidden" name="message_type" value="location"><label for="chat-location-label">Venue or address</label><input id="chat-location-label" class="form-control" name="location_label" maxlength="500"><label for="chat-latitude">Latitude (optional)</label><input id="chat-latitude" class="form-control" name="latitude" inputmode="decimal"><label for="chat-longitude">Longitude (optional)</label><input id="chat-longitude" class="form-control" name="longitude" inputmode="decimal"><p>Enter a label, both coordinates, or both. Coordinates support six decimal places.</p><button class="btn btn-primary" type="submit">Share location</button></form></details>
<?php else: ?><p>This booking conversation is read-only. Its message history is preserved.</p><?php endif; ?></section>
<?php endif; ?><a class="booking-back-link" href="<?= $chatRole ?>-bookings.php">Back to bookings</a>
