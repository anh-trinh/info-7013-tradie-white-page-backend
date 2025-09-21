<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('/tradies', 'TradieController@search');
    $router->get('/tradies/{id}', 'TradieController@getById');
    $router->get('/services', 'CategoryController@getAll');

    $router->group(['prefix' => 'admin'], function () use ($router) {
        $router->get('/categories', 'CategoryController@getAll');
        $router->post('/categories', 'CategoryController@create');
        $router->put('/categories/{id}', 'CategoryController@update');
        $router->delete('/categories/{id}', 'CategoryController@delete');
    });
});
