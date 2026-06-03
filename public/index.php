<?php

declare(strict_types=1);

/**
 * Front controller — the single entry point for every request.
 *
 * The web server's document root is THIS directory (public/), so the
 * application source under app/, config/ and the database layer are never
 * directly reachable over HTTP.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/autoload.php';

use App\Core\Config;
use App\Core\Request;
use App\Core\Router;

Config::load();

// All timestamps are handled in UTC end-to-end (DB session tz is also set to UTC
// in the Database layer) so message time filtering is consistent and correct.
date_default_timezone_set('UTC');

// Harden the session cookie before it is started.
$secure = (($_SERVER['HTTPS'] ?? '') === 'on')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $secure,
]);
session_start();

// In development surface errors; in production hide them from users.
if (Config::get('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

/** @var Router $router */
$router = require BASE_PATH . '/config/routes.php';
$router->dispatch(new Request());
