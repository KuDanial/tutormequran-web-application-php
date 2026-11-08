<div class="faq-accordion">
<?php foreach (array_slice($faqRows, 0, 50) as $faq): ?>
<details data-faq-id="<?= (int)$faq['faq_id'] ?>"><summary><?= tmq_escape($faq['question']) ?></summary><p><?= nl2br(tmq_escape($faq['answer'])) ?></p></details>
<?php endforeach; ?>
<?php if (!$faqRows): ?><p>No FAQs are available.</p><?php endif; ?>
</div>
<?php if (count($faqRows) > 50): ?><a class="btn btn-outline btn-sm" href="?after=<?= (int)$faqRows[49]['faq_id'] ?>">More FAQs</a><?php endif; ?>
