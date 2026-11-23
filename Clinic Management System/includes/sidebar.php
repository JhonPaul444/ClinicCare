<?php
// Visual navigation only. Backend authorization must protect every destination.
$appBasePath = $appBasePath ?? '../..';
$activeAdminPage = $activeAdminPage ?? '';
$adminBase = $appBasePath . '/admin';
$sidebarItems = [
    ['label' => 'Dashboard', 'href' => $adminBase . '/dashboard.php', 'key' => 'dashboard', 'icon' => '▦'],
    ['label' => 'Appointments', 'href' => $adminBase . '/appointments/index.php', 'key' => 'appointments', 'icon' => '▣'],
    ['label' => 'Patients', 'href' => $adminBase . '/patients/index.php', 'key' => 'patients', 'icon' => '♙'],
    ['label' => 'Doctors', 'href' => $adminBase . '/doctors/index.php', 'key' => 'doctors', 'icon' => '✚'],
    ['label' => 'Scheduling', 'href' => $adminBase . '/schedules/index.php', 'key' => 'schedules', 'icon' => '◷'],
    ['label' => 'Specializations', 'href' => $adminBase . '/specializations/index.php', 'key' => 'specializations', 'icon' => '▤'],
    ['label' => 'Users', 'href' => $adminBase . '/users/index.php', 'key' => 'users', 'icon' => '♧'],
    ['label' => 'Reports', 'href' => $adminBase . '/reports/index.php', 'key' => 'reports', 'icon' => '▥'],
];
?>
<aside class="app-sidebar" id="admin-sidebar" aria-label="Admin navigation">
    <a class="brand-lockup sidebar-brand" href="<?php echo htmlspecialchars($adminBase . '/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>" aria-label="ClinicCare Admin dashboard">
        <span class="sidebar-monogram" aria-hidden="true">MC</span>
        <span class="sidebar-brand-copy"><strong>Admin Panel</strong><small>MedCare Clinic</small></span>
    </a>
    <nav class="sidebar-nav">
        <?php foreach ($sidebarItems as $item): ?>
            <?php $isCurrent = $activeAdminPage === $item['key']; ?>
            <a class="sidebar-link<?php echo $isCurrent ? ' is-current' : ''; ?>" href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isCurrent ? ' aria-current="page"' : ''; ?>>
                <span class="sidebar-icon" aria-hidden="true"><?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-user">
        <span class="avatar avatar-small">AD</span>
        <span><strong>Admin</strong><small>Administrator</small></span>
    </div>
</aside>
