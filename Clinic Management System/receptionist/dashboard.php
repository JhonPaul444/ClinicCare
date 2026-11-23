<?php
require_once __DIR__ . '/../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Receptionist']);
$appRole = 'Receptionist'; $bodyClass = 'admin-app'; $appBasePath = '../';
$pageTitle = 'Receptionist dashboard'; $pageDescription = "Today's appointments and clinic activity.";
require __DIR__ . '/../includes/dashboard_page.php';
