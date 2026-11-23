<?php
require_once __DIR__ . '/../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Doctor']);
$appRole = 'Doctor'; $bodyClass = 'admin-app'; $appBasePath = '../';
$pageTitle = 'Doctor dashboard'; $pageDescription = 'Your assigned appointments and consultation activity.';
require __DIR__ . '/../includes/dashboard_page.php';
