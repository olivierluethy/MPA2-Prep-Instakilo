<?php

declare(strict_types=1);

namespace App\Core;

/**
 * One-shot flash messages stored in the session and consumed on the next render.
 */
final class Flash
{
    private const KEY = '_flash';

    public static function set(string $type, string $message): void
    {
        $_SESSION[self::KEY][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Return all queued messages and clear them.
     *
     * @return array<int, array{type:string, message:string}>
     */
    public static function pull(): array
    {
        $messages = $_SESSION[self::KEY] ?? [];
        unset($_SESSION[self::KEY]);
        return $messages;
    }
}
