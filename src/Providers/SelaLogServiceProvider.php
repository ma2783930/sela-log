<?php

namespace Sela\Providers;

use Illuminate\Support\ServiceProvider;
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
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/sela.php', 'sela'
        );

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'sela-log');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
