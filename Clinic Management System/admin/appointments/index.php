<?php
require_once __DIR__ . '/../../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Admin']);
$appRole = 'Admin'; $activePage = 'appointments'; $pageKey = 'appointments';
$pageTitle = 'Appointments'; $pageIntro = 'Monitor clinic appointments and update booking status.'; $actionLabel = 'New appointment';
$bodyClass = 'admin-app'; $appBasePath = '../..';
require __DIR__ . '/../../includes/management_page.php';
