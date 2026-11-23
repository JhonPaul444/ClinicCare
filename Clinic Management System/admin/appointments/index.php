<?php
$pageTitle='Appointments'; $pageDescription='Monitor clinic appointments.'; $bodyClass='admin-app'; $appBasePath='../..'; $activeAdminPage='appointments';
$tableHeading='All appointments'; $tableFilters=['Scheduled','Completed','Cancelled','No-show'];
$tableHeaders=['Patient','Doctor','Date & time','Reason','Status'];
$tableRows=[['Emma Wilson','Dr. Olivia Chen','Today, 9:30 AM','Annual checkup','Scheduled'],['James Miller','Dr. Marcus Lee','Today, 11:00 AM','Follow-up visit','Completed'],['Sophia Brown','Dr. Olivia Chen','Tomorrow, 2:15 PM','Skin consultation','Scheduled'],['Noah Davis','Dr. Amara Patel','Mar 28, 10:00 AM','New patient visit','Cancelled']];
require __DIR__.'/../../includes/header.php';
?>
<div class="app-layout"><?php require __DIR__.'/../../includes/sidebar.php'; ?><main class="app-main"><?php require __DIR__.'/../../includes/admin_topbar.php'; ?><div class="page-content">
    <section class="page-hero"><div><span class="hero-kicker">CLINIC MANAGEMENT</span><h1>Appointments</h1><p>View and manage patient appointments.</p></div><button class="btn hero-action" type="button" data-demo-action="Appointment creation">＋ <span>New appointment</span></button></section>
    <div class="preview-notice"><span class="notice-dot"></span> Status values follow the approved appointment workflow</div>
    <?php require __DIR__.'/../../includes/admin_table.php'; ?>
</div></main></div>
<?php require __DIR__.'/../../includes/footer.php'; ?>
