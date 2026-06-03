<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Validator;
use App\Core\View;
use App\Models\Message;
use App\Models\MessageMedia;
use App\Models\Post;
use App\Models\User;

/**
 * Direct messages: conversation list, thread, sending (text/media/link),
 * sharing a post, real-time polling, typing signal and media streaming.
 */
final class MessageController extends Controller
{
    private const INLINE_KINDS = ['image', 'gif', 'video'];

    public function index(Request $request): void
    {
        $this->requireAuth($request);
        $this->view('messages.index', [
            'conversations' => (new Message())->conversations((int) Auth::id()),
        ], 'Nachrichten – Instakilo');
    }

    public function thread(Request $request): void
    {
        $this->requireAuth($request);
        $me = (int) Auth::id();

        $otherId = $request->intQuery('with');
        if ($otherId === null || $otherId === $me) {
            $this->redirect('messages');
        }

        $other = (new User())->findById($otherId);
        if ($other === null) {
            http_response_code(404);
            $this->view('errors.error', ['code' => 404, 'heading' => 'Benutzer nicht gefunden', 'detail' => ''], '404 – Instakilo');
            return;
        }

        $messages = new Message();
        $messages->markRead($me, $otherId);
        $thread = $messages->thread($me, $otherId);

        $previews = $this->previewsFor($thread);
        $lastId = $thread === [] ? 0 : (int) end($thread)['id'];

        $this->view('messages.thread', [
            'me'       => $me,
            'other'    => $other,
            'messages' => $thread,
            'previews' => $previews,
            'lastId'   => $lastId,
        ], $other['username'] . ' – Nachrichten');
    }

    /**
     * Send a message: optional uploaded media (image/gif/video/file) and/or text
     * (which may itself be a media URL or a link). AJAX returns JSON {id}.
     */
    public function send(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);
        $me = (int) Auth::id();
        $json = $request->wantsJson();

        $recipientId = (int) ($request->input('recipient', '0') ?? 0);
        $body = trim(strip_tags($request->raw('body')));

        $recipient = (new User())->findById($recipientId);
        if ($recipient === null || $recipientId === $me) {
            $this->sendError($request, 'Ungültiger Empfänger.', $recipientId);
        }
        if (mb_strlen($body) > 2000) {
            $this->sendError($request, 'Nachricht ist zu lang (max. 2000 Zeichen).', $recipientId);
        }

        $file = $request->files('media');
        $hasFile = !empty($file['tmp_name']) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

        if ($hasFile) {
            if (($error = $this->validateMedia($file)) !== null) {
                $this->sendError($request, $error, $recipientId);
            }
            $mime = mime_content_type($file['tmp_name']) ?: 'application/octet-stream';
            $kind = $this->mediaKind($mime);
            $id = (new Message())->send($me, $recipientId, $kind, $body !== '' ? $body : null, null);
            (new MessageMedia())->add(
                $id,
                $kind,
                $mime,
                $this->safeName((string) $file['name']),
                (int) ($file['size'] ?? 0),
                file_get_contents($file['tmp_name'])
            );
        } elseif ($body !== '') {
            $id = (new Message())->send($me, $recipientId, $this->urlKind($body), $body, null);
        } else {
            $this->sendError($request, 'Nachricht darf nicht leer sein.', $recipientId);
        }

        if ($json) {
            $this->ok(['id' => $id]);
        }
        $this->redirect('messages/thread?with=' . $recipientId);
    }

    /** Share a post via DM (kind = post). */
    public function share(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);
        $me = (int) Auth::id();

        $postId = $request->intQuery('id');
        $recipientId = (int) ($request->input('recipient', '0') ?? 0);
        $note = trim(strip_tags($request->raw('body')));

        if ($postId === null) {
            $this->fail('Missing post id.', 422);
        }
        $recipient = (new User())->findById($recipientId);
        if ($recipient === null || $recipientId === $me) {
            $this->fail('Ungültiger Empfänger.', 422);
        }
        if ((new Post())->byIds([$postId], $me) === []) {
            $this->fail('Du kannst diesen Beitrag nicht teilen.', 403);
        }

        (new Message())->send($me, $recipientId, 'post', $note !== '' ? $note : null, $postId);

        if ($request->wantsJson()) {
            $this->ok(['redirect' => url('messages/thread?with=' . $recipientId)]);
        }
        $this->redirect('messages/thread?with=' . $recipientId);
    }

    /** Real-time polling: new messages since ?after= + peer typing flag. */
    public function poll(Request $request): void
    {
        $this->requireAuth($request);
        $me = (int) Auth::id();
        $otherId = $request->intQuery('with');
        if ($otherId === null) {
            $this->fail('Missing peer id.', 422);
        }
        $after = (int) ($request->query('after', '0') ?? 0);

        $messages = new Message();
        $new = $messages->since($me, $otherId, $after);
        if ($new !== []) {
            $messages->markRead($me, $otherId);
        }

        $previews = $this->previewsFor($new);
        $lastId = $after;
        $rendered = [];
        foreach ($new as $m) {
            $lastId = max($lastId, (int) $m['id']);
            $pid = $m['shared_post_id'] !== null ? (int) $m['shared_post_id'] : null;
            $rendered[] = [
                'id'   => (int) $m['id'],
                'mine' => (int) $m['sender_id'] === $me,
                'kind' => $m['kind'],
                'ts'   => strtotime((string) $m['created_at']),
                'text' => (string) ($m['body'] ?? ''),
                'html' => View::partial('partials.message', ['m' => $m, 'me' => $me, 'preview' => $pid ? $previews[$pid] : null]),
            ];
        }

        $this->ok([
            'messages' => $rendered,
            'lastId'   => $lastId,
            'typing'   => $messages->peerTyping($me, $otherId),
        ]);
    }

    /** Total unread DM count for the nav badge (live polling). */
    public function unread(Request $request): void
    {
        $this->requireAuth($request);
        $this->ok(['count' => (new Message())->unreadCount((int) Auth::id())]);
    }

    /** Record that the current user is typing to ?with=. */
    public function typing(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);
        $otherId = $request->intQuery('with');
        if ($otherId !== null) {
            (new Message())->setTyping((int) Auth::id(), $otherId);
        }
        $this->ok([]);
    }

    /** Stream a DM attachment (participants only). */
    public function media(Request $request): void
    {
        $this->requireAuth($request);
        $id = $request->intQuery('id');
        $media = $id !== null ? (new MessageMedia())->findForParticipant($id, (int) Auth::id()) : null;

        if ($media === null) {
            http_response_code(404);
            exit;
        }

        $data = $media['data'];
        $inline = in_array($media['kind'], self::INLINE_KINDS, true);

        // Inline only known-safe media types; everything else downloads. nosniff
        // stops the browser from re-interpreting the content type.
        header('Content-Type: ' . ($inline ? $media['mime'] : 'application/octet-stream'));
        header('X-Content-Type-Options: nosniff');
        if (!$inline) {
            header('Content-Disposition: attachment; filename="' . $this->safeName($media['file_name']) . '"');
        }
        header('Content-Length: ' . strlen($data));
        header('Cache-Control: private, max-age=86400');
        echo $data;
        exit;
    }

    /* ---------- helpers ---------- */

    private function sendError(Request $request, string $message, int $recipientId): never
    {
        if ($request->wantsJson()) {
            $this->fail($message, 422);
        }
        Flash::set('error', $message);
        $this->redirect($recipientId > 0 ? 'messages/thread?with=' . $recipientId : 'messages');
    }

    /** @param array<int, array> $messages */
    private function previewsFor(array $messages): array
    {
        $previews = [];
        $postModel = new Post();
        foreach ($messages as $m) {
            $pid = $m['shared_post_id'] !== null ? (int) $m['shared_post_id'] : null;
            if ($pid !== null && !array_key_exists($pid, $previews)) {
                $previews[$pid] = $postModel->preview($pid);
            }
        }
        return $previews;
    }

    private function validateMedia(array $file): ?string
    {
        if (!is_uploaded_file($file['tmp_name'])) {
            return 'Ungültiger Upload.';
        }
        $max = (int) config('uploads.max_media_size');
        if (($file['size'] ?? 0) > $max) {
            return 'Datei ist zu groß (max. ' . round($max / 1024 / 1024) . ' MB).';
        }
        return null;
    }

    private function mediaKind(string $mime): string
    {
        if ($mime === 'image/gif') {
            return 'gif';
        }
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return 'image';
        }
        if (in_array($mime, ['video/mp4', 'video/webm', 'video/ogg'], true)) {
            return 'video';
        }
        return 'file';
    }

    /** Classify a text body as a media URL, a link, or plain text. */
    private function urlKind(string $text): string
    {
        if (preg_match('#^https?://\S+$#i', $text) && !preg_match('#\s#', $text)) {
            $ext = strtolower(pathinfo((string) parse_url($text, PHP_URL_PATH), PATHINFO_EXTENSION));
            if ($ext === 'gif') {
                return 'gif';
            }
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'bmp'], true)) {
                return 'image';
            }
            if (in_array($ext, ['mp4', 'webm', 'ogg', 'mov'], true)) {
                return 'video';
            }
            return 'link';
        }
        return preg_match('#https?://#i', $text) ? 'link' : 'text';
    }

    private function safeName(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('#[\r\n"\\\\/]+#', '_', $name) ?? 'datei';
        return mb_substr($name, 0, 200) ?: 'datei';
    }
}
