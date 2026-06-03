<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller with shared response helpers: view rendering, redirects,
 * JSON responses, auth guards and CSRF enforcement. Keeps the concrete
 * controllers thin and consistent.
 */
abstract class Controller
{
    /**
     * Render a view within the layout.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $template, array $data = [], string $title = 'Instakilo'): void
    {
        // Clear any one-shot "old input" once a page renders.
        unset($_SESSION['_old']);
        View::render($template, $data, $title);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function back(string $fallback = 'home'): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        // Only honour same-origin referers.
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($referer !== '' && $host !== '' && str_contains($referer, $host)) {
            header('Location: ' . $referer);
            exit;
        }
        $this->redirect($fallback);
    }

    /**
     * Send a JSON response and stop.
     *
     * @param array<string, mixed> $payload
     */
    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Standard success envelope.
     *
     * @param array<string, mixed> $data
     */
    protected function ok(array $data = [], int $status = 200): void
    {
        $this->json(['success' => true, 'data' => $data], $status);
    }

    /**
     * Standard error envelope.
     */
    protected function fail(string $message, int $status = 400, array $errors = []): void
    {
        $this->json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
    }

    /**
     * Require an authenticated user; redirect or 401 (JSON) otherwise.
     */
    protected function requireAuth(Request $request): void
    {
        if (Auth::check()) {
            return;
        }
        if ($request->wantsJson()) {
            $this->fail('Authentication required.', 401);
        }
        Flash::set('error', 'Bitte zuerst einloggen.');
        $this->redirect('login');
    }

    /**
     * Verify the CSRF token on a state-changing request.
     */
    protected function requireCsrf(Request $request): void
    {
        if (Csrf::verify(Csrf::fromRequest())) {
            return;
        }
        if ($request->wantsJson()) {
            // 403 (not 419): mod_php/Apache only emits IANA-registered codes,
            // and 419 is silently rewritten to 500.
            $this->fail('Invalid or missing CSRF token.', 403);
        }
        Flash::set('error', 'Sitzung abgelaufen. Bitte erneut versuchen.');
        $this->back();
    }

    /**
     * Persist input so a form can be repopulated after a failed validation.
     *
     * @param array<string, mixed> $input
     */
    protected function flashOld(array $input): void
    {
        unset($input['password'], $input['verypass'], $input['_csrf_token']);
        $_SESSION['_old'] = $input;
    }
}
