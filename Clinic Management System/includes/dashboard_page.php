<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$pageDescription = $pageDescription ?? 'Clinic summary.';
$appBasePath = $appBasePath ?? '..';
$appRole = $appRole ?? 'Admin';
$activePage = 'dashboard';
$pageKey = 'dashboard';
?>
<?php require __DIR__ . '/header.php'; ?>
<div class="app-layout">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <main class="app-main">
        <?php require __DIR__ . '/admin_topbar.php'; ?>
        <div class="page-content" data-clinic-page="dashboard" data-dashboard-role="<?php echo htmlspecialchars($appRole, ENT_QUOTES, 'UTF-8'); ?>" data-api-base="<?php echo htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8'); ?>">
            <section class="page-hero dashboard-hero"><div><span class="hero-kicker">CLINIC MANAGEMENT</span><h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1><p><?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?></p></div><span class="hero-watermark" aria-hidden="true">▦</span></section>
            <div class="alert d-none" role="status" aria-live="polite" data-feedback></div>
            <section class="metric-grid" aria-label="Clinic summary" data-dashboard-metrics>
                <article class="metric-card"><div class="metric-top"><span data-stat-label>Patients</span><span class="metric-icon metric-violet">♙</span></div><strong data-stat="patients_total">—</strong><small>Registered patients</small></article>
                <article class="metric-card"><div class="metric-top"><span data-stat-label>Active doctors</span><span class="metric-icon metric-teal">✚</span></div><strong data-stat="doctors_active">—</strong><small>Available doctor profiles</small></article>
                <article class="metric-card"><div class="metric-top"><span data-stat-label>Appointments today</span><span class="metric-icon metric-blue">▦</span></div><strong data-stat="appointments_today">—</strong><small>Bookings for today</small></article>
                <article class="metric-card"><div class="metric-top"><span data-stat-label>Upcoming</span><span class="metric-icon metric-cyan">◷</span></div><strong data-stat="appointments_upcoming">—</strong><small>Scheduled appointments</small></article>
            </section>
            <section class="data-card dashboard-appointments">
                <div class="data-card-heading"><div><h2>Upcoming appointments</h2><p>Next scheduled clinic visits</p></div><a class="btn btn-outline-secondary btn-sm" href="<?php echo htmlspecialchars(rtrim($appBasePath, '/') . '/' . ($appRole === 'Admin' ? 'admin/appointments/index.php' : strtolower($appRole) . '/appointments/index.php'), ENT_QUOTES, 'UTF-8'); ?>">View appointments →</a></div>
                <div class="table-responsive"><table class="table clinic-table mb-0"><thead><tr><th>Patient</th><th>Doctor</th><th>Date &amp; time</th><th>Status</th></tr></thead><tbody data-recent-appointments><tr><td colspan="4">Loading…</td></tr></tbody></table></div>
            </section>
        </div>
    </main>
</div>
<?php require __DIR__ . '/footer.php'; ?>
