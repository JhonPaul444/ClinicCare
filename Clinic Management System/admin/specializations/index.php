<?php
require_once __DIR__ . '/../../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Admin']);
$appRole = 'Admin'; $activePage = 'specializations'; $pageKey = 'specializations';
$pageTitle = 'Specializations'; $pageIntro = 'Manage the clinic specialties offered by doctors.'; $actionLabel = 'Add specialization';
$bodyClass = 'admin-app'; $appBasePath = '../..';
require __DIR__ . '/../../includes/management_page.php';
