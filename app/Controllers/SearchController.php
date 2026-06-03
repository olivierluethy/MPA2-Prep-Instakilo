<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\User;

/**
 * User search used by the nav search box. Returns JSON consumed by app.js.
 */
final class SearchController extends Controller
{
    public function users(Request $request): void
    {
        $query = (string) $request->query('q', '');

        // Require at least one character to avoid dumping the whole table.
        if (trim($query) === '') {
            $this->ok(['users' => []]);
        }

        $results = (new User())->search($query);

        $this->ok([
            'users' => array_map(
                static fn (array $u): array => [
                    'id'       => (int) $u['id'],
                    'username' => $u['username'],
                ],
                $results
            ),
        ]);
    }
}
