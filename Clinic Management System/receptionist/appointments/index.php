<?php
require_once __DIR__ . '/../../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Admin', 'Receptionist']);
$appRole = 'Receptionist'; $activePage = 'appointments'; $pageKey = 'appointments';
$pageTitle = 'Appointments'; $pageIntro = 'Book, search, reschedule, or cancel clinic appointments.'; $actionLabel = 'New appointment';
$bodyClass = 'admin-app'; $appBasePath = '../../';
require __DIR__ . '/../../includes/management_page.php';
