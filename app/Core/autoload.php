<?php

declare(strict_types=1);

/**
 * Minimal PSR-4 style autoloader.
 *
 * Maps the `App\` namespace prefix to the `app/` directory so classes load on
 * demand. This replaces the old hand-written `require` chain in bootstrap.php.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = BASE_PATH . '/app/';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/helpers.php';
