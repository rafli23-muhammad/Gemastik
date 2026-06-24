<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $databasePath = config('database.connections.sqlite.database');

        if (!is_string($databasePath) || $databasePath === '' || $databasePath === ':memory:') {
            return;
        }

        $directory = dirname($databasePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (!file_exists($databasePath)) {
            touch($databasePath);
        }
    }
}
