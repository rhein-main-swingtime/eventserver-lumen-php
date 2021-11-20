<?php
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use League\Flysystem\Adapter\Local;
use Kevinrob\GuzzleCache\Strategy\PublicCacheStrategy;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Kevinrob\GuzzleCache\Storage\FlysystemStorage;

class GuzzleServiceProvider extends ServiceProvider
{

    /**
    * Register any application services.
    *
    * @return void
    */
    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            // Create default HandlerStack
            $stack = HandlerStack::create();
            // Add this middleware to the top with `push`
            $stack->push(
                new CacheMiddleware(
                    new GreedyCacheStrategy(
                        new FlysystemStorage(
                            new Local(storage_path().'/cache/guzzle')
                        ),
                        20 * 60 // 20 minutes
                    )
                ),
                'cache'
            );
            // Initialize the client with the handler option
            $client = new Client(['handler' => $stack]);
            return $client;
        });
    }
}
