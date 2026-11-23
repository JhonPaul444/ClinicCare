<?php
$pageTitle='Scheduling'; $pageDescription='Review doctor availability schedules.'; $bodyClass='admin-app'; $appBasePath='../..'; $activeAdminPage='schedules';
$tableHeading='Doctor schedules'; $tableFilters=['Active','Inactive']; $tableHeaders=['Doctor','Available day','Working hours','Schedule status'];
$tableRows=[['Dr. Olivia Chen','Monday','9:00 AM – 1:00 PM','Active'],['Dr. Marcus Lee','Tuesday','10:00 AM – 4:00 PM','Active'],['Dr. Amara Patel','Wednesday','9:00 AM – 2:00 PM','Active'],['Dr. Ethan Jones','Friday','1:00 PM – 5:00 PM','Inactive']];
require __DIR__.'/../../includes/header.php';
?>
<div class="app-layout"><?php require __DIR__.'/../../includes/sidebar.php'; ?><main class="app-main"><?php require __DIR__.'/../../includes/admin_topbar.php'; ?><div class="page-content">
    <section class="page-hero"><div><span class="hero-kicker">CLINIC MANAGEMENT</span><h1>Scheduling</h1><p>Review doctor working days and available hours.</p></div><button class="btn hero-action" type="button" data-demo-action="Schedule management">＋ <span>Create schedule</span></button></section>
    <div class="preview-notice"><span class="notice-dot"></span> Schedule validation and conflict checks belong to the backend</div>
    <?php require __DIR__.'/../../includes/admin_table.php'; ?>
</div></main></div>
<?php require __DIR__.'/../../includes/footer.php'; ?>
