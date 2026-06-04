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

if (!function_exists('linkify')) {
    /**
     * Escape plain text and turn http(s) URLs into safe, clickable links flagged
     * with data-external (the client shows a "leave Instakilo?" confirm).
     */
    function linkify(string $text): string
    {
        $parts = preg_split('#(https?://[^\s<]+)#i', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $out = '';
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                $u = e($part);
                $out .= '<a href="' . $u . '" data-external rel="noopener noreferrer nofollow" target="_blank" class="underline break-all">' . $u . '</a>';
            } else {
                $out .= nl2br(e($part));
            }
        }
        return $out;
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        $units = ['KB', 'MB', 'GB'];
        $i = -1;
        do {
            $bytes /= 1024;
            $i++;
        } while ($bytes >= 1024 && $i < count($units) - 1);
        return round($bytes, 1) . ' ' . $units[$i];
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format a datetime string for display in a single, consistent style across
     * the whole app, e.g. "June 3, 2026, 14:45" (date + 24h time).
     *
     * Returns an empty string for null/empty/unparseable input so callers can
     * use it unconditionally.
     */
    function format_datetime(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }
        try {
            return (new DateTimeImmutable($value))->format('F j, Y, H:i');
        } catch (Exception) {
            return '';
        }
    }
}

if (!function_exists('format_date')) {
    /**
     * Date-only variant (no time), e.g. "June 1, 2026" — used for the optional
     * "photo taken on" date which has no time component.
     */
    function format_date(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }
        try {
            return (new DateTimeImmutable($value))->format('F j, Y');
        } catch (Exception) {
            return '';
        }
    }
}

if (!function_exists('time_ago')) {
    /**
     * Compact relative timestamp (German), e.g. "vor 5 Sekunden", "vor 2
     * Minuten", "vor 1 Stunde", "Gestern", "vor 3 Tagen". Older than a week
     * falls back to an absolute date. Returns '' for empty/unparseable input.
     */
    function time_ago(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }
        try {
            $then = new DateTimeImmutable($value);
        } catch (Exception) {
            return '';
        }
        $diff = (new DateTimeImmutable('now'))->getTimestamp() - $then->getTimestamp();
        if ($diff < 0) {
            $diff = 0;
        }
        $unit = static fn (int $n, string $one, string $many): string =>
            'vor ' . $n . ' ' . ($n === 1 ? $one : $many);

        if ($diff < 60) {
            return $unit($diff, 'Sekunde', 'Sekunden');
        }
        if ($diff < 3600) {
            return $unit(intdiv($diff, 60), 'Minute', 'Minuten');
        }
        if ($diff < 86400) {
            return $unit(intdiv($diff, 3600), 'Stunde', 'Stunden');
        }
        $days = intdiv($diff, 86400);
        if ($days === 1) {
            return 'Gestern';
        }
        if ($days < 7) {
            return $unit($days, 'Tag', 'Tagen');
        }
        return format_date($value);
    }
}

if (!function_exists('iso_datetime')) {
    /**
     * Machine-readable timestamp for the <time datetime="…"> attribute.
     */
    function iso_datetime(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }
        try {
            return (new DateTimeImmutable($value))->format('c');
        } catch (Exception) {
            return '';
        }
    }
}
