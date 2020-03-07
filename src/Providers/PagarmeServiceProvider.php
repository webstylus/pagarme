<?php

namespace Lojazone\Pagarme\Providers;

use Illuminate\Support\ServiceProvider;
use Lojazone\Pagarme\Pagarme;

class PagarmeServiceProvider extends ServiceProvider
{
    /**
     * bootstrap web services
     * listen for events
     * publish configuration files or database migrations
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../views', 'pagarme');
        $this->publishes([
            __DIR__ . '/../config/pagarme.php' => config_path('pagarme.php')
        ], 'lojazone-pagarme');

        $this->mergeConfigFrom(__DIR__ . '/../config/pagarme.php', 'pagarme');
    }

    /**
     * extend funcionality from others classes
     * register service providers
     * create singleton classes
     */
    public function register()
    {
        $this->app->singleton(Pagarme::class, function () {
            return new Pagarme();
        });
    }

}
