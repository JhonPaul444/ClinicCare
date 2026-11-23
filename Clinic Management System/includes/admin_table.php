<section class="data-card">
    <div class="data-card-heading">
        <div>
            <h2><?php echo htmlspecialchars($tableHeading ?? 'All records', ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo count($tableRows ?? []); ?> sample records</p>
        </div>
        <div class="table-tools">
            <?php if (!empty($tableFilters)): ?>
                <label class="filter-control"><span class="visually-hidden">Filter records</span>
                    <select class="form-select form-select-sm" data-table-filter>
                        <option value=""><?php echo htmlspecialchars($tableFilterLabel ?? 'All statuses', ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($tableFilters as $filter): ?><option value="<?php echo htmlspecialchars(strtolower($filter), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($filter, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <button class="btn btn-outline-secondary btn-sm export-button" type="button" data-export-table>
                <span aria-hidden="true">⇩</span> Export
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table clinic-table mb-0" data-clinic-table>
            <thead><tr><?php foreach ($tableHeaders as $heading): ?><th scope="col"><?php echo htmlspecialchars($heading, ENT_QUOTES, 'UTF-8'); ?></th><?php endforeach; ?></tr></thead>
            <tbody>
                <?php foreach ($tableRows as $row): ?>
                    <?php $rowText = implode(' ', array_map(static fn ($value) => (string) $value, $row)); ?>
                    <tr data-table-row data-search-text="<?php echo htmlspecialchars(strtolower($rowText), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php foreach ($row as $cell): ?>
                            <?php $cellString = (string) $cell; $statusClass = preg_replace('/[^a-z0-9]+/', '-', strtolower($cellString)); ?>
                            <td><?php if (in_array($cellString, $tableFilters ?? [], true)): ?><span class="status-pill <?php echo in_array($cellString, ['Admin','Receptionist','Doctor'], true) ? 'role-pill' : 'status-' . htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($cellString, ENT_QUOTES, 'UTF-8'); ?></span><?php else: ?><?php echo htmlspecialchars($cellString, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                <tr class="empty-table-row" data-empty-row hidden><td colspan="<?php echo max(1, count($tableHeaders)); ?>">No matching records. Try a different search or filter.</td></tr>
            </tbody>
        </table>
    </div>
    <div class="data-card-footer"><span>Preview data only</span><span>Backend connection required for live records</span></div>
</section>
