<?php
$pageTitle = 'Dashboard'; $pageDescription = 'Admin overview of ClinicCare appointments, patients, and doctors.'; $bodyClass = 'admin-app'; $appBasePath = '..'; $activeAdminPage = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="app-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="app-main">
        <?php require __DIR__ . '/../includes/admin_topbar.php'; ?>
        <div class="page-content">
            <section class="page-hero dashboard-hero"><div><span class="hero-kicker">CLINIC MANAGEMENT</span><h1>Dashboard</h1><p>Welcome back to your clinic dashboard.</p></div><span class="hero-watermark" aria-hidden="true">▦</span></section>
            <div class="preview-notice"><span class="notice-dot"></span> No clinic records have been added yet.</div>
            <section class="metric-grid" aria-label="Clinic summary">
                <article class="metric-card"><div class="metric-top"><span>Appointments</span><span class="metric-icon metric-blue">▦</span></div><strong>0</strong><small><span class="metric-positive">Today</span> scheduled appointments</small></article>
                <article class="metric-card"><div class="metric-top"><span>Patients</span><span class="metric-icon metric-violet">♙</span></div><strong>0</strong><small>Registered patient profiles</small></article>
                <article class="metric-card"><div class="metric-top"><span>Doctors</span><span class="metric-icon metric-teal">✚</span></div><strong>0</strong><small>Active doctor profiles</small></article>
                <article class="metric-card"><div class="metric-top"><span>Scheduled</span><span class="metric-icon metric-cyan">◷</span></div><strong>0</strong><small>Upcoming appointments</small></article>
            </section>
            <section class="quick-actions-card">
                <div class="section-heading"><div><h2>Quick actions</h2><p>Common clinic management tasks</p></div><span class="soft-tag">FAST ACCESS</span></div>
                <div class="quick-action-grid">
                    <a class="quick-action quick-action-blue" href="patients/index.php"><span class="quick-action-icon">＋</span><strong>View patients</strong><small>Search patient profiles</small><span class="quick-arrow">↗</span></a>
                    <a class="quick-action quick-action-green" href="appointments/index.php"><span class="quick-action-icon">▦</span><strong>Appointments</strong><small>Review clinic bookings</small><span class="quick-arrow">↗</span></a>
                    <a class="quick-action quick-action-purple" href="reports/index.php"><span class="quick-action-icon">▤</span><strong>View reports</strong><small>Open basic summaries</small><span class="quick-arrow">↗</span></a>
                </div>
            </section>
            <section class="data-card dashboard-appointments">
                <div class="data-card-heading"><div><h2>Recent appointments</h2><p>Latest clinic bookings</p></div><a class="btn btn-outline-secondary btn-sm" href="appointments/index.php">View all <span aria-hidden="true">→</span></a></div>
                <div class="table-responsive"><table class="table clinic-table mb-0"><thead><tr><th>Patient</th><th>Doctor</th><th>Date &amp; time</th><th>Status</th></tr></thead><tbody>
                    <tr><td colspan="4" class="text-center">No appointments yet.</td></tr>
                </tbody></table></div>
            </section>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
