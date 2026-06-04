<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\View;
use App\Models\Notification;

/**
 * Notification center: the page, real-time polling (mirrors the DM poll), the
 * nav unread badge, pagination, and read-state mutations. Every action is
 * scoped to the authenticated user — you only ever see or touch your own rows.
 */
final class NotificationController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth($request);
        $me = (int) Auth::id();

        $model = new Notification();
        $items = $model->forUser($me, Notification::PER_PAGE);

        $this->view('notifications.index', [
            'items'   => $items,
            'unread'  => $model->unreadCount($me),
            'hasMore' => count($items) >= Notification::PER_PAGE,
            'sig'     => $model->signature($me),
        ], 'Benachrichtigungen – Instakilo');
    }

    /**
     * Real-time poll. Returns the unread count always, and the freshly rendered
     * top page only when the feed actually changed since the client's signature
     * (so an idle tab does no rendering work).
     */
    public function poll(Request $request): void
    {
        $this->requireAuth($request);
        $me = (int) Auth::id();

        $model = new Notification();
        $sig = $model->signature($me);
        $clientSig = (string) ($request->query('sig', '') ?? '');

        $data = ['count' => $model->unreadCount($me), 'sig' => $sig, 'changed' => $sig !== $clientSig];
        if ($data['changed']) {
            $items = $model->forUser($me, Notification::PER_PAGE);
            $data['html'] = $this->renderList($items);
            $data['empty'] = $items === [];
            $data['hasMore'] = count($items) >= Notification::PER_PAGE;
        }
        $this->ok($data);
    }

    /** Lightweight unread count for the global nav badge (polled on every page). */
    public function unread(Request $request): void
    {
        $this->requireAuth($request);
        $this->ok(['count' => (new Notification())->unreadCount((int) Auth::id())]);
    }

    /** Older pages for lazy loading ("Mehr laden"). */
    public function more(Request $request): void
    {
        $this->requireAuth($request);
        $me = (int) Auth::id();

        $page = max(1, (int) ($request->query('page', '1') ?? 1));
        $offset = ($page - 1) * Notification::PER_PAGE;
        $items = (new Notification())->forUser($me, Notification::PER_PAGE, $offset);

        $this->ok([
            'html'    => $this->renderList($items),
            'hasMore' => count($items) >= Notification::PER_PAGE,
        ]);
    }

    /** Mark one notification (or its whole group) as read. */
    public function read(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);

        $id = $request->intQuery('id');
        if ($id === null) {
            $this->fail('Missing notification id.', 422);
        }
        $count = (new Notification())->markRead((int) Auth::id(), $id);
        $this->ok(['count' => $count]);
    }

    /** Mark every notification as read. */
    public function readAll(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);

        $model = new Notification();
        $model->markAllRead((int) Auth::id());
        $this->ok(['count' => 0]);
    }

    /** @param array<int, array> $items */
    private function renderList(array $items): string
    {
        return View::partial('partials.notification-list', ['items' => $items]);
    }
}
