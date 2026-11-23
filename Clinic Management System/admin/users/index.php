<?php
require_once __DIR__ . '/../../includes/page_guard.php';
$currentUser = cliniccare_page_guard(['Admin']);
$appRole = 'Admin'; $activePage = 'users'; $pageKey = 'users';
$pageTitle = 'Users'; $pageIntro = 'Manage active clinic accounts and their roles.'; $actionLabel = 'Add user';
$bodyClass = 'admin-app'; $appBasePath = '../..';
require __DIR__ . '/../../includes/management_page.php';
