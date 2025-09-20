<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['prefix' => 'api/accounts'], function () use ($router) {
    $router->post('register', 'AccountController@register');
    $router->post('login', 'AccountController@login');
    $router->get('me', ['middleware' => 'jwt', 'uses' => 'AccountController@me']);
    $router->put('me', ['middleware' => 'jwt', 'uses' => 'AccountController@updateMe']);
});

$router->group(['prefix' => 'api/admin/accounts', 'middleware' => 'jwt:admin'], function () use ($router) {
    $router->get('/', 'AdminAccountController@list');
    $router->get('{id}', 'AdminAccountController@get');
    $router->put('{id}/status', 'AdminAccountController@updateStatus');
    $router->delete('{id}', 'AdminAccountController@delete');
});

$router->get('/', function () use ($router) {
    return $router->app->version();
});
