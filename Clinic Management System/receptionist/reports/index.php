<?php
require_once __DIR__ . '/../../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Admin', 'Receptionist']);
$appRole = 'Receptionist'; $activePage = 'reports'; $pageKey = 'reports';
$pageTitle = 'Reports'; $pageIntro = 'View appointment and patient summaries for a date range.'; $actionLabel = '';
$bodyClass = 'admin-app'; $appBasePath = '../../';
require __DIR__ . '/../../includes/management_page.php';
