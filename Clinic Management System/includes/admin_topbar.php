<?php
$displayName = (string)($currentUser['full_name'] ?? 'ClinicCare user');
$initials = strtoupper(implode('', array_map(static fn($part) => substr($part, 0, 1), array_slice(preg_split('/\s+/', trim($displayName)) ?: [], 0, 2))));
$logoutToken = cliniccare_csrf_token();
?>
<header class="app-topbar">
    <button type="button" class="mobile-menu-toggle" aria-label="Open navigation" aria-expanded="false" aria-controls="admin-sidebar" data-sidebar-toggle><span></span><span></span><span></span></button>
    <label class="global-search" for="admin-global-search"><span aria-hidden="true">⌕</span><input type="search" id="admin-global-search" placeholder="Search this page..." autocomplete="off" data-global-search></label>
    <div class="topbar-actions">
        <div class="topbar-divider"></div>
        <div class="dropdown profile-dropdown" data-profile-dropdown>
            <button class="profile-button dropdown-toggle" type="button" data-profile-toggle aria-haspopup="true" aria-expanded="false" aria-controls="admin-profile-menu">
                <span class="avatar"><?php echo htmlspecialchars($initials !== '' ? $initials : 'CC', ENT_QUOTES, 'UTF-8'); ?></span><span class="profile-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end profile-menu" id="admin-profile-menu" data-profile-menu hidden>
                <li><span class="dropdown-item-text"><strong><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></strong><small class="d-block text-secondary"><?php echo htmlspecialchars((string)($currentUser['role'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><form action="<?php echo htmlspecialchars(rtrim($appBasePath, '/') . '/logout.php', ENT_QUOTES, 'UTF-8'); ?>" method="post" class="px-2"><input type="hidden" name="_csrf" value="<?php echo htmlspecialchars($logoutToken, ENT_QUOTES, 'UTF-8'); ?>"><button class="dropdown-item text-danger" type="submit">Sign out</button></form></li>
            </ul>
        </div>
    </div>
</header>
<div class="toast-container position-fixed top-0 end-0 p-3" aria-live="polite" aria-atomic="true"><div class="toast clinic-toast" role="status" data-clinic-toast><div class="toast-header"><strong class="me-auto">ClinicCare</strong><button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button></div><div class="toast-body" data-toast-message></div></div></div>
