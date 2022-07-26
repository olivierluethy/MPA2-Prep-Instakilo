<?php
require 'core/bootstrap.php';

$routes = [
	'' => 'FrameworkController@index',
	'view' => 'FrameworkController@index',
	'create' => 'FrameworkController@create',
	'update' => 'FrameworkController@update',
	'delete' => 'FrameworkController@delete',
];

$db = [
	'name'     => 'framework',
	'username' => 'root',
	'password' => '',
];

$router = new Router($routes);
$router->run($_GET['url'] ?? '');