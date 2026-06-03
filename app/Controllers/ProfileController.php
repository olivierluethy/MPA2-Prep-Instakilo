<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Follow;
use App\Models\Post;
use App\Models\User;

/**
 * The current user's profile, other users' profiles, profile updates and the
 * avatar image endpoint.
 */
final class ProfileController extends Controller
{
    public function me(Request $request): void
    {
        $this->requireAuth($request);

        $id     = (int) Auth::id();
        $user   = (new User())->findById($id);
        $follow = new Follow();

        if ($user === null) {
            Auth::logout();
            $this->redirect('login');
        }

        $this->view('profile.me', [
            'user'      => $user,
            'followers' => $follow->followerCount($id),
            'following' => $follow->followingCount($id),
            'posts'     => (new Post())->byUser($id, $id, true),
        ], $user['username'] . ' – Instakilo');
    }

    public function visit(Request $request): void
    {
        $id = $request->intQuery('id');
        if ($id === null) {
            $this->redirect('home');
        }

        $user = (new User())->findById($id);
        if ($user === null) {
            http_response_code(404);
            $this->view('errors.error', [
                'code' => 404, 'heading' => 'Profil nicht gefunden', 'detail' => '',
            ], '404 – Instakilo');
            return;
        }

        $viewerId = Auth::id();
        $follow   = new Follow();
        $isOwnProfile = $viewerId === $id;

        if ($isOwnProfile) {
            $this->redirect('profile');
        }

        $isFollowing = $viewerId !== null && $follow->isFollowing($id, $viewerId);

        $this->view('profile.visit', [
            'user'        => $user,
            'followers'   => $follow->followerCount($id),
            'following'   => $follow->followingCount($id),
            'isFollowing' => $isFollowing,
            'isLoggedIn'  => $viewerId !== null,
            'posts'       => (new Post())->byUser($id, $viewerId, $isFollowing),
        ], $user['username'] . ' – Instakilo');
    }

    /**
     * Update the current user's username, description, password or avatar.
     * The form sends only the section being edited.
     */
    public function update(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);

        $id    = (int) Auth::id();
        $users = new User();

        $username    = $request->input('username');
        $description = $request->input('description');
        $password    = $request->raw('password');

        if ($username !== null && $username !== '') {
            $v = (new Validator(['username' => $username]))
                ->max('username', 255, 'Benutzername ist zu lang.');
            if ($v->fails()) {
                $this->updateFailed($v->firstError());
            }
            if ($username !== Auth::username() && $users->usernameExists($username)) {
                $this->updateFailed('Dieser Benutzername ist bereits vergeben.');
            }
            $users->updateUsername($id, $username);
            $_SESSION['username'] = $username;
        }

        if ($description !== null) {
            $v = (new Validator(['description' => $description]))
                ->max('description', 500, 'Beschreibung ist zu lang (max. 500 Zeichen).');
            if ($v->fails()) {
                $this->updateFailed($v->firstError());
            }
            $users->updateDescription($id, $description);
        }

        if ($password !== '') {
            $v = (new Validator(['password' => $password, 'verypass' => $request->raw('verypass')]))
                ->min('password', 6, 'Passwort muss mindestens 6 Zeichen lang sein.')
                ->matches('verypass', 'password', 'Passwörter stimmen nicht überein.');
            if ($v->fails()) {
                $this->updateFailed($v->firstError());
            }
            $users->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
        }

        $avatar = $request->files('avatar');
        if (!empty($avatar['tmp_name']) && ($avatar['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $error = $this->validateImage($avatar);
            if ($error !== null) {
                $this->updateFailed($error);
            }
            $users->updateAvatar($id, mime_content_type($avatar['tmp_name']), file_get_contents($avatar['tmp_name']));
        }

        Flash::set('success', 'Profil aktualisiert.');
        $this->redirect('profile');
    }

    /**
     * Stream a user's avatar. Falls back to the default avatar asset.
     */
    public function avatar(Request $request): void
    {
        $id = $request->intQuery('id');
        $avatar = $id !== null ? (new User())->avatar($id) : null;

        if ($avatar === null) {
            $this->redirect('assets/profile.png');
        }

        $this->streamImage($avatar['image_type'] ?? $avatar['avatar_type'], $avatar['avatar_data'], "avatar-{$id}");
    }

    private function updateFailed(?string $message): never
    {
        Flash::set('error', $message ?? 'Aktualisierung fehlgeschlagen.');
        $this->redirect('profile');
    }

    /**
     * @return string|null error message, or null when valid
     */
    private function validateImage(array $file): ?string
    {
        $maxSize = (int) config('uploads.max_file_size');
        if (($file['size'] ?? 0) > $maxSize) {
            return 'Bild ist zu groß (max. ' . round($maxSize / 1024 / 1024, 1) . ' MB).';
        }
        $info = @getimagesize($file['tmp_name']);
        $allowed = config('uploads.allowed_mime', []);
        if ($info === false || !in_array($info['mime'], $allowed, true)) {
            return 'Nur JPEG-, PNG-, GIF- oder WebP-Bilder sind erlaubt.';
        }
        return null;
    }

    private function streamImage(string $mime, string $data, string $etagSeed): void
    {
        $etag = '"' . md5($etagSeed . strlen($data)) . '"';
        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);
            exit;
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($data));
        header('Cache-Control: private, max-age=86400');
        header('ETag: ' . $etag);
        echo $data;
        exit;
    }
}
