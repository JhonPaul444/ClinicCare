<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$name = trim((string)($argv[1] ?? ''));
$username = trim((string)($argv[2] ?? ''));
$password = (string)(getenv('CLINICCARE_INITIAL_ADMIN_PASSWORD') ?: '');
$confirmPassword = (string)(getenv('CLINICCARE_INITIAL_ADMIN_PASSWORD_CONFIRM') ?: '');
putenv('CLINICCARE_INITIAL_ADMIN_PASSWORD');
putenv('CLINICCARE_INITIAL_ADMIN_PASSWORD_CONFIRM');

if ($name === '' || $username === '' || $password === '' || $confirmPassword === '') {
    fwrite(STDERR, "Usage: securely set CLINICCARE_INITIAL_ADMIN_PASSWORD and CLINICCARE_INITIAL_ADMIN_PASSWORD_CONFIRM, then run php tools/create-first-admin.php \"Full Name\" username\n");
    exit(2);
}
if (!hash_equals($password, $confirmPassword)) {
    fwrite(STDERR, "Admin password confirmation does not match.\n");
    exit(2);
}
if (strlen($name) > 120 || strlen($username) > 50) {
    fwrite(STDERR, "Name must be at most 120 characters and username at most 50 characters.\n");
    exit(2);
}
if (strlen($password) < 12) {
    fwrite(STDERR, "Admin password must be at least 12 characters.\n");
    exit(2);
}

$pdo = cliniccare_pdo();
$lockName = 'cliniccare_first_admin_setup';
$lock = $pdo->prepare('SELECT GET_LOCK(?, 10)');
$lock->execute([$lockName]);
if ((int)$lock->fetchColumn() !== 1) {
    fwrite(STDERR, "Could not acquire the local setup lock; try again.\n");
    exit(1);
}

try {
    $exists = $pdo->query("SELECT user_id FROM users WHERE role='Admin' LIMIT 1")->fetchColumn();
    if ($exists !== false) {
        fwrite(STDERR, "An Admin account already exists. First-admin setup is disabled.\n");
        exit(1);
    }

    $insert = $pdo->prepare("INSERT INTO users (full_name, username, password_hash, role, status) VALUES (?, ?, ?, 'Admin', 'Active')");
    $insert->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT)]);
    $createdUserId = (int)$pdo->lastInsertId();
    $verify = $pdo->prepare("SELECT user_id, password_hash FROM users WHERE user_id=? AND username=? AND role='Admin'");
    $verify->execute([$createdUserId, $username]);
    $createdAdmin = $verify->fetch();
    if (!$createdAdmin || !password_verify($password, (string)$createdAdmin['password_hash'])) {
        fwrite(STDERR, "Admin account was inserted, but its password verification did not pass. Do not rerun setup.\n");
        exit(1);
    }
    fwrite(STDOUT, "Initial Admin account created and verified. Password hash check passed; no password was displayed.\n");
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        fwrite(STDERR, "That username already exists. Choose another username.\n");
        exit(1);
    }
    throw $exception;
} finally {
    $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
    $release->execute([$lockName]);
}
