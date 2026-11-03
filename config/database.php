<?php
declare(strict_types=1);

// Local XAMPP defaults only. Configure real credentials in the server environment.
// No .env loader or credentials in pages; never commit a real password here.
$databaseEnv = static function (string $name, string $fallback): string {
    $value = getenv($name);
    return $value === false ? $fallback : $value;
};

return [
    'host' => $databaseEnv('TMQ_DB_HOST', '127.0.0.1'),
    'port' => $databaseEnv('TMQ_DB_PORT', '3306'),
    'name' => $databaseEnv('TMQ_DB_NAME', 'tutormequran'),
    'username' => $databaseEnv('TMQ_DB_USER', 'root'),
    'password' => $databaseEnv('TMQ_DB_PASSWORD', ''),
];
