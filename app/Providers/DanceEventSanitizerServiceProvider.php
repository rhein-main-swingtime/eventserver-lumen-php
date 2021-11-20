<?php
declare(strict_types=1);

namespace App\Providers;

use App\HtmlSanitizer\DanceEventSanitizer;
use App\HtmlSanitizer\DanceEventSanitizerInterface;
use Illuminate\Support\ServiceProvider;

class DanceEventSanitizerServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(DanceEventSanitizerInterface::class, function ($app) {
            return DanceEventSanitizer::create(
                [
                    'extensions' => ['basic', 'list', 'table'],
                ]
            );
        });
    }
}
