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

    // Internal endpoint for service-to-service communication (no auth; internal network only)
    $router->get('/internal/accounts/{id}', 'AccountController@getAccountByIdInternal');

    $router->group(['middleware' => 'auth'], function () use ($router) {
        $router->get('/accounts/me', 'AccountController@me');
        $router->put('/accounts/me', 'AccountController@updateProfile');

        // Internal endpoint for API Gateway to validate token and extract user context
        $router->get('/accounts/validate', function () {
            $user = \Illuminate\Support\Facades\Auth::user();
            return response('Token is valid.', 200)
                ->header('X-User-Id', $user ? $user->id : '')
                ->header('X-User-Role', $user ? ($user->role ?? '') : '');
        });

        $router->group(['prefix' => 'admin', 'middleware' => 'admin'], function () use ($router) {
            $router->get('/accounts', 'AccountController@getAllAccounts');
            $router->get('/accounts/{id}', 'AccountController@getAccountById');
            $router->put('/accounts/{id}/status', 'AccountController@updateAccountStatus');
            $router->delete('/accounts/{id}', 'AccountController@deleteAccount');
        });
    });
});

$router->get('/', function () use ($router) {
    return response()->json(['service' => 'account', 'version' => $router->app->version()]);
});
$router->get('/health', function () {
    return response()->json(['status' => 'healthy']);
});
