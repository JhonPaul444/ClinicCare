<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Sign out requires a CSRF-protected POST request.'); }
cliniccare_require_auth();
cliniccare_verify_csrf();
cliniccare_logout();
if (str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json')) {
    cliniccare_json(['message' => 'Signed out.']);
}
header('Location: login.php');
exit;
