<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Loads config/config.php once and exposes values via dot-notation keys
 * (e.g. Config::get('db.host')). Single source of truth for configuration.
 */
final class Config
{
    private static ?array $items = null;

    public static function load(): void
    {
        if (self::$items === null) {
            self::$items = require BASE_PATH . '/config/config.php';
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();

        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
