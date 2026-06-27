<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LaravelPaykuServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $basePath = base_path('vendor/sebacarrasco93/laravel-payku');

        $this->loadRoutesFrom($basePath . '/routes/web.php');
        $this->loadViewsFrom($basePath . '/resources/views', 'payku');
        $this->loadTranslationsFrom($basePath . '/resources/lang', 'laravel-payku');

        $this->publishes([
            $basePath . '/config/laravel-payku.php' => base_path('config/laravel-payku.php'),
        ], 'laravel-payku-config');

        $this->publishes([
            $basePath . '/database/migrations' => database_path('migrations'),
        ], 'laravel-payku-migrations');
    }

    public function register(): void
    {
        $this->app->bind('laravel-payku', function () {
            return new \SebaCarrasco93\LaravelPayku\LaravelPayku();
        });

        $this->mergeConfigFrom(
            base_path('vendor/sebacarrasco93/laravel-payku/config/laravel-payku.php'),
            'laravel-payku'
        );
    }
}
