<?php
$pageTitle='Patients'; $pageDescription='Review patient profiles.'; $bodyClass='admin-app'; $appBasePath='../..'; $activeAdminPage='patients';
$tableHeading='All patients'; $tableFilters=[]; $tableHeaders=['Patient','Patient ID','Last visit','Contact','Address'];
$tableRows=[];
require __DIR__.'/../../includes/header.php';
?>
<div class="app-layout"><?php require __DIR__.'/../../includes/sidebar.php'; ?><main class="app-main"><?php require __DIR__.'/../../includes/admin_topbar.php'; ?><div class="page-content">
    <section class="page-hero"><div><span class="hero-kicker">CLINIC MANAGEMENT</span><h1>Patients</h1><p>Access patient profiles and contact information.</p></div><button class="btn hero-action" type="button" data-demo-action="Patient registration">＋ <span>Add patient</span></button></section>
    <div class="preview-notice"><span class="notice-dot"></span> No patient records have been added yet.</div>
    <?php require __DIR__.'/../../includes/admin_table.php'; ?>
</div></main></div>
<?php require __DIR__.'/../../includes/footer.php'; ?>
