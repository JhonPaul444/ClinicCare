<?php
require_once __DIR__ . '/../../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Doctor']);
$appRole = 'Doctor'; $activePage = 'appointments'; $pageKey = 'appointments';
$pageTitle = 'My appointments'; $pageIntro = 'Review your assigned appointments and record consultations.'; $actionLabel = '';
$bodyClass = 'admin-app'; $appBasePath = '../../';
require __DIR__ . '/../../includes/management_page.php';
