<?php
require 'core/bootstrap.php';

$routes = [
	/* Home Page */
	'' => 'InstakiloController@index',
	'home' => 'InstakiloController@index',

	'imageUpload' => 'ImageUploadController@index',

	/* Login Page */
	'login' => 'LoginController@login',
	'register' => 'LoginController@register',
	'logout' => 'LoginController@logout',

	'view' => 'FrameworkController@index',
	'create' => 'FrameworkController@create',
	'update' => 'FrameworkController@update',
	'delete' => 'FrameworkController@delete',
];

$db = [
	'name'     => 'instakilo',
	'username' => 'root',
	'password' => '',
];

$router = new Router($routes);
$router->run($_GET['url'] ?? '');