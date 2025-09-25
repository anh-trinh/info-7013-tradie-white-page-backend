<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('/tradies', 'TradieController@search');
    $router->get('/tradies/{id}', 'TradieController@getById');
    $router->get('/services', 'CategoryController@getAll');

    // Protected routes requiring a valid user context from API Gateway
    $router->group(['middleware' => 'auth'], function () use ($router) {
        // Update or create the authenticated tradie's profile
        $router->put('/tradies/profile', 'TradieController@updateProfile');
    });

    $router->group(['prefix' => 'admin', 'middleware' => 'admin'], function () use ($router) {
        $router->get('/categories', 'CategoryController@getAll');
        $router->post('/categories', 'CategoryController@create');
        $router->put('/categories/{id}', 'CategoryController@update');
        $router->delete('/categories/{id}', 'CategoryController@delete');
    });
});
