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
    $router->post('quotes', 'BookingController@createQuote');
    $router->get('quotes', 'BookingController@getQuotes');
    $router->post('bookings', 'BookingController@createBooking');
    $router->get('bookings', 'BookingController@getJobs');
    $router->put('bookings/{id}/status', 'BookingController@updateJobStatus');
});
$router->get('/', function () use ($router) {
    return response()->json(['service' => 'booking', 'version' => $router->app->version()]);
});
$router->get('/health', function () use ($router) {
    return response()->json(['status' => 'healthy']);
});
