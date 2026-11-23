<?php
require_once __DIR__ . '/../../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Doctor']);
$appRole = 'Doctor'; $activePage = 'medical-records'; $pageKey = 'medical-records';
$pageTitle = 'Medical records'; $pageIntro = 'View medical records for patients you are authorized to treat.'; $actionLabel = '';
$bodyClass = 'admin-app'; $appBasePath = '../../';
require __DIR__ . '/../../includes/management_page.php';
