<?php
$pageTitle='Reports'; $pageDescription='Basic patient and appointment reports.'; $bodyClass='admin-app'; $appBasePath='../..'; $activeAdminPage='reports';
$tableHeading='Recent report summaries'; $tableFilters=[]; $tableHeaders=['Report','Period','Summary','Updated'];
$tableRows=[['Daily appointment list','Today','14 appointments scheduled','Today'],['Appointment status summary','This month','10 Scheduled · 3 Completed · 1 Cancelled','Today'],['Appointments by doctor','This month','8 doctors with appointments','Today'],['Patient registration summary','This month','12 fictional registrations','Today']];
require __DIR__.'/../../includes/header.php';
?>
<div class="app-layout"><?php require __DIR__.'/../../includes/sidebar.php'; ?><main class="app-main"><?php require __DIR__.'/../../includes/admin_topbar.php'; ?><div class="page-content">
    <section class="page-hero"><div><span class="hero-kicker">CLINIC MANAGEMENT</span><h1>Reports</h1><p>Review basic appointment and patient summaries.</p></div><button class="btn hero-action" type="button" data-demo-action="Report generation">＋ <span>New report</span></button></section>
    <div class="preview-notice"><span class="notice-dot"></span> Report values are examples; live SQL reports require backend integration</div>
    <section class="report-shortcuts"><button type="button" class="report-shortcut" data-report-filter="appointment"><span class="report-icon report-icon-blue">▦</span><span><strong>Daily appointments</strong><small>List appointments by date</small></span><span aria-hidden="true">→</span></button><button type="button" class="report-shortcut" data-report-filter="patient"><span class="report-icon report-icon-green">♙</span><span><strong>Patient summary</strong><small>Review patient totals</small></span><span aria-hidden="true">→</span></button><button type="button" class="report-shortcut" data-report-filter="doctor"><span class="report-icon report-icon-violet">✚</span><span><strong>Appointments by doctor</strong><small>Group bookings by doctor</small></span><span aria-hidden="true">→</span></button></section>
    <?php require __DIR__.'/../../includes/admin_table.php'; ?>
</div></main></div>
<?php require __DIR__.'/../../includes/footer.php'; ?>
