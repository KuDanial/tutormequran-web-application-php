<?php
declare(strict_types=1);

// Opt-in, lazy PDO connection. Existing interface pages do not load this file.
function tmq_db(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('The PDO MySQL extension is unavailable.');
    }

    $config = require __DIR__ . '/../config/database.php';
    if (!preg_match('/\A[a-zA-Z0-9_.:-]+\z/', $config['host'])
        || !preg_match('/\A[a-zA-Z0-9_]+\z/', $config['name'])
        || !ctype_digit($config['port'])
        || (int) $config['port'] < 1
        || (int) $config['port'] > 65535) {
        throw new RuntimeException('Invalid database host, name or port configuration.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $config['name']
    );

    try {
        $connection = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_TIMEOUT => 3,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00', sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'",
        ]);
    } catch (PDOException $exception) {
        // Log only the code; do not expose credentials, DSN or driver diagnostics.
        error_log('TutorMeQuran database connection failed; code ' . $exception->getCode());
        throw new RuntimeException('Database connection unavailable. Check the local database configuration.');
    }

    return $connection;
}
