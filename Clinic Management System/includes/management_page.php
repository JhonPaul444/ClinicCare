<?php
$pageTitle = $pageTitle ?? 'Clinic records';
$pageDescription = $pageDescription ?? 'Manage clinic records.';
$appBasePath = $appBasePath ?? '../..';
$appRole = $appRole ?? 'Admin';
$activePage = $activePage ?? '';
$pageKey = $pageKey ?? '';
$actionLabel = $actionLabel ?? '';
$pageIntro = $pageIntro ?? '';
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
                <section class="report-shortcuts"><div class="report-shortcut"><span class="report-icon report-icon-blue">▦</span><span><strong>Appointment summary</strong><small>Filter by date range below</small></span></div><div class="report-shortcut"><span class="report-icon report-icon-green">♙</span><span><strong>Patient totals</strong><small>Live counts from the database</small></span></div><div class="report-shortcut"><span class="report-icon report-icon-violet">✚</span><span><strong>Appointments by doctor</strong><small>Grouped using live records</small></span></div></section>
            <?php endif; ?>
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
                        <?php if ($pageKey === 'reports'): ?><label class="filter-control"><span class="visually-hidden">Start date</span><input class="form-control form-control-sm" type="date" data-report-from></label><label class="filter-control"><span class="visually-hidden">End date</span><input class="form-control form-control-sm" type="date" data-report-to></label><button class="btn btn-outline-secondary btn-sm" type="button" data-report-refresh>Apply dates</button><?php endif; ?>
                        <?php if ($pageKey !== 'reports' && $pageKey !== 'medical-records'): ?><button class="btn btn-outline-secondary btn-sm" type="button" data-export-table>Export CSV</button><?php endif; ?>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-refresh>Refresh</button>
                    </div>
                </div>
                <div class="table-responsive" data-table-region aria-live="polite"><table class="table clinic-table mb-0" data-clinic-table><thead><tr data-table-head></tr></thead><tbody data-table-body><tr><td>Loading…</td></tr></tbody></table></div>
                <div class="data-card-footer"><span>Live records from ClinicCare</span><span>Updates save to the database</span></div>
            </section>
        </div>
    </main>
</div>
<?php require __DIR__ . '/footer.php'; ?>
