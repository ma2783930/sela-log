<?php

namespace Sela\Providers;

use Illuminate\Support\ServiceProvider;
use Sela\Console\Commands\GenerateSelaConfig;

class SelaLogServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->commands([
            GenerateSelaConfig::class
        ]);

        $this->mergeConfigFrom(__DIR__ . '/../../config/sela.php', 'config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
            __DIR__ . '/../../config/sela.php'     => config_path('sela.php')
        ], 'sela');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
