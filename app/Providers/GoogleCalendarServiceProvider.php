<?php

namespace App\Providers;

use Google_Client;
use Illuminate\Support\ServiceProvider;
use Google\Service\Calendar;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use GuzzleHttp\Client;
use Stash\Pool;
use League\Flysystem\Adapter\Local as Adapter;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Stash as StashStore;

class GoogleCalendarServiceProvider extends ServiceProvider
{

    /**
     * Returns path to credentials file
     *
     * @return string
     */
    private function getCredentialsPath(): string
    {
        return $this->app->basePath()
            . DIRECTORY_SEPARATOR
            . env("CREDENTIALS_GOOGLE");
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Calendar::class, function ($app) {

            // You can optionally pass a driver (recommended, default: in-memory driver)
            $pool = new Pool();
            // Storage key and expire time are optional
            $cache = new StashStore($pool, 'storageKey', 300);
            $adapter = new CachedAdapter(new Adapter(storage_path()), $cache);
            $filesystem = new Filesystem($adapter);
            $cache = new FilesystemCachePool($filesystem);

            $guzzleClient = app(Client::class);
            $client = new Google_Client();
            $client->setAuthConfig($this->getCredentialsPath());
            $client->setScopes([Calendar::CALENDAR_READONLY]);
            // $client->setCache($cache);
            $client->setHttpClient($guzzleClient);
            return new Calendar($client);
        });
    }
}
