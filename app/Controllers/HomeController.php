<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Follow;
use App\Models\Post;
use App\Models\Repost;

/**
 * The home feed. Logged-in users get a chronological timeline that merges their
 * own/followed/public posts (scope-dependent) with reposts from their network.
 */
final class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $postModel = new Post();
        $viewerId = Auth::id();

        if ($viewerId === null) {
            $this->view('home.index', [
                'posts'      => $postModel->publicFeed(),
                'isLoggedIn' => false,
                'viewerId'   => null,
                'scope'      => 'all',
            ], 'Instakilo – Feed');
            return;
        }

        $scope = $request->query('feed') === 'following' ? 'following' : 'all';
        $feed = $this->buildFeed($postModel, $viewerId, $scope);

        $this->view('home.index', [
            'posts'      => $feed,
            'isLoggedIn' => true,
            'viewerId'   => $viewerId,
            'scope'      => $scope,
        ], 'Instakilo – Feed');
    }

    /**
     * Merge direct posts with repost events from the viewer's network into one
     * chronological timeline.
     */
    private function buildFeed(Post $postModel, int $viewerId, string $scope): array
    {
        $base = $postModel->feedFor($viewerId, $scope);

        // Reposts authored by the viewer or anyone they follow.
        $reposterIds = array_merge([$viewerId], (new Follow())->followingIds($viewerId));
        $events = (new Repost())->eventsBy($reposterIds);

        // One repost entry per post (most recent reposter wins).
        $seen = [];
        $uniqueEvents = [];
        foreach ($events as $event) {
            if (isset($seen[$event['post_id']])) {
                continue;
            }
            $seen[$event['post_id']] = true;
            $uniqueEvents[] = $event;
        }

        $repostPostsById = [];
        foreach ($postModel->byIds(array_column($uniqueEvents, 'post_id'), $viewerId) as $p) {
            $repostPostsById[(int) $p['id']] = $p;
        }

        $repostItems = [];
        foreach ($uniqueEvents as $event) {
            $pid = $event['post_id'];
            if (!isset($repostPostsById[$pid])) {
                continue; // not visible (e.g. no longer public)
            }
            $item = $repostPostsById[$pid];
            $item['reposted_by'] = $event['reposter_username'];
            $item['event_time']  = $event['created_at'];
            $repostItems[] = $item;
        }

        $feed = array_merge($base, $repostItems);
        usort(
            $feed,
            static fn (array $a, array $b): int => strcmp((string) $b['event_time'], (string) $a['event_time'])
        );

        return $feed;
    }
}
