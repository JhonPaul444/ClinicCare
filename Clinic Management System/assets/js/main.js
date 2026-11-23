(() => {
    'use strict';

    const showToast = (message) => {
        const toastElement = document.querySelector('[data-clinic-toast]');
        const toastMessage = document.querySelector('[data-toast-message]');

        if (!toastElement || !toastMessage) {
            window.alert(message);
            return;
        }

        toastMessage.textContent = message;
        if (window.bootstrap && window.bootstrap.Toast) {
            window.bootstrap.Toast.getOrCreateInstance(toastElement, { delay: 3600 }).show();
            return;
        }

        toastElement.classList.add('show');
        window.setTimeout(() => toastElement.classList.remove('show'), 3600);
    };

    const passwordToggle = document.querySelector('[data-password-toggle]');

    if (passwordToggle) {
        const passwordFieldId = passwordToggle.getAttribute('aria-controls');
        const passwordField = passwordFieldId ? document.getElementById(passwordFieldId) : null;

        if (passwordField) {
            passwordToggle.addEventListener('click', () => {
                const shouldShow = passwordField.type === 'password';
                passwordField.type = shouldShow ? 'text' : 'password';
                passwordToggle.setAttribute('aria-pressed', String(shouldShow));
                passwordToggle.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
            });
        }
    }

    const searchInput = document.querySelector('[data-global-search]');
    const filterSelect = document.querySelector('[data-table-filter]');
    const tableRows = Array.from(document.querySelectorAll('[data-table-row]'));
    const emptyRow = document.querySelector('[data-empty-row]');

    const filterTable = () => {
        const query = (searchInput?.value || '').trim().toLocaleLowerCase();
        const filter = (filterSelect?.value || '').trim().toLocaleLowerCase();
        let visibleCount = 0;

        if (tableRows.length === 0) {
            document.querySelectorAll('.sidebar-link, .quick-action').forEach((item) => {
                item.hidden = Boolean(query) && !item.textContent.toLocaleLowerCase().includes(query);
            });
            return;
        }

        tableRows.forEach((row) => {
            const text = row.dataset.searchText || row.textContent.toLocaleLowerCase();
            const matches = text.includes(query) && (!filter || text.includes(filter));
            row.hidden = !matches;
            if (matches) visibleCount += 1;
        });

        if (emptyRow) emptyRow.hidden = visibleCount !== 0;
    };

    searchInput?.addEventListener('input', filterTable);
    filterSelect?.addEventListener('change', filterTable);

    document.querySelectorAll('[data-export-table]').forEach((button) => {
        button.addEventListener('click', () => {
            const table = button.closest('.data-card')?.querySelector('[data-clinic-table]');
            if (!table) return;

            const rows = Array.from(table.querySelectorAll('tr')).filter((row) => !row.hidden && !row.hasAttribute('data-empty-row'));
            const csv = rows.map((row) => Array.from(row.querySelectorAll('th,td')).map((cell) => {
                const value = cell.innerText.trim().replaceAll('"', '""');
                return `"${value}"`;
            }).join(',')).join('\r\n');
            const downloadUrl = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
            const downloadLink = document.createElement('a');
            downloadLink.href = downloadUrl;
            downloadLink.download = 'cliniccare-preview.csv';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            downloadLink.remove();
            URL.revokeObjectURL(downloadUrl);
        });
    });

    document.querySelectorAll('[data-demo-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const action = button.dataset.demoAction || 'This action';
            showToast(`${action} is a frontend preview. Backend integration is required to save changes.`);
        });
    });

    document.querySelectorAll('[data-report-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            const term = button.dataset.reportFilter || '';
            if (searchInput) {
                searchInput.value = term;
                filterTable();
                document.querySelector('[data-clinic-table]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });

    document.querySelector('[data-notifications]')?.addEventListener('click', () => {
        showToast('There are no new notifications in this frontend preview.');
    });

    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const closeSidebar = () => {
        document.body.classList.remove('sidebar-open');
        sidebarToggle?.setAttribute('aria-expanded', 'false');
    };

    sidebarToggle?.addEventListener('click', () => {
        const isOpen = document.body.classList.toggle('sidebar-open');
        sidebarToggle.setAttribute('aria-expanded', String(isOpen));
    });

    document.addEventListener('pointerdown', (event) => {
        if (!document.body.classList.contains('sidebar-open')) return;
        if (event.target.closest('#admin-sidebar') || event.target.closest('[data-sidebar-toggle]')) return;
        closeSidebar();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeSidebar();
    });
})();
