<?php
$appBasePath = $appBasePath ?? '../..';
$appRole = $appRole ?? 'Admin';
$activePage = $activePage ?? $activeAdminPage ?? 'dashboard';
$root = rtrim($appBasePath, '/');
$roleItems = [
    'Admin' => [
        ['Dashboard', 'admin/dashboard.php', 'dashboard', '▦'],
        ['Appointments', 'admin/appointments/index.php', 'appointments', '▣'],
        ['Patients', 'admin/patients/index.php', 'patients', '♙'],
        ['Doctors', 'admin/doctors/index.php', 'doctors', '✚'],
        ['Scheduling', 'admin/schedules/index.php', 'schedules', '◷'],
        ['Specializations', 'admin/specializations/index.php', 'specializations', '▤'],
        ['Users', 'admin/users/index.php', 'users', '♧'],
        ['Reports', 'admin/reports/index.php', 'reports', '▥'],
    ],
    'Receptionist' => [
        ['Dashboard', 'receptionist/dashboard.php', 'dashboard', '▦'],
        ['Appointments', 'receptionist/appointments/index.php', 'appointments', '▣'],
        ['Patients', 'receptionist/patients/index.php', 'patients', '♙'],
        ['Reports', 'receptionist/reports/index.php', 'reports', '▥'],
    ],
    'Doctor' => [
        ['Dashboard', 'doctor/dashboard.php', 'dashboard', '▦'],
        ['Appointments', 'doctor/appointments/index.php', 'appointments', '▣'],
        ['Medical records', 'doctor/medical-records/index.php', 'medical-records', '▤'],
    ],
];
$items = $roleItems[$appRole] ?? $roleItems['Admin'];
$displayName = (string)($currentUser['full_name'] ?? $appRole);
$initials = strtoupper(implode('', array_map(static fn($part) => substr($part, 0, 1), array_slice(preg_split('/\s+/', trim($displayName)) ?: [], 0, 2))));
?>
<aside class="app-sidebar" id="admin-sidebar" aria-label="<?php echo htmlspecialchars($appRole, ENT_QUOTES, 'UTF-8'); ?> navigation">
    <a class="brand-lockup sidebar-brand" href="<?php echo htmlspecialchars($root . '/' . ($appRole === 'Admin' ? 'admin/dashboard.php' : strtolower($appRole) . '/dashboard.php'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="ClinicCare dashboard">
        <span class="sidebar-monogram" aria-hidden="true">CC</span>
        <span class="sidebar-brand-copy"><strong>ClinicCare</strong><small><?php echo htmlspecialchars($appRole . ' portal', ENT_QUOTES, 'UTF-8'); ?></small></span>
    </a>
    <nav class="sidebar-nav">
        <?php foreach ($items as [$label, $path, $key, $icon]): ?>
            <?php $isCurrent = $activePage === $key; ?>
            <a class="sidebar-link<?php echo $isCurrent ? ' is-current' : ''; ?>" href="<?php echo htmlspecialchars($root . '/' . $path, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isCurrent ? ' aria-current="page"' : ''; ?>>
                <span class="sidebar-icon" aria-hidden="true"><?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?></span>
                <span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-user">
        <span class="avatar avatar-small"><?php echo htmlspecialchars($initials !== '' ? $initials : 'CC', ENT_QUOTES, 'UTF-8'); ?></span>
        <span><strong><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($appRole, ENT_QUOTES, 'UTF-8'); ?></small></span>
    </div>
</aside>
