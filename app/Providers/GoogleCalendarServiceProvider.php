<?php

namespace App\Providers;

use Google_Client;
use Illuminate\Support\ServiceProvider;

class GoogleCalendarServiceProvider extends ServiceProvider
{

    private const SETTINGS = [
        // Default highest number, since we'll consolidate later,
        // let's grab everything we can get our hands on!
        'maxResults' => 2500,
        // Do not change this shit, seriously.
        'singleEvents' => true,
    ];

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
        $this->app->singleton(\Google_Service_Calendar::class, function ($app) {
            $client = new Google_Client();
            $client->setAuthConfig($this->getCredentialsPath());
            $client->setScopes([\Google_Service_Calendar::CALENDAR_READONLY]);
            return new \Google_Service_Calendar($client);
        });
    }
}
