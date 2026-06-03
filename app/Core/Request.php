<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Thin wrapper around the PHP superglobals so controllers never touch
 * $_GET/$_POST/$_FILES directly. Centralizes input access and trimming.
 */
final class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * The routed path, provided by the .htaccess rewrite as ?url=...
     */
    public function path(): string
    {
        return trim((string) ($_GET['url'] ?? ''), '/');
    }

    public function query(string $key, ?string $default = null): ?string
    {
        $value = $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    public function input(string $key, ?string $default = null): ?string
    {
        $value = $_POST[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    /**
     * Raw (untrimmed) input — used for rich-text/HTML fields.
     */
    public function raw(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    public function intQuery(string $key): ?int
    {
        $value = $this->query($key);
        return ($value !== null && ctype_digit($value)) ? (int) $value : null;
    }

    public function files(string $key): array
    {
        return $_FILES[$key] ?? [];
    }

    public function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        return $xhr || str_contains($accept, 'application/json');
    }
}
