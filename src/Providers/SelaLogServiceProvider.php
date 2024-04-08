<?php

namespace Sela\Providers;

use Illuminate\Support\ServiceProvider;
use Sela\Console\Commands\GenerateSelaConfig;
use Sela\Helpers\SelaHelper;

class SelaLogServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind('sela', fn () => new SelaHelper);

        $this->commands([GenerateSelaConfig::class]);
        $this->mergeConfigFrom(__DIR__ . '/../../config/sela.php', 'sela');
        $this->publishes([
            __DIR__ . '/../../config/sela.php' => config_path('sela.php')
        ], 'sela');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $useStorage = config('sela.use_storage');
        $path = config('sela.path');

        app()->config['filesystems.disks.sela'] = [
            'driver' => 'local',
            'root' => $useStorage ? storage_path($path) : $path
        ];
    }
}
