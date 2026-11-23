<header class="app-topbar">
    <button type="button" class="mobile-menu-toggle" aria-label="Open navigation" aria-expanded="false" aria-controls="admin-sidebar" data-sidebar-toggle>
        <span></span><span></span><span></span>
    </button>
    <label class="global-search" for="admin-global-search">
        <span aria-hidden="true">⌕</span>
        <input type="search" id="admin-global-search" placeholder="Search this page..." autocomplete="off" data-global-search>
    </label>
    <div class="topbar-actions">
        <button class="icon-button" type="button" aria-label="Show notifications" data-notifications>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg><i></i>
        </button>
        <div class="topbar-divider"></div>
        <div class="dropdown">
            <button class="profile-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar">AD</span><span class="profile-name">Dr. Admin</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end profile-menu">
                <li><span class="dropdown-item-text"><strong>Admin preview</strong><small class="d-block text-secondary">Backend account details pending</small></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?php echo htmlspecialchars($appBasePath . '/login.php', ENT_QUOTES, 'UTF-8'); ?>">Return to sign in</a></li>
            </ul>
        </div>
    </div>
</header>
<div class="toast-container position-fixed top-0 end-0 p-3" aria-live="polite" aria-atomic="true">
    <div class="toast clinic-toast" role="status" data-clinic-toast>
        <div class="toast-header"><strong class="me-auto">ClinicCare</strong><button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button></div>
        <div class="toast-body" data-toast-message>Preview message.</div>
    </div>
</div>
