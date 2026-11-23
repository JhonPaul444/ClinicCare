<?php
require_once __DIR__ . '/../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Admin']);
$appRole = 'Admin'; $bodyClass = 'admin-app'; $appBasePath = '..';
$pageTitle = 'Dashboard'; $pageDescription = 'Live overview of appointments, patients, and doctors.';
require __DIR__ . '/../includes/dashboard_page.php';
