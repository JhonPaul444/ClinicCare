<?php
require_once __DIR__ . '/../../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Admin']);
$appRole = 'Admin'; $activePage = 'patients'; $pageKey = 'patients';
$pageTitle = 'Patients'; $pageIntro = 'Search and maintain patient demographics.'; $actionLabel = 'Add patient';
$bodyClass = 'admin-app'; $appBasePath = '../..';
require __DIR__ . '/../../includes/management_page.php';
