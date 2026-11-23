<?php
declare(strict_types=1);

/**
 * Return the shared ClinicCare PDO connection.
 *
 * Backend code can load this file once and call cliniccare_pdo() wherever a
 * database connection is needed:
 *     require_once __DIR__ . '/../config/database.php';
 *     $pdo = cliniccare_pdo();
 */
if (!function_exists('cliniccare_pdo')) {
    function cliniccare_pdo(): PDO
    {
        static $connection = null;

        if ($connection instanceof PDO) {
            return $connection;
        }

        $localConfigPath = __DIR__ . '/database.local.php';
        if (!is_file($localConfigPath)) {
            throw new RuntimeException(
                'ClinicCare local database configuration is missing. Create config/database.local.php from config/database.local.example.php.'
            );
        }

        $config = require $localConfigPath;
        $requiredKeys = ['host', 'port', 'database', 'username', 'password'];
        if (!is_array($config) || array_diff($requiredKeys, array_keys($config)) !== []) {
            throw new RuntimeException(
                'ClinicCare local database configuration is invalid. Check config/database.local.php.'
            );
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            (string) $config['host'],
            (int) $config['port'],
            (string) $config['database']
        );

        try {
            $connection = new PDO(
                $dsn,
                (string) $config['username'],
                (string) $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Unable to connect to the ClinicCare database. Check the local configuration and MariaDB service.'
            );
        }

        return $connection;
    }
}
