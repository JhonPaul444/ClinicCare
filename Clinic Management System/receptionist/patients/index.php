<?php
require_once __DIR__ . '/../../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Admin', 'Receptionist']);
$appRole = 'Receptionist'; $activePage = 'patients'; $pageKey = 'patients';
$pageTitle = 'Patients'; $pageIntro = 'Register, find, and update patient demographics.'; $actionLabel = 'Add patient';
$bodyClass = 'admin-app'; $appBasePath = '../../';
require __DIR__ . '/../../includes/management_page.php';
