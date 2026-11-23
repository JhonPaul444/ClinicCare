<?php
require_once __DIR__ . '/../../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Admin']);
$appRole = 'Admin'; $activePage = 'doctors'; $pageKey = 'doctors';
$pageTitle = 'Doctors'; $pageIntro = 'Manage doctor profiles, specializations, and availability.'; $actionLabel = 'Add doctor profile';
$bodyClass = 'admin-app'; $appBasePath = '../..';
require __DIR__ . '/../../includes/management_page.php';
