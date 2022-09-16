<?php
require 'core/bootstrap.php';

$routes = [
	/* Home Page */
	'' => 'InstakiloController@index',
	'home' => 'InstakiloController@index',

	'profile' => 'InstakiloController@profile',

	/* Check someone else out, follow and unfollow */
	'visitProfile' => 'InstakiloController@visitProfile',
	'follow' => 'InstakiloController@follow',
	'unfollow' => 'InstakiloController@unfollow',

	/* Like Post */
	'likePost' => 'InstakiloController@likePost',
	'unlikePost' => 'InstakiloController@unlikePost',
	'imageUpload' => 'ImageUploadController@index',

	/* Login Page */
	'login' => 'LoginController@login',
	'register' => 'LoginController@register',
	'logout' => 'LoginController@logout',
];

$db = [
	'name'     => 'instakilo',
	'username' => 'root',
	'password' => '',
];

$router = new Router($routes);
$router->run($_GET['url'] ?? '');