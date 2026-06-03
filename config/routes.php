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
$router->post('posts/like', 'PostController@like');          // ?id=
$router->post('posts/unlike', 'PostController@unlike');      // ?id=
$router->get('posts/image', 'PostController@image');         // ?id= -> serves blob
$router->post('posts/comment', 'PostController@comment');    // ?id= -> add comment
$router->get('posts/comments', 'PostController@comments');   // ?id=&page= -> list

/* ---------- Search ---------- */
$router->get('search', 'SearchController@users');            // ?q= -> JSON users

return $router;
