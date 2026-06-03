<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF protection. A per-session token is embedded in every state-changing form
 * (and AJAX header) and verified on each POST. The old app had no CSRF defense
 * and performed mutations over GET — both fixed here.
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    /**
     * Hidden input markup for forms.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="' . self::KEY . '" value="' . e(self::token()) . '">';
    }

    public static function verify(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION[self::KEY])
            && hash_equals($_SESSION[self::KEY], $token);
    }

    /**
     * Read the token from a POST field or the X-CSRF-Token header.
     */
    public static function fromRequest(): ?string
    {
        return $_POST[self::KEY]
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;
    }
}
