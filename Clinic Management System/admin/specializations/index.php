<?php
$pageTitle='Specializations'; $pageDescription='Manage clinic medical specializations.'; $bodyClass='admin-app'; $appBasePath='../..'; $activeAdminPage='specializations';
$tableHeading='Medical specializations'; $tableFilters=['Active','Inactive']; $tableHeaders=['Specialization','Description','Doctors','Status'];
$tableRows=[];
require __DIR__.'/../../includes/header.php';
?>
<div class="app-layout"><?php require __DIR__.'/../../includes/sidebar.php'; ?><main class="app-main"><?php require __DIR__.'/../../includes/admin_topbar.php'; ?><div class="page-content">
    <section class="page-hero"><div><span class="hero-kicker">CLINIC MANAGEMENT</span><h1>Specializations</h1><p>Manage the medical specialties offered by the clinic.</p></div><button class="btn hero-action" type="button" data-demo-action="Specialization creation">＋ <span>Add specialization</span></button></section>
    <div class="preview-notice"><span class="notice-dot"></span> No specializations have been added yet.</div>
    <?php require __DIR__.'/../../includes/admin_table.php'; ?>
</div></main></div>
<?php require __DIR__.'/../../includes/footer.php'; ?>
