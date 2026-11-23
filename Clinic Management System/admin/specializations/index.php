<?php
$pageTitle='Specializations'; $pageDescription='Manage clinic medical specializations.'; $bodyClass='admin-app'; $appBasePath='../..'; $activeAdminPage='specializations';
$tableHeading='Medical specializations'; $tableFilters=['Active','Inactive']; $tableHeaders=['Specialization','Description','Doctors','Status'];
$tableRows=[['General Medicine','Primary care and routine consultations','3','Active'],['Pediatrics','Care for infants, children, and adolescents','2','Active'],['Dermatology','Skin, hair, and nail consultations','1','Active'],['Cardiology','Heart and cardiovascular consultations','2','Inactive']];
require __DIR__.'/../../includes/header.php';
?>
<div class="app-layout"><?php require __DIR__.'/../../includes/sidebar.php'; ?><main class="app-main"><?php require __DIR__.'/../../includes/admin_topbar.php'; ?><div class="page-content">
    <section class="page-hero"><div><span class="hero-kicker">CLINIC MANAGEMENT</span><h1>Specializations</h1><p>Manage the medical specialties offered by the clinic.</p></div><button class="btn hero-action" type="button" data-demo-action="Specialization creation">＋ <span>Add specialization</span></button></section>
    <div class="preview-notice"><span class="notice-dot"></span> Fictional sample records for interface preview</div>
    <?php require __DIR__.'/../../includes/admin_table.php'; ?>
</div></main></div>
<?php require __DIR__.'/../../includes/footer.php'; ?>
