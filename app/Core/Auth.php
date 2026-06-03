<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session-backed authentication state. Wraps the raw $_SESSION keys the old
 * code spread across every controller into one tested surface.
 */
final class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function username(): ?string
    {
        return $_SESSION['username'] ?? null;
    }

    /**
     * Log a user in and regenerate the session id (prevents fixation).
     */
    public static function login(int $id, string $username, string $email): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']  = $id;
        $_SESSION['username'] = $username;
        $_SESSION['email']    = $email;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
