<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Message;
use App\Models\Post;
use App\Models\User;

/**
 * Direct messages: conversation list, thread view, sending, and sharing a post.
 */
final class MessageController extends Controller
{
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

        // Resolve previews for any shared posts in the thread.
        $previews = [];
        $postModel = new Post();
        foreach ($thread as $m) {
            $pid = $m['shared_post_id'] !== null ? (int) $m['shared_post_id'] : null;
            if ($pid !== null && !array_key_exists($pid, $previews)) {
                $previews[$pid] = $postModel->preview($pid);
            }
        }

        $this->view('messages.thread', [
            'me'       => $me,
            'other'    => $other,
            'messages' => $thread,
            'previews' => $previews,
        ], $other['username'] . ' – Nachrichten');
    }

    public function send(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);
        $me = (int) Auth::id();

        $recipientId = (int) ($request->input('recipient', '0') ?? 0);
        $body = trim(strip_tags($request->raw('body')));

        $validator = (new Validator(['body' => $body]))
            ->required('body', 'Nachricht darf nicht leer sein.')
            ->max('body', 2000, 'Nachricht ist zu lang (max. 2000 Zeichen).');

        $recipient = (new User())->findById($recipientId);
        if ($recipient === null || $recipientId === $me) {
            $validator->addError('recipient', 'Ungültiger Empfänger.');
        }

        if ($validator->fails()) {
            Flash::set('error', (string) $validator->firstError());
            $this->redirect($recipientId > 0 ? 'messages/thread?with=' . $recipientId : 'messages');
        }

        (new Message())->send($me, $recipientId, $body, null);
        $this->redirect('messages/thread?with=' . $recipientId);
    }

    /**
     * Share a post with another user via DM (optional note). JSON or redirect.
     */
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

        // The sender must be able to see the post they are sharing.
        if ((new Post())->byIds([$postId], $me) === []) {
            $this->fail('Du kannst diesen Beitrag nicht teilen.', 403);
        }

        (new Message())->send($me, $recipientId, $note !== '' ? $note : null, $postId);

        $target = url('messages/thread?with=' . $recipientId);
        if ($request->wantsJson()) {
            $this->ok(['redirect' => $target]);
        }
        $this->redirect('messages/thread?with=' . $recipientId);
    }
}
