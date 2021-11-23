<?php
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Store;
use Psr\Cache\CacheItemPoolInterface;

class CacheServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CacheItemPoolInterface::class, function ($app) {
            $cacheManager = new CacheManager($app);
            $store = $cacheManager->getStore();
            return $store;
        });
    }
}
