<?php
require_once __DIR__ . '/../../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Admin', 'Receptionist']);
$appRole = 'Receptionist'; $activePage = 'appointments'; $pageKey = 'appointments';
$pageTitle = 'New appointment'; $pageIntro = 'Create an appointment using an active patient, doctor, and schedule.'; $actionLabel = 'New appointment';
$bodyClass = 'admin-app'; $appBasePath = '../../';
require __DIR__ . '/../../includes/management_page.php';
