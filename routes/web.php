<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Http\Controllers\FilterController;
use App\Versions\Api;

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
    'events' . api::VersionParam(true) . '[/]',
    ['uses' => 'EventsController@findEvents']
);

$router->get(
    'filters' . api::VersionParam() . '/available[/]',
    ['uses' => (new \ReflectionClass(FilterController::class))->getShortName() . '@fetchFilters']
);

$router->get(
    'filters' . api::VersionParam() . '/count/{category}/{name}[/]',
    ['uses' => (new \ReflectionClass(FilterController::class))->getShortName() . '@getCount']
);

$router->get(
    'filters'. api::VersionParam() . '/total-count[/]',
    ['uses' => (new \ReflectionClass(FilterController::class))->getShortName() . '@getTotalCount']
);
