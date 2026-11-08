<?php
require_once __DIR__ . '/../includes/config.php'; // Existing active-Admin guard runs before output.
require_once __DIR__ . '/../includes/reports.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit('Use GET to view or export reports.');
}
try {
    $month = $_GET['month'] ?? '';
    $range = tmq_report_month($month);
    $export = $_GET['export'] ?? '';
    if (!is_string($export) || !in_array($export, ['', 'csv'], true)) {
        throw new DomainException('Choose CSV export.');
    }
} catch (DomainException $error) {
    http_response_code(422);
    exit(tmq_escape($error->getMessage()));
}
try {
    $report = tmq_report_data(tmq_db(), $range);
} catch (Throwable $error) {
    http_response_code(503);
    exit('Reports are temporarily unavailable. Please try again later.');
}
if ($export === 'csv') { tmq_report_csv($report, $month); }
$period = $month ?: 'All time';
$maxBookings = $report['comparison'] ? (int)$report['comparison'][0]['total_bookings'] : 0;
$colors = ['#3b7c64', '#a16207', '#b91c1c', '#64748b', '#14b8a6'];
$stops = [];
$cumulative = 0;
foreach ($report['composition'] as $index => $row) {
    $start = number_format($cumulative * 100 / $report['payment_total'], 4, '.', '');
    $cumulative += $row['payment_count'];
    $end = number_format($cumulative * 100 / $report['payment_total'], 4, '.', '');
    $stops[] = $colors[$index % count($colors)] . " $start% $end%";
}
$donut = $stops ? 'conic-gradient(' . implode(', ', $stops) . ')' : '#e5e7eb';
$assetPrefix = '../';
$pageTitle = 'Report Dashboard - TutorMeQuran';
$pageDescription = 'Total bookings by Tutor and payment status composition.';
$currentPage = '../report/report-dashboard.php';
$adminPrefix = '../admin/';
$workspaceLabel = 'Reports';
$showAdminSettings = false;
require __DIR__ . '/../includes/header.php';
?>
<body class="portal-page admin-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<div class="admin-layout">
<?php require __DIR__ . '/../includes/admin-sidebar.php'; ?>
<div class="admin-body">
<?php require __DIR__ . '/../includes/admin-header.php'; ?>
<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Operations workspace</span><h1>Report Dashboard</h1><p><?= tmq_escape($pageDescription) ?></p></div>
<div class="portal-actions"><a class="btn btn-outline btn-sm" href="../admin/admin-dashboard.php">Back to Admin</a></div>
</header>
<form method="get" class="portal-actions">
<label for="report-month">Record month (Malaysia)</label>
<input id="report-month" name="month" type="month" min="1000-01" max="9999-12" value="<?= tmq_escape($month) ?>">
<button class="btn btn-outline" type="submit">Apply</button>
<a class="btn btn-outline" href="report-dashboard.php">All time</a>
</form>
<div class="portal-actions"><a class="btn btn-primary" href="?<?= tmq_escape(http_build_query(['month' => $month, 'export' => 'csv'])) ?>">Export CSV</a></div>
<p class="portal-report-intro">Period: <?= tmq_escape($period) ?>. Comparison uses booking creation dates; composition uses payment creation dates. Month boundaries use Malaysia time. Leave the month empty for all time.</p>
<section class="portal-card">
<h2>Comparison Report</h2>
<figure class="portal-chart"><figcaption>Total Bookings by Tutor &middot; <?= tmq_escape($period) ?></figcaption>
<?php foreach ($report['comparison'] as $row): ?>
<div class="portal-bar-row"><span><?= tmq_escape($row['tutor_name']) ?></span><div class="portal-bar-track"><div class="portal-bar" style="width:<?= number_format($row['total_bookings'] * 100 / $maxBookings, 2, '.', '') ?>%"></div></div><strong><?= (int)$row['total_bookings'] ?></strong></div>
<?php endforeach; ?>
</figure>
<div class="portal-table-wrap" role="region" aria-label="Tutor booking performance" tabindex="0">
<table class="portal-table" id="booking-comparison"><caption>Total Bookings by Tutor</caption>
<thead><tr><th scope="col">Tutor ID</th><th scope="col">Tutor Name</th><th scope="col">Total Bookings</th></tr></thead><tbody>
<?php foreach ($report['comparison'] as $row): ?>
<tr><td><?= (int)$row['tutor_id'] ?></td><td><?= tmq_escape($row['tutor_name']) ?></td><td><?= (int)$row['total_bookings'] ?></td></tr>
<?php endforeach; ?>
<?php if (!$report['comparison']): ?><tr><td colspan="3">No bookings in this period.</td></tr><?php endif; ?>
</tbody></table></div>
<p class="portal-muted">All booking statuses are included, including rejected and cancelled bookings. Historical Tutors remain included even if now private or inactive.</p>
</section>
<section class="portal-card">
<h2>Composition Report</h2>
<figure class="portal-composition"><div class="portal-donut" style="background:<?= tmq_escape($donut) ?>" role="img" aria-label="Payment status composition; counts and percentages in the table below"><span><strong><?= (int)$report['payment_total'] ?></strong>payments</span></div><figcaption><?= tmq_escape($period) ?> &middot; payment status composition</figcaption></figure>
<div class="portal-table-wrap" role="region" aria-label="Payment composition" tabindex="0">
<table class="portal-table" id="payment-composition"><caption>Payment Status Composition</caption>
<thead><tr><th scope="col">Payment Status</th><th scope="col">Total</th><th scope="col">Percentage</th></tr></thead><tbody>
<?php foreach ($report['composition'] as $row): ?>
<tr><td><?= tmq_escape($row['status']) ?></td><td><?= (int)$row['payment_count'] ?></td><td><?= tmq_escape($row['percentage']) ?>%</td></tr>
<?php endforeach; ?>
<?php if (!$report['composition']): ?><tr><td colspan="3">No payments in this period.</td></tr><?php endif; ?>
</tbody></table></div>
<p class="field-help">Each payment record is counted once. Percentages use the payment total for this period and are rounded to two decimal places.</p>
</section>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div></div><script src="../assets/js/action-confirmation.js"></script></body></html>
