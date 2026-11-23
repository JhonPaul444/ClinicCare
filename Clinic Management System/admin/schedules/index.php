<?php
require_once __DIR__ . '/../../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Admin']);
$appRole = 'Admin'; $activePage = 'schedules'; $pageKey = 'schedules';
$pageTitle = 'Scheduling'; $pageIntro = 'Manage doctor working days and available hours.'; $actionLabel = 'Add schedule';
$bodyClass = 'admin-app'; $appBasePath = '../..';
require __DIR__ . '/../../includes/management_page.php';
