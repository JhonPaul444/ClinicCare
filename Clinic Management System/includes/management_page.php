<?php
$pageTitle = $pageTitle ?? 'Clinic records';
$pageDescription = $pageDescription ?? 'Manage clinic records.';
$appBasePath = $appBasePath ?? '../..';
$appRole = $appRole ?? 'Admin';
$activePage = $activePage ?? '';
$pageKey = $pageKey ?? '';
$actionLabel = $actionLabel ?? '';
$pageIntro = $pageIntro ?? '';
if ($pageKey === 'reports') $pageIntro = 'Monitor clinic appointments, patient statistics, and doctor performance.';
$pageFilters = $pageFilters ?? [];
?>
<?php require __DIR__ . '/header.php'; ?>
<div class="app-layout">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <main class="app-main">
        <?php require __DIR__ . '/admin_topbar.php'; ?>
        <div class="page-content" data-clinic-page="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>" data-app-role="<?php echo htmlspecialchars($appRole, ENT_QUOTES, 'UTF-8'); ?>" data-api-base="<?php echo htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8'); ?>">
            <section class="page-hero">
                <div><span class="hero-kicker">CLINIC MANAGEMENT</span><h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1><p><?php echo htmlspecialchars($pageIntro, ENT_QUOTES, 'UTF-8'); ?></p></div>
                <?php if ($actionLabel !== ''): ?><button class="btn hero-action" type="button" data-open-form>＋ <span><?php echo htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8'); ?></span></button><?php endif; ?>
            </section>
            <div class="alert d-none" role="status" aria-live="polite" data-feedback></div>
            <?php if ($pageKey === 'reports'): ?>
                <section class="report-filter-card" aria-label="Report filters">
                    <div class="report-presets" role="group" aria-label="Quick date range">
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-report-preset="today">Today</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-report-preset="week">This Week</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-report-preset="month">This Month</button>
                        <button class="btn btn-outline-secondary btn-sm active" type="button" data-report-preset="custom" aria-pressed="true">Custom Date Range</button>
                    </div>
                    <div class="report-filter-controls">
                        <label class="filter-control"><span class="visually-hidden">Start date</span><input class="form-control form-control-sm" type="date" data-report-from aria-label="Start date"></label>
                        <span class="report-date-separator" aria-hidden="true">to</span>
                        <label class="filter-control"><span class="visually-hidden">End date</span><input class="form-control form-control-sm" type="date" data-report-to aria-label="End date"></label>
                        <button class="btn btn-primary btn-sm" type="button" data-report-apply>Apply dates</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-report-reset>Reset</button>
                        <span class="report-filter-spacer"></span>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-report-csv>Export CSV</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-report-pdf>Export PDF</button>
                    </div>
                </section>
                <section class="metric-grid report-metric-grid" aria-label="Appointment summary">
                    <article class="metric-card"><div class="metric-top"><span>Total Appointments</span><span class="metric-icon metric-blue" aria-hidden="true">▦</span></div><strong data-report-stat="appointments">—</strong><small>In the selected date range</small></article>
                    <article class="metric-card"><div class="metric-top"><span>Total Patients</span><span class="metric-icon metric-violet" aria-hidden="true">♙</span></div><strong data-report-stat="patients">—</strong><small>Registered patient records</small></article>
                    <article class="metric-card"><div class="metric-top"><span>Pending Appointments</span><span class="metric-icon metric-cyan" aria-hidden="true">◷</span></div><strong data-report-stat="pending">—</strong><small>Scheduled in this date range</small></article>
                    <article class="metric-card"><div class="metric-top"><span>Completed Appointments</span><span class="metric-icon metric-teal" aria-hidden="true">✓</span></div><strong data-report-stat="completed">—</strong><small>Completed in this date range</small></article>
                </section>
                <section class="data-card report-chart-card">
                    <div class="data-card-heading report-section-heading"><div><h2>Appointment trends</h2><p>Appointments grouped by the selected interval and date range.</p></div><label class="filter-control report-view-control"><span class="visually-hidden">Trend interval</span><select class="form-select form-select-sm" data-report-granularity aria-label="Trend interval"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select></label></div>
                    <div class="report-chart-wrap"><div class="report-chart" data-report-chart role="img" aria-label="Appointment counts chart"></div><p class="report-empty-chart d-none" data-report-chart-empty>No appointments match this date range.</p></div>
                </section>
                <section class="data-card report-doctor-card">
                    <div class="data-card-heading report-section-heading"><div><h2>Appointments by doctor</h2><p>Appointment totals and status breakdown for the selected date range.</p></div></div>
                    <div class="table-responsive"><table class="table clinic-table mb-0"><thead><tr><th scope="col">Doctor Name</th><th scope="col">Specialization</th><th scope="col">Total Appointments</th><th scope="col">Pending Appointments</th><th scope="col">Completed Appointments</th></tr></thead><tbody data-report-doctors><tr><td colspan="5" class="text-center">Loading report…</td></tr></tbody></table></div>
                    <div class="data-card-footer"><span>Live aggregates from ClinicCare</span><span data-report-period></span></div>
                </section>
                <div class="report-print-header" data-report-print-header></div>
            <?php else: ?>
            <form class="data-card p-3 d-none" data-record-form novalidate></form>
            <section class="data-card" data-record-card>
                <div class="data-card-heading">
                    <div><h2><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h2><p data-record-count>Loading records…</p></div>
                    <div class="table-tools">
                        <?php if (in_array($pageKey, ['patients','appointments','users','doctors','specializations','schedules','medical-records'], true)): ?>
                            <label class="filter-control<?php echo $appRole === 'Receptionist' && $pageKey === 'appointments' ? ' appointment-search' : ''; ?>"><span class="visually-hidden">Search records</span><input class="form-control form-control-sm" type="search" placeholder="Search records" data-record-search></label>
                            <?php if ($appRole === 'Receptionist' && $pageKey === 'appointments'): ?><button class="btn btn-outline-secondary btn-sm appointment-search-submit" type="button" data-search-submit>Search</button><?php endif; ?>
                        <?php endif; ?>
                        <?php if ($pageKey === 'appointments'): ?><label class="filter-control"><span class="visually-hidden">Filter status</span><select class="form-select form-select-sm" data-record-filter><option value="">All statuses</option><option>Scheduled</option><option>Completed</option><option>Cancelled</option><option>No-show</option></select></label><input class="form-control form-control-sm" type="date" aria-label="Filter appointment date" data-date-filter><?php endif; ?>
                        <?php if ($pageKey !== 'medical-records'): ?><button class="btn btn-outline-secondary btn-sm" type="button" data-export-table>Export CSV</button><?php endif; ?>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-refresh>Refresh</button>
                    </div>
                </div>
                <div class="table-responsive" data-table-region aria-live="polite"><table class="table clinic-table mb-0" data-clinic-table><thead><tr data-table-head></tr></thead><tbody data-table-body><tr><td>Loading…</td></tr></tbody></table></div>
                <div class="data-card-footer"><span>Live records from ClinicCare</span><span>Updates save to the database</span></div>
            </section>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require __DIR__ . '/footer.php'; ?>
