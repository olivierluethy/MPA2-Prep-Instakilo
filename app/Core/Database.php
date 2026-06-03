<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * The single, centralized database access layer.
 *
 * Replaces the three competing implementations the project used to have
 * (connectDatabase(), the broken db() helper, and the mysqli config.php).
 * Every model obtains its connection from Database::connection(), which returns
 * one shared, lazily-created PDO instance configured for safe, modern defaults.
 */
final class Database
{
    private static ?PDO $pdo = null;

    /**
     * Return the shared PDO connection, creating it on first use.
     */
    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            Config::get('db.host'),
            Config::get('db.port'),
            Config::get('db.name'),
            Config::get('db.charset')
        );

        try {
            self::$pdo = new PDO(
                $dsn,
                (string) Config::get('db.user'),
                (string) Config::get('db.password'),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Use real prepared statements, not client-side emulation.
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            // Never leak credentials/DSN to the client.
            if (Config::get('app.debug')) {
                throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
            }
            http_response_code(503);
            exit('Service temporarily unavailable. Please try again later.');
        }

        return self::$pdo;
    }

    /**
     * For tests: reset the shared connection.
     */
    public static function reset(): void
    {
        self::$pdo = null;
    }
}
