<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Follow;
use App\Models\Notification;

/**
 * Follow / unfollow actions. POST-only and CSRF-protected (previously these
 * mutated data over GET, which is unsafe).
 */
final class FollowController extends Controller
{
    public function follow(Request $request): void
    {
        $this->handle($request, true);
    }

    public function unfollow(Request $request): void
    {
        $this->handle($request, false);
    }

    private function handle(Request $request, bool $follow): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);

        $targetId = $request->intQuery('id');
        if ($targetId === null) {
            $this->fail('Missing user id.', 422);
        }

        $model = new Follow();
        $me = (int) Auth::id();

        if ($follow) {
            $model->follow($targetId, $me);
            // Notify the followed user (reference = the follower's own id).
            (new Notification())->create($targetId, $me, 'follow', $me);
        } else {
            $model->unfollow($targetId, $me);
        }

        if ($request->wantsJson()) {
            $this->ok([
                'following'     => $model->isFollowing($targetId, $me),
                'followerCount' => $model->followerCount($targetId),
            ]);
        }

        $this->redirect('profile/visit?id=' . $targetId);
    }
}
