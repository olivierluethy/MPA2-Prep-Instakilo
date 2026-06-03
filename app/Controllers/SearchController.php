<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Post;
use App\Models\User;

/**
 * Search for users and posts. Returns JSON consumed by the nav search box.
 */
final class SearchController extends Controller
{
    public function users(Request $request): void
    {
        $query = (string) $request->query('q', '');

        if (trim($query) === '') {
            $this->ok(['users' => [], 'posts' => []]);
        }

        $viewerId = Auth::id();

        $userResults = (new User())->search($query);
        $postResults = (new Post())->search($query, $viewerId);

        $this->ok([
            'users' => array_map(
                static fn (array $u): array => ['id' => (int) $u['id'], 'username' => $u['username']],
                $userResults
            ),
            'posts' => array_map(
                static fn (array $p): array => [
                    'id'       => $p['id'],
                    'title'    => $p['title'],
                    'username' => $p['username'],
                    'imageId'  => $p['cover_image_id'],
                ],
                $postResults
            ),
        ]);
    }
}
