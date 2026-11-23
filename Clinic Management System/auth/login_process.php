<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Method not allowed.'); }
cliniccare_verify_csrf();
try {
    $in = cliniccare_input();
    $username = cliniccare_required($in, 'username', 'Username');
    $password = (string)($in['password'] ?? '');
    if ($password === '') cliniccare_json(['error' => 'Password is required.'], 422);
    if (!cliniccare_login($username, $password)) {
        cliniccare_start_session(); $_SESSION['login_error'] = 'Username or password is incorrect, or this account is inactive.';
        header('Location: ../login.php'); exit;
    }
    header('Location: ../index.php'); exit;
} catch (Throwable $e) { cliniccare_error_handler($e); }
