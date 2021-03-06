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

$router->get(
    'search/{search}[/{limit:[0-9]+}]',
    ['uses' => 'SearchController@runTextSearch']
);

$router->get(
    'events',
    ['uses' => 'EventsController@ShowEvents']
);

$router->get(
    'events/byMonth/{date:\d{4}_\d{1,2}}',
    ['uses' => 'EventsController@EventsByMonth']
);

$router->get(
    'filters',
    ['uses' => 'EventsController@ReturnFilters']
);
