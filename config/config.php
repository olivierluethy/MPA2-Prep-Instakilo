<?php

declare(strict_types=1);

/**
 * Central application configuration.
 *
 * Every value is sourced from environment variables (see .env.example) with a
 * sensible local-development fallback. This is the single place the app reads
 * configuration from — controllers, models and the database layer all consume
 * the values returned here.
 */

$env = static function (string $key, ?string $default = null): ?string {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
};

return [
    'app' => [
        'name'      => $env('APP_NAME', 'Instakilo'),
        // 'production' disables verbose error output to the browser.
        'env'       => $env('APP_ENV', 'development'),
        'debug'     => filter_var($env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOL),
        // Optional sub-directory the app is served from, e.g. "/instakilo".
        // Empty string means the app lives at the web-root (the Docker default).
        'base_path' => rtrim((string) $env('APP_BASE_PATH', ''), '/'),
    ],

    'db' => [
        'host'     => $env('DB_HOST', '127.0.0.1'),
        'port'     => $env('DB_PORT', '3306'),
        'name'     => $env('DB_NAME', 'instakilo'),
        'user'     => $env('DB_USER', 'root'),
        'password' => $env('DB_PASSWORD', ''),
        'charset'  => 'utf8mb4',
    ],

    'uploads' => [
        // Per-file limit in bytes (default 5 MB).
        'max_file_size' => (int) $env('UPLOAD_MAX_FILE_SIZE', (string) (5 * 1024 * 1024)),
        // Maximum number of images allowed per post.
        'max_files'     => (int) $env('UPLOAD_MAX_FILES', '10'),
        'allowed_mime'  => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    ],
];
