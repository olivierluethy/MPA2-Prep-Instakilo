<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Method-aware router. Maps an HTTP verb + path to "Controller@method".
 *
 * Improvements over the old router:
 *  - distinguishes GET from POST (state changes are POST-only now);
 *  - returns 405 (not 404) when a path exists under another verb;
 *  - resolves controllers through the autoloader/namespace, no manual require.
 */
final class Router
{
    /** @var array<string, array<string, string>> */
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, string $action): void
    {
        $this->routes['GET'][$this->normalize($path)] = $action;
    }

    public function post(string $path, string $action): void
    {
        $this->routes['POST'][$this->normalize($path)] = $action;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path   = $this->normalize($request->path());

        $action = $this->routes[$method][$path] ?? null;

        if ($action === null) {
            // Path known under a different verb? -> 405, otherwise 404.
            $existsElsewhere = false;
            foreach ($this->routes as $verb => $map) {
                if ($verb !== $method && isset($map[$path])) {
                    $existsElsewhere = true;
                    break;
                }
            }
            $this->abort($existsElsewhere ? 405 : 404);
            return;
        }

        [$controller, $methodName] = explode('@', $action);
        $class = 'App\\Controllers\\' . $controller;

        if (!class_exists($class) || !method_exists($class, $methodName)) {
            $this->abort(500, "Route handler {$action} is not callable.");
            return;
        }

        (new $class())->{$methodName}($request);
    }

    private function normalize(string $path): string
    {
        return strtolower(trim($path, '/'));
    }

    private function abort(int $code, string $message = ''): void
    {
        http_response_code($code);
        $titles = [404 => 'Page not found', 405 => 'Method not allowed', 500 => 'Server error'];
        $title  = $titles[$code] ?? 'Error';
        View::render('errors.error', [
            'code'    => $code,
            'heading' => $title,
            'detail'  => Config::get('app.debug') ? $message : '',
        ], "{$code} – {$title}");
    }
}
