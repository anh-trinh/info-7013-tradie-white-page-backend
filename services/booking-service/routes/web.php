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
    $router->post('accounts/register', 'AccountController@register');
    $router->post('quotes', 'QuoteController@store');
    $router->get('quotes', 'QuoteController@index');
});
$router->get('/', function () use ($router) {
    return $router->app->version();
});
    $router->get('/test', function () use ($router) {
        return response()->json(['status' => 'ok']);
    });
$router->get('/health', function () use ($router) {
    return response()->json(['status' => 'healthy']);
});
