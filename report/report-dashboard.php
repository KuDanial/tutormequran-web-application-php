<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Report Dashboard - TutorMeQuran';
$pageDescription = 'Manage your TutorMeQuran activity.';
$currentPage = '../report/report-dashboard.php';
$adminPrefix = '../admin/';
$workspaceLabel = 'Reports';
$showAdminSettings = false;
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page admin-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<div class="admin-layout">
<?php require __DIR__ . '/../includes/admin-sidebar.php'; ?>
<div class="admin-body">
<?php require __DIR__ . '/../includes/admin-header.php'; ?>
<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Operations workspace</span>
<h1>Report Dashboard</h1>
<p>Manage your TutorMeQuran activity.</p>
</div>
<div class="portal-actions"><a class="btn btn-outline btn-sm" href="../admin/admin-dashboard.php">Back to Admin</a>
</div>
</header><div class="portal-actions"><button type="button" class="btn btn-primary">Export PDF</button><button type="button" class="btn btn-outline">Export Excel</button><button type="button" class="btn btn-outline">Print Report</button></div><p class="field-help">Export and print controls are interface previews.</p>

  <p class="portal-report-intro">Comparison report is used to compare booking performance between tutors. Composition report is used to show the percentage of payment status in the system.</p>
<section class="report-stat-grid" aria-label="Report overview"><article class="report-stat-card"><span class="report-stat-icon"><i class="fas fa-calendar-check" aria-hidden="true"></i></span><div><span>Total bookings</span><strong>60</strong><small>+12% vs September</small></div></article><article class="report-stat-card"><span class="report-stat-icon"><i class="fas fa-coins" aria-hidden="true"></i></span><div><span>Verified revenue</span><strong>RM 1,860</strong><small>45 verified payments</small></div></article><article class="report-stat-card"><span class="report-stat-icon"><i class="fas fa-user-check" aria-hidden="true"></i></span><div><span>Active tutors</span><strong>18</strong><small>14 publicly listed</small></div></article><article class="report-stat-card"><span class="report-stat-icon"><i class="fas fa-star" aria-hidden="true"></i></span><div><span>Average rating</span><strong>4.8</strong><small>Based on 36 reviews</small></div></article></section>
<section class="report-visual-grid" aria-label="Performance diagrams"><article class="portal-card report-chart-card"><div class="report-card-heading"><div><span class="section-subtitle">Trend</span><h2>Bookings over time</h2></div><span class="report-chart-legend"><i></i> Bookings</span></div><figure class="report-line-chart"><svg viewBox="0 0 640 240" role="img" aria-label="Bookings rose from 32 in July to 60 in October"><line x1="48" y1="20" x2="48" y2="200"/><line x1="48" y1="200" x2="610" y2="200"/><line x1="48" y1="155" x2="610" y2="155"/><line x1="48" y1="110" x2="610" y2="110"/><line x1="48" y1="65" x2="610" y2="65"/><polyline points="72,174 190,151 308,126 426,96 544,48"/><circle cx="72" cy="174" r="5"/><circle cx="190" cy="151" r="5"/><circle cx="308" cy="126" r="5"/><circle cx="426" cy="96" r="5"/><circle cx="544" cy="48" r="5"/><text x="61" y="222">Jun</text><text x="179" y="222">Jul</text><text x="297" y="222">Aug</text><text x="415" y="222">Sep</text><text x="533" y="222">Oct</text></svg></figure></article><article class="portal-card report-chart-card"><div class="report-card-heading"><div><span class="section-subtitle">Distribution</span><h2>Booking status</h2></div></div><div class="report-donut-wrap"><div class="report-donut" role="img" aria-label="Accepted 62 percent, pending 18 percent, completed 15 percent, cancelled 5 percent"><span><strong>60</strong><small>bookings</small></span></div><ul class="report-legend"><li><i class="accepted"></i>Accepted <strong>62%</strong></li><li><i class="pending"></i>Pending <strong>18%</strong></li><li><i class="completed"></i>Completed <strong>15%</strong></li><li><i class="cancelled"></i>Cancelled <strong>5%</strong></li></ul></div></article></section>
<section class="portal-card ">
<h2>Comparison Report</h2>
<figure class="portal-chart">
<figcaption>Total bookings by tutor · October 2026</figcaption>
<div class="portal-bar-row"><span>Ustaz Muhammad Farhan</span>
<div class="portal-bar-track">
<div class="portal-bar" style="width:100.0%">
</div>
</div><strong>24</strong>
</div>
<div class="portal-bar-row"><span>Ustazah Nur Aisyah</span>
<div class="portal-bar-track">
<div class="portal-bar" style="width:75.0%">
</div>
</div><strong>18</strong>
</div>
<div class="portal-bar-row"><span>Ustaz Ahmad Zaki</span>
<div class="portal-bar-track">
<div class="portal-bar" style="width:50.0%">
</div>
</div><strong>12</strong>
</div>
<div class="portal-bar-row"><span>Ustazah Siti Maryam</span>
<div class="portal-bar-track">
<div class="portal-bar" style="width:25.0%">
</div>
</div><strong>6</strong>
</div>
<p class="portal-muted">Scale: 0–24 bookings. Sample data.</p>
</figure>
<div class="portal-table-wrap" role="region" aria-label="Tutor booking performance" tabindex="0">
<table class="portal-table">
<caption>Tutor booking performance</caption>
<thead>
<tr>
<th scope="col">Tutor Name</th>
<th scope="col">Total Bookings</th>
<th scope="col">Verified Payments</th>
<th scope="col">Total Revenue</th>
</tr>
</thead>
<tbody>
<tr>
<td>Ustaz Muhammad Farhan</td>
<td>24</td>
<td>18</td>
<td>RM 720.00</td>
</tr>
<tr>
<td>Ustazah Nur Aisyah</td>
<td>18</td>
<td>15</td>
<td>RM 675.00</td>
</tr>
<tr>
<td>Ustaz Ahmad Zaki</td>
<td>12</td>
<td>9</td>
<td>RM 315.00</td>
</tr>
<tr>
<td>Ustazah Siti Maryam</td>
<td>6</td>
<td>3</td>
<td>RM 150.00</td>
</tr>
</tbody>
</table>
</div>
<p class="portal-muted">Total revenue includes verified payments only. Totals: 60 bookings, 45 verified payments, RM 1,860.00 revenue.</p>
</section>
<section class="portal-card ">
<h2>Composition Report</h2><figure class="portal-composition"><div class="portal-donut payment-composition-five" role="img" aria-label="Verified 75%, Pending 10%, Underpaid 5%, Rejected 5%, Reupload Required 5%"><span><strong>60</strong>payments</span></div><figcaption>October 2026 · five payment review statuses</figcaption></figure><div class="portal-table-wrap" role="region" aria-label="Payment composition" tabindex="0"><table class="portal-table"><caption>Payment status composition</caption><thead><tr><th scope="col">Payment Status</th><th scope="col">Total</th><th scope="col">Percentage</th></tr></thead><tbody><tr><td><span class="portal-badge good">Verified</span></td><td>45</td><td>75%</td></tr><tr><td><span class="portal-badge pending">Pending</span></td><td>6</td><td>10%</td></tr><tr><td><span class="portal-badge pending">Underpaid</span></td><td>3</td><td>5%</td></tr><tr><td><span class="portal-badge pending">Rejected</span></td><td>3</td><td>5%</td></tr><tr><td><span class="portal-badge pending">Reupload Required</span></td><td>3</td><td>5%</td></tr></tbody></table></div><p class="field-help">Only Verified payments contribute to revenue. Pending, Underpaid, Rejected and Reupload Required payments are excluded.</p></section>

</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
