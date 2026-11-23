<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = cliniccare_user();
if (!$user) { header('Location: login.php'); exit; }
$routes = ['Admin'=>'admin/dashboard.php','Receptionist'=>'receptionist/dashboard.php','Doctor'=>'doctor/dashboard.php'];
header('Location: ' . ($routes[$user['role']] ?? 'login.php'));
exit;
