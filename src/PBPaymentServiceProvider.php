<?php

namespace Salamzadeh\PBPayment;

use Illuminate\Support\ServiceProvider;

class PBPaymentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pbpayment.php', 'pbpayment');
        $this->publishes([
            __DIR__.'/../config/pbpayment.php' => config_path('pbpayment.php'),
        ], 'config');

        if ($this->isLumen()) {
            $this->app->configure('pbpayment');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([
            __DIR__.'/../database/migrations' => $this->app->databasePath().'/migrations',
        ], 'migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pbpayment');
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/pbpayment'),
        ], 'views');

        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/pbpayment'),
        ], 'assets');

        if (app('config')->get('app.env', 'production') !== 'production') {
            $this->loadRoutesFrom(__DIR__.'/Gateways/Test/routes.php');
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Check if app uses Lumen.
     *
     * @return bool
     */
    private function isLumen(): bool
    {
        return str_contains($this->app->version(), 'Lumen');
    }
}
