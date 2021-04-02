<?php

namespace Tanjiu\Http\Providers;

use Illuminate\Support\ServiceProvider;
use Tanjiu\Http\Client;

class ClientServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('client', function ($app) {
            return new Client($app);
        });
    }
}
