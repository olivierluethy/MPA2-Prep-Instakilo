<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\Message;
use App\Models\MessageMedia;
use App\Models\MessageReaction;
use App\Models\Post;
use App\Models\User;

/**
 * Direct messages: conversation list, thread, sending (text/multi-media/link),
 * sharing a post, replies, edit, soft-delete, reactions, real-time polling,
 * typing signal and media streaming.
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

        $this->view('messages.thread', [
            'me'        => $me,
            'other'     => $other,
            'messages'  => $thread,
            'previews'  => $this->previewsFor($thread),
            'reactions' => (new MessageReaction())->forConversation($me, $otherId),
            'lastId'    => $thread === [] ? 0 : (int) end($thread)['id'],
            'rev'       => time(),
        ], $other['username'] . ' – Nachrichten');
    }

    /**
     * Send a message: optional reply, optional text, and zero or more uploaded
     * attachments (media[]). AJAX returns JSON {id}.
     */
    public function send(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);
        $me = (int) Auth::id();

        $recipientId = (int) ($request->input('recipient', '0') ?? 0);
        $body = trim(strip_tags($request->raw('body')));
        $replyToId = $this->replyTarget($request, $me, $recipientId);

        $recipient = (new User())->findById($recipientId);
        if ($recipient === null || $recipientId === $me) {
            $this->sendError($request, 'Ungültiger Empfänger.', $recipientId);
        }
        if (mb_strlen($body) > 2000) {
            $this->sendError($request, 'Nachricht ist zu lang (max. 2000 Zeichen).', $recipientId);
        }

        $files = $this->normalizeFiles($request->files('media'));
        if (count($files) > (int) config('uploads.max_files')) {
            $this->sendError($request, 'Zu viele Anhänge (max. ' . config('uploads.max_files') . ').', $recipientId);
        }
        foreach ($files as $file) {
            if (($error = $this->validateMedia($file)) !== null) {
                $this->sendError($request, $error, $recipientId);
            }
        }

        if ($files !== []) {
            $id = (new Message())->send($me, $recipientId, 'media', $body !== '' ? $body : null, null, $replyToId);
            $media = new MessageMedia();
            foreach (array_values($files) as $order => $file) {
                $mime = mime_content_type($file['tmp_name']) ?: 'application/octet-stream';
                $media->add($id, $this->mediaKind($mime), $mime, $this->safeName((string) $file['name']), (int) ($file['size'] ?? 0), file_get_contents($file['tmp_name']), $order);
            }
        } elseif ($body !== '') {
            $id = (new Message())->send($me, $recipientId, $this->urlKind($body), $body, null, $replyToId);
        } else {
            $this->sendError($request, 'Nachricht darf nicht leer sein.', $recipientId);
        }

        if ($request->wantsJson()) {
            $this->ok(['id' => $id]);
        }
        $this->redirect('messages/thread?with=' . $recipientId);
    }

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

    /** Edit own message (body). */
    public function edit(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);
        $id = $request->intQuery('id');
        if ($id === null) {
            $this->fail('Missing message id.', 422);
        }
        $messages = new Message();
        if (!$messages->isOwnedBy($id, (int) Auth::id())) {
            $this->fail('Du darfst nur eigene Nachrichten bearbeiten.', 403);
        }
        $body = trim(strip_tags($request->raw('body')));
        if ($body === '') {
            $this->fail('Nachricht darf nicht leer sein.', 422);
        }
        if (mb_strlen($body) > 2000) {
            $this->fail('Nachricht ist zu lang.', 422);
        }
        $messages->edit($id, $body);
        $this->ok(['id' => $id, 'body' => $body]);
    }

    /** Soft-delete own message. */
    public function delete(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);
        $id = $request->intQuery('id');
        if ($id === null) {
            $this->fail('Missing message id.', 422);
        }
        $messages = new Message();
        if (!$messages->isOwnedBy($id, (int) Auth::id())) {
            $this->fail('Du darfst nur eigene Nachrichten löschen.', 403);
        }
        $messages->softDelete($id);
        $this->ok(['id' => $id]);
    }

    /** Toggle an emoji reaction on a message in the user's conversation. */
    public function react(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);
        $me = (int) Auth::id();

        $id = $request->intQuery('id');
        $emoji = trim(strip_tags((string) $request->input('emoji', '')));
        if ($id === null || $emoji === '' || mb_strlen($emoji) > 8) {
            $this->fail('Ungültige Reaktion.', 422);
        }
        if (!(new Message())->participates($id, $me)) {
            $this->fail('Nicht erlaubt.', 403);
        }
        $active = (new MessageReaction())->toggle($id, $me, $emoji);
        $this->ok(['id' => $id, 'emoji' => $emoji, 'active' => $active]);
    }

    /** Real-time polling: new messages + revisions (edits/deletes) + reactions. */
    public function poll(Request $request): void
    {
        $this->requireAuth($request);
        $me = (int) Auth::id();
        $otherId = $request->intQuery('with');
        if ($otherId === null) {
            $this->fail('Missing peer id.', 422);
        }
        $after = (int) ($request->query('after', '0') ?? 0);
        $rev = (int) ($request->query('rev', '0') ?? 0);

        $messages = new Message();
        $new = $messages->since($me, $otherId, $after);
        if ($new !== []) {
            $messages->markRead($me, $otherId);
        }
        $revisions = $rev > 0 ? $messages->revisionsSince($me, $otherId, $rev, $after) : [];

        $this->ok([
            'messages'  => array_map(fn ($m) => $this->renderMessage($m, $me), $new),
            'revisions' => array_map(fn ($m) => ['id' => (int) $m['id'], 'html' => $this->bubbleHtml($m, $me)], $revisions),
            'reactions' => (new MessageReaction())->forConversation($me, $otherId),
            'typing'    => $messages->peerTyping($me, $otherId),
            'lastId'    => $new === [] ? $after : (int) end($new)['id'],
            'rev'       => time(),
        ]);
    }

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

    public function unread(Request $request): void
    {
        $this->requireAuth($request);
        $this->ok(['count' => (new Message())->unreadCount((int) Auth::id())]);
    }

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

    private function renderMessage(array $m, int $me): array
    {
        return [
            'id'   => (int) $m['id'],
            'mine' => (int) $m['sender_id'] === $me,
            'ts'   => (int) strtotime((string) $m['created_at']),
            'text' => (string) ($m['body'] ?? ''),
            'html' => $this->bubbleHtml($m, $me),
        ];
    }

    private function bubbleHtml(array $m, int $me): string
    {
        $preview = $m['shared_post_id'] !== null ? (new Post())->preview((int) $m['shared_post_id']) : null;
        return View::partial('partials.message', ['m' => $m, 'me' => $me, 'preview' => $preview]);
    }

    private function replyTarget(Request $request, int $me, int $recipientId): ?int
    {
        $raw = $request->input('reply_to', '');
        if ($raw === null || !ctype_digit((string) $raw)) {
            return null;
        }
        $id = (int) $raw;
        // The referenced message must belong to this conversation.
        return (new Message())->participates($id, $me) ? $id : null;
    }

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

    /** Flatten PHP's multi-file $_FILES into a list of single-file arrays. */
    private function normalizeFiles(array $files): array
    {
        if (empty($files['name'])) {
            return [];
        }
        $names = (array) $files['name'];
        $out = [];
        foreach ($names as $i => $name) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name'     => $name,
                'type'     => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error'    => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $files['size'][$i] ?? 0,
            ];
        }
        return $out;
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
