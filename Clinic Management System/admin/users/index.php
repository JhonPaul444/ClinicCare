<?php
$pageTitle='Users'; $pageDescription='Manage system user accounts.'; $bodyClass='admin-app'; $appBasePath='../..'; $activeAdminPage='users';
$tableHeading='System users'; $tableFilters=['Admin','Receptionist','Doctor']; $tableFilterLabel='All roles'; $tableHeaders=['Name','Username','Role','Account status'];
$tableRows=[['Avery Morgan','admin.demo','Admin','Active'],['Taylor Reed','reception.demo','Receptionist','Active'],['Olivia Chen','doctor.chen','Doctor','Active'],['Marcus Lee','doctor.lee','Doctor','Inactive']];
require __DIR__.'/../../includes/header.php';
?>
<div class="app-layout"><?php require __DIR__.'/../../includes/sidebar.php'; ?><main class="app-main"><?php require __DIR__.'/../../includes/admin_topbar.php'; ?><div class="page-content">
    <section class="page-hero"><div><span class="hero-kicker">ADMINISTRATION</span><h1>Users</h1><p>Review role-based system accounts.</p></div><button class="btn hero-action" type="button" data-demo-action="User account creation">＋ <span>Add user</span></button></section>
    <div class="preview-notice"><span class="notice-dot"></span> Passwords and credentials are never shown in this preview</div>
    <?php require __DIR__.'/../../includes/admin_table.php'; ?>
</div></main></div>
<?php require __DIR__.'/../../includes/footer.php'; ?>
