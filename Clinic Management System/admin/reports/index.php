<?php
require_once __DIR__ . '/../../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Admin']);
$appRole = 'Admin'; $activePage = 'reports'; $pageKey = 'reports';
$pageTitle = 'Reports'; $pageIntro = 'Review live appointment and patient summaries.'; $actionLabel = '';
$bodyClass = 'admin-app'; $appBasePath = '../..';
require __DIR__ . '/../../includes/management_page.php';
