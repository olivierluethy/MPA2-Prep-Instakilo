<?php

declare(strict_types=1);

use App\Core\Router;

/**
 * Application route table.
 *
 * Convention: GET renders pages / serves assets; POST performs every
 * state-changing action (login, follow, like, upload). This closes the old
 * design where likes/follows mutated data over GET.
 */
$router = new Router();

/* ---------- Home / feed ---------- */
$router->get('', 'HomeController@index');
$router->get('home', 'HomeController@index');

/* ---------- Auth ---------- */
$router->get('login', 'AuthController@show');
$router->post('login', 'AuthController@login');
$router->post('register', 'AuthController@register');
$router->post('logout', 'AuthController@logout');

/* ---------- Profile ---------- */
$router->get('profile', 'ProfileController@me');
$router->get('profile/visit', 'ProfileController@visit');   // ?id=
$router->post('profile/update', 'ProfileController@update');
$router->get('avatar', 'ProfileController@avatar');          // ?id= -> serves blob

/* ---------- Follow ---------- */
$router->post('follow', 'FollowController@follow');          // ?id=
$router->post('unfollow', 'FollowController@unfollow');      // ?id=

/* ---------- Posts ---------- */
$router->post('posts/store', 'PostController@store');
$router->get('post', 'PostController@show');                 // ?id= -> single post page
$router->get('posts/edit', 'PostController@edit');           // ?id= -> JSON edit data
$router->post('posts/update', 'PostController@update');      // ?id= -> edit post
$router->post('posts/delete', 'PostController@delete');      // ?id= -> delete post
$router->post('posts/like', 'PostController@like');          // ?id=
$router->post('posts/unlike', 'PostController@unlike');      // ?id=
$router->get('posts/image', 'PostController@image');         // ?id= -> serves blob

/* ---------- Comments ---------- */
$router->post('posts/comment', 'PostController@comment');           // ?id= -> add
$router->post('posts/comment/update', 'PostController@commentUpdate'); // ?id= -> edit
$router->post('posts/comment/delete', 'PostController@commentDelete'); // ?id= -> delete
$router->get('posts/comments', 'PostController@comments');          // ?id=&page= -> list

/* ---------- Save / Repost ---------- */
$router->post('posts/save', 'PostController@save');          // ?id=
$router->post('posts/unsave', 'PostController@unsave');      // ?id=
$router->post('posts/repost', 'PostController@repost');      // ?id=
$router->post('posts/unrepost', 'PostController@unrepost');  // ?id=
$router->get('saved', 'PostController@saved');               // saved collection page

/* ---------- Direct messages ---------- */
$router->get('messages', 'MessageController@index');
$router->get('messages/thread', 'MessageController@thread'); // ?with=
$router->post('messages/send', 'MessageController@send');
$router->post('messages/share', 'MessageController@share');  // ?id= (postId)

/* ---------- Search ---------- */
$router->get('search', 'SearchController@users');            // ?q= -> JSON users + posts

return $router;
