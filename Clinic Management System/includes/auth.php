<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function cliniccare_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('cliniccare_session');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => $secure,
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
}

function cliniccare_user(): ?array
{
    cliniccare_start_session();
    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) return null;
    $s = cliniccare_pdo()->prepare('SELECT user_id, full_name, username, role, status FROM users WHERE user_id=?');
    $s->execute([(int)$_SESSION['user']['user_id']]);
    $current = $s->fetch();
    if (!$current || strcasecmp((string)$current['status'], 'Active') !== 0 || (string)$current['role'] !== (string)($_SESSION['user']['role'] ?? '')) {
        cliniccare_logout();
        return null;
    }
    $_SESSION['user']['full_name'] = (string)$current['full_name'];
    $_SESSION['user']['username'] = (string)$current['username'];
    return $_SESSION['user'];
}

function cliniccare_require_auth(?array $roles = null): array
{
    $user = cliniccare_user();
    if (!$user) cliniccare_json(['error' => 'Authentication required.'], 401);
    if ($roles !== null && !in_array($user['role'] ?? '', $roles, true)) {
        cliniccare_json(['error' => 'You are not authorized to perform this action.'], 403);
    }
    return $user;
}

function cliniccare_login(string $username, string $password): bool
{
    cliniccare_start_session();
    $stmt = cliniccare_pdo()->prepare('SELECT user_id, full_name, username, password_hash, role, status FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || strcasecmp((string)$user['status'], 'Active') !== 0 || !password_verify($password, (string)$user['password_hash'])) return false;
    if (password_needs_rehash((string)$user['password_hash'], PASSWORD_DEFAULT)) {
        $update = cliniccare_pdo()->prepare('UPDATE users SET password_hash=?, updated_at=CURRENT_TIMESTAMP WHERE user_id=?');
        $update->execute([password_hash($password, PASSWORD_DEFAULT), (int)$user['user_id']]);
    }
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'user_id' => (int)$user['user_id'], 'full_name' => (string)$user['full_name'],
        'username' => (string)$user['username'], 'role' => (string)$user['role'],
    ];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return true;
}

function cliniccare_logout(): void
{
    cliniccare_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'domain' => $p['domain'], 'secure' => $p['secure'], 'httponly' => $p['httponly'], 'samesite' => $p['samesite'] ?? 'Lax']);
    }
    session_destroy();
}

function cliniccare_csrf_token(): string
{
    cliniccare_start_session();
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function cliniccare_verify_csrf(): void
{
    cliniccare_start_session();
    $given = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? '');
    if ($given === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $given)) {
        cliniccare_json(['error' => 'Your session token is missing or expired. Refresh and try again.'], 419);
    }
}

function cliniccare_require_active_doctor(PDO $pdo, array $user, bool $lock = false): array
{
    if (($user['role'] ?? '') !== 'Doctor') cliniccare_json(['error' => 'Doctor access required.'], 403);
    $sql = "SELECT d.doctor_id, d.status AS doctor_status, u.status AS user_status FROM doctors d JOIN users u ON u.user_id=d.user_id WHERE d.user_id=?" . ($lock ? ' FOR UPDATE' : '');
    $s = $pdo->prepare($sql); $s->execute([(int)$user['user_id']]); $d = $s->fetch();
    if (!$d || strcasecmp((string)$d['doctor_status'], 'Active') !== 0 || strcasecmp((string)$d['user_status'], 'Active') !== 0) cliniccare_json(['error' => 'An active doctor profile is required.'], 403);
    return $d;
}
