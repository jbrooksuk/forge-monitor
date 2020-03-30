<?php

namespace App\Providers;

use App\Monitors\ConfigFileFinder;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Finder\Finder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->app->singleton(ConfigFileFinder::class, function ($app) {
            $finder = new Finder();

            return (new ConfigFileFinder($finder))
                ->addDirectory(home_data_path())
                ->addDirectory(realpath(__DIR__.'/../../'));
        });

        $this->app->singleton(MonitorConfig::class, function ($app) {
            return new MonitorConfig();
        });
    }
}
