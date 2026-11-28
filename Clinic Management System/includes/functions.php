<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function cliniccare_json(array $data, int $status = 200): never
{
    $activeTransaction = $GLOBALS['cliniccare_active_transaction'] ?? null;
    if ($activeTransaction instanceof PDO && $activeTransaction->inTransaction()) {
        $activeTransaction->rollBack();
        unset($GLOBALS['cliniccare_active_transaction']);
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function cliniccare_input(): array
{
    $type = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
    if (str_contains($type, 'application/json')) {
        $data = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($data)) cliniccare_json(['error' => 'Request body must be a JSON object.'], 400);
        return $data;
    }
    return $_POST;
}

function cliniccare_required(array $in, string $key, string $label): string
{
    $v = trim((string)($in[$key] ?? ''));
    if ($v === '') cliniccare_json(['error' => "$label is required."], 422);
    return $v;
}

function cliniccare_id(array $in, string $key, string $label): int
{
    $v = filter_var($in[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($v === false) cliniccare_json(['error' => "$label must be a valid ID."], 422);
    return (int)$v;
}

function cliniccare_date(string $value, string $label, bool $allowPast = true): DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count'])) || $date->format('Y-m-d') !== $value) cliniccare_json(['error' => "$label must be a valid date."], 422);
    if (!$allowPast && $date->format('Y-m-d') < (new DateTimeImmutable('today'))->format('Y-m-d')) cliniccare_json(['error' => "$label cannot be in the past."], 422);
    return $date;
}

function cliniccare_not_future_date(string $value, string $label): DateTimeImmutable
{
    $date = cliniccare_date($value, $label);
    if ($date->format('Y-m-d') > date('Y-m-d')) cliniccare_json(['error' => "$label cannot be in the future."], 422);
    return $date;
}

function cliniccare_time(string $value, string $label): string
{
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value)) cliniccare_json(['error' => "$label must be a valid 24-hour time."], 422);
    return strlen($value) === 5 ? $value . ':00' : $value;
}

function cliniccare_appointment_schedule_error(array $activeSchedules, string $date, string $startTime, string $endTime): ?string
{
    $appointmentDate = new DateTimeImmutable($date);
    $dayName = $appointmentDate->format('l');
    if ($activeSchedules === []) {
        return "No active schedule exists for this doctor on {$dayName}. Choose another date or doctor.";
    }

    foreach ($activeSchedules as $schedule) {
        if ((string)$schedule['start_time'] <= $startTime && (string)$schedule['end_time'] >= $endTime) {
            return null;
        }
    }

    $formatTime = static fn(string $time): string => (new DateTimeImmutable($date . ' ' . $time))->format('g:i A');
    $ranges = array_map(
        static fn(array $schedule): string => $formatTime((string)$schedule['start_time']) . '–' . $formatTime((string)$schedule['end_time']),
        $activeSchedules
    );
    $available = implode(', ', $ranges);
    $appointmentEnd = $formatTime($endTime);

    return "The appointment ends at {$appointmentEnd}, outside the doctor's active {$dayName} hours ({$available}). Choose a start time and duration that fit fully within one schedule.";
}

function cliniccare_appointment_operating_hours_error(string $startTime, string $endTime): ?string
{
    if ($startTime < '07:00:00') {
        return 'Appointments cannot start before 7:00 AM. Choose a time between 7:00 AM and 7:00 PM.';
    }
    if ($endTime > '19:00:00') {
        return 'The appointment must finish by 7:00 PM. Choose an earlier start time or shorter duration.';
    }
    return null;
}

function cliniccare_gender_is_allowed(string $gender): bool
{
    return in_array($gender, ['Male', 'Female', 'Prefer not to say'], true);
}

function cliniccare_length(PDO $pdo, string $table, string $column, string $value, string $label, bool $required = true): void
{
    if ($required && trim($value) === '') cliniccare_json(['error' => "$label is required."], 422);
    $s = $pdo->prepare('SELECT CHARACTER_MAXIMUM_LENGTH, DATA_TYPE, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $s->execute([$table, $column]); $meta = $s->fetch();
    if (!$meta) throw new RuntimeException("Approved database column {$table}.{$column} was not found.");
    $characters = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : preg_match_all('/./us', $value);
    if ($characters === false) cliniccare_json(['error' => "$label contains invalid text."], 422);
    if ($meta['CHARACTER_MAXIMUM_LENGTH'] !== null && $characters > (int)$meta['CHARACTER_MAXIMUM_LENGTH']) cliniccare_json(['error' => "$label exceeds the allowed length of {$meta['CHARACTER_MAXIMUM_LENGTH']} characters."], 422);
    if (strtolower((string)$meta['DATA_TYPE']) === 'enum') {
        preg_match_all("/'((?:[^']|'')*)'/", (string)$meta['COLUMN_TYPE'], $m);
        $allowed = array_map(static fn($x) => str_replace("''", "'", $x), $m[1] ?? []);
        if ($allowed && !in_array($value, $allowed, true)) cliniccare_json(['error' => "$label must match an approved database option."], 422);
    }
}

function cliniccare_status(string $value, array $allowed, string $label = 'Status'): string
{
    if (!in_array($value, $allowed, true)) cliniccare_json(['error' => "$label is invalid."], 422);
    return $value;
}

function cliniccare_error_handler(Throwable $e): never
{
    error_log('[ClinicCare] ' . $e->getMessage());
    cliniccare_json(['error' => 'The request could not be completed. Check the server configuration and try again.'], 500);
}

function cliniccare_audit_actor(array $user): int { return (int)$user['user_id']; }

/** Run a transaction and always roll back on thrown errors. */
function cliniccare_transaction(PDO $pdo, callable $work): mixed
{
    $pdo->beginTransaction();
    $GLOBALS['cliniccare_active_transaction'] = $pdo;
    try { $result = $work(); $pdo->commit(); return $result; }
    catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    finally { unset($GLOBALS['cliniccare_active_transaction']); }
}
