<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Post;

/**
 * The home feed.
 */
final class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $posts = new Post();

        if (Auth::check()) {
            $feed = $posts->feedFor((int) Auth::id());
        } else {
            $feed = $posts->publicFeed();
        }

        $this->view('home.index', [
            'posts'      => $feed,
            'isLoggedIn' => Auth::check(),
        ], 'Instakilo – Feed');
    }
}
