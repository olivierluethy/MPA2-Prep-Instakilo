<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Validator;
use App\Models\User;

/**
 * Authentication: login, registration and logout.
 *
 * Fixes over the legacy LoginController: no inline HTML/echo, no double
 * session_start(), prepared statements via the model, CSRF on every POST,
 * generic credential errors (no user enumeration) and auto-login after signup.
 */
final class AuthController extends Controller
{
    public function show(Request $request): void
    {
        if (Auth::check()) {
            $this->redirect('home');
        }
        $this->view('auth.login', [], 'Login – Instakilo');
    }

    public function login(Request $request): void
    {
        $this->requireCsrf($request);

        $login    = (string) $request->input('login', '');
        $password = $request->raw('password');

        $validator = (new Validator(['login' => $login, 'password' => $password]))
            ->required('login', 'Bitte E-Mail oder Benutzername eingeben.')
            ->required('password', 'Bitte Passwort eingeben.');

        if ($validator->fails()) {
            $this->loginFailed($validator->firstError(), ['login' => $login]);
        }

        $user = (new User())->findByLogin($login);

        // Same generic message whether the account or the password is wrong.
        if ($user === null || !password_verify($password, $user['password'])) {
            $this->loginFailed('E-Mail/Benutzername oder Passwort ist falsch.', ['login' => $login]);
        }

        Auth::login((int) $user['id'], $user['username'], $user['email']);
        Flash::set('success', 'Willkommen zurück, ' . $user['username'] . '!');
        $this->redirect('home');
    }

    public function register(Request $request): void
    {
        $this->requireCsrf($request);

        $username = (string) $request->input('username', '');
        $email    = (string) $request->input('email', '');
        $password = $request->raw('password');
        $verify   = $request->raw('verypass');

        $validator = (new Validator([
            'username' => $username,
            'email'    => $email,
            'password' => $password,
            'verypass' => $verify,
        ]))
            ->required('username', 'Bitte Benutzername eingeben.')
            ->max('username', 255, 'Benutzername ist zu lang.')
            ->required('email', 'Bitte E-Mail eingeben.')
            ->email('email', 'Bitte eine gültige E-Mail eingeben.')
            ->required('password', 'Bitte Passwort eingeben.')
            ->min('password', 6, 'Passwort muss mindestens 6 Zeichen lang sein.')
            ->matches('verypass', 'password', 'Passwörter stimmen nicht überein.');

        $users = new User();
        if ($validator->passes()) {
            if ($users->emailExists($email)) {
                $validator->addError('email', 'Diese E-Mail ist bereits registriert.');
            }
            if ($users->usernameExists($username)) {
                $validator->addError('username', 'Dieser Benutzername ist bereits vergeben.');
            }
        }

        if ($validator->fails()) {
            $this->flashOld(['login' => $email, 'username' => $username, 'email' => $email]);
            Flash::set('error', (string) $validator->firstError());
            $this->redirect('login');
        }

        $id = $users->create($username, $email, password_hash($password, PASSWORD_DEFAULT));

        // Auto-login the freshly created account.
        Auth::login($id, $username, $email);
        Flash::set('success', 'Konto erstellt. Willkommen bei Instakilo!');
        $this->redirect('home');
    }

    public function logout(Request $request): void
    {
        $this->requireCsrf($request);
        Auth::logout();
        $this->redirect('login');
    }

    private function loginFailed(?string $message, array $old): never
    {
        $this->flashOld($old);
        Flash::set('error', $message ?? 'Anmeldung fehlgeschlagen.');
        $this->redirect('login');
    }
}
