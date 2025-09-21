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

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('/accounts/register', 'AccountController@register');
    $router->post('/accounts/login', 'AccountController@login');

    $router->group(['middleware' => 'auth'], function () use ($router) {
        $router->get('/accounts/me', 'AccountController@me');
        $router->put('/accounts/me', 'AccountController@updateProfile');

        $router->group(['prefix' => 'admin'], function () use ($router) {
            $router->get('/accounts', 'AccountController@getAllAccounts');
            $router->put('/accounts/{id}/status', 'AccountController@updateAccountStatus');
        });
    });
});
