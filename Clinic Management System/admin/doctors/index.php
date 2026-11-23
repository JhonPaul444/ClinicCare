<?php
$pageTitle='Doctors'; $pageDescription='Manage doctor profiles and specializations.'; $bodyClass='admin-app'; $appBasePath='../..'; $activeAdminPage='doctors';
$tableHeading='All doctors'; $tableFilters=['Active','Inactive']; $tableHeaders=['Doctor','Specialization','Profile status','Appointments'];
$tableRows=[['Dr. Olivia Chen','General Medicine','Active','38'],['Dr. Marcus Lee','Cardiology','Active','26'],['Dr. Amara Patel','Pediatrics','Active','31'],['Dr. Ethan Jones','Dermatology','Inactive','19']];
require __DIR__.'/../../includes/header.php';
?>
<div class="app-layout"><?php require __DIR__.'/../../includes/sidebar.php'; ?><main class="app-main"><?php require __DIR__.'/../../includes/admin_topbar.php'; ?><div class="page-content">
    <section class="page-hero"><div><span class="hero-kicker">CLINIC MANAGEMENT</span><h1>Doctors</h1><p>Manage doctor profiles and specialties.</p></div><button class="btn hero-action" type="button" data-demo-action="Doctor profile creation">＋ <span>Add doctor</span></button></section>
    <div class="preview-notice"><span class="notice-dot"></span> Fictional sample records for interface preview</div>
    <?php require __DIR__.'/../../includes/admin_table.php'; ?>
</div></main></div>
<?php require __DIR__.'/../../includes/footer.php'; ?>
