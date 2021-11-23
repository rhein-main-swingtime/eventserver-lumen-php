<?php
declare(strict_types=1);

namespace App\Providers;

use HtmlSanitizer\Sanitizer;
use HtmlSanitizer\SanitizerInterface;
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
        $this->app->singleton(SanitizerInterface::class, function ($app) {
            return Sanitizer::create(
                [
                    'extensions' => ['basic', 'list', 'table'],
                ]
            );
        });
    }
}
