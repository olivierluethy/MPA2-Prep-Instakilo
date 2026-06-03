<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Tiny template renderer. A view template renders into $content, which the
 * shared layout (app/Views/layouts/app.php) then wraps. Keeps the V in MVC a
 * single, consistent mechanism instead of each view re-declaring <html>.
 */
final class View
{
    /**
     * Render a template inside the main layout and send it to the browser.
     *
     * @param string               $template Dot path, e.g. "home.index"
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = [], string $title = 'Instakilo'): void
    {
        $content = self::capture($template, $data);
        $layoutData = $data + ['title' => $title, 'content' => $content];
        echo self::capture('layouts.app', $layoutData);
    }

    /**
     * Render a template (or partial) to a string without the layout.
     *
     * @param array<string, mixed> $data
     */
    public static function partial(string $template, array $data = []): string
    {
        return self::capture($template, $data);
    }

    private static function capture(string $template, array $data): string
    {
        $path = BASE_PATH . '/app/Views/' . str_replace('.', '/', $template) . '.php';
        if (!is_file($path)) {
            throw new RuntimeException("View not found: {$template}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $path;
        return (string) ob_get_clean();
    }
}
