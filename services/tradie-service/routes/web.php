$router->group(['prefix' => 'api/tradies'], function () use ($router) {
    $router->get('/', 'TradieController@index');
    $router->get('{id}', 'TradieController@show');
});

$router->get('api/services', 'ServiceCategoryController@index');

$router->group(['prefix' => 'api/admin/categories', 'middleware' => 'jwt:admin'], function () use ($router) {
    $router->post('/', 'ServiceCategoryController@store');
    $router->get('/', 'ServiceCategoryController@index');
    $router->get('{id}', 'ServiceCategoryController@show');
    $router->put('{id}', 'ServiceCategoryController@update');
    $router->delete('{id}', 'ServiceCategoryController@destroy');
});
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

$router->get('/', function () use ($router) {
    return $router->app->version();
});
