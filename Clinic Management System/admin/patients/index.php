<?php
$pageTitle='Patients'; $pageDescription='Review patient profiles.'; $bodyClass='admin-app'; $appBasePath='../..'; $activeAdminPage='patients';
$tableHeading='All patients'; $tableFilters=[]; $tableHeaders=['Patient','Patient ID','Last visit','Contact','Address'];
$tableRows=[['Emma Wilson','#PT-1042','Mar 21, 2025','(555) 201-4190','Sample address'],['James Miller','#PT-1041','Mar 19, 2025','(555) 814-2271','Sample address'],['Sophia Brown','#PT-1039','Mar 15, 2025','(555) 621-9024','Sample address'],['Noah Davis','#PT-1037','Mar 10, 2025','(555) 331-1187','Sample address']];
require __DIR__.'/../../includes/header.php';
?>
<div class="app-layout"><?php require __DIR__.'/../../includes/sidebar.php'; ?><main class="app-main"><?php require __DIR__.'/../../includes/admin_topbar.php'; ?><div class="page-content">
    <section class="page-hero"><div><span class="hero-kicker">CLINIC MANAGEMENT</span><h1>Patients</h1><p>Access patient profiles and contact information.</p></div><button class="btn hero-action" type="button" data-demo-action="Patient registration">＋ <span>Add patient</span></button></section>
    <div class="preview-notice"><span class="notice-dot"></span> Fictional sample records for interface preview</div>
    <?php require __DIR__.'/../../includes/admin_table.php'; ?>
</div></main></div>
<?php require __DIR__.'/../../includes/footer.php'; ?>
