<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
function cliniccare_page_guard(array $roles): array
{
    header('Cache-Control: no-store, private, max-age=0');
    header('Pragma: no-cache');
    $user = cliniccare_user();
    if (!$user) {
        $script = (string)parse_url($_SERVER['SCRIPT_NAME'] ?? '/', PHP_URL_PATH);
        $base = preg_replace('~/(admin|doctor|receptionist)(/.*)?$~', '', $script) ?? '';
        header('Location: ' . rtrim($base, '/') . '/login.php'); exit;
    }
    if (!in_array($user['role'] ?? '', $roles, true)) { http_response_code(403); exit('You are not authorized to view this page.'); }
    return $user;
}
