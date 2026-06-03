<?php

declare(strict_types=1);

use App\Core\Config;

/**
 * Global view/controller helper functions.
 *
 * These are intentionally tiny, side-effect-free utilities used throughout the
 * views. Anything with real logic lives in a class under App\Core.
 */

if (!function_exists('e')) {
    /**
     * Escape a value for safe HTML output (prevents XSS).
     */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /**
     * Build a root-relative URL, honouring the optional APP_BASE_PATH so the app
     * works both at the web-root (Docker) and inside a sub-directory.
     */
    function url(string $path = ''): string
    {
        $base = Config::get('app.base_path', '');
        return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * URL for a static asset under the public/ web-root.
     */
    function asset(string $path): string
    {
        return url(ltrim($path, '/'));
    }
}

if (!function_exists('old')) {
    /**
     * Repopulate a form field after a validation failure.
     */
    function old(string $key, string $default = ''): string
    {
        return e($_SESSION['_old'][$key] ?? $default);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}
