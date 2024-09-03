<?php

namespace App\Providers;

use App\Classes\Countries\Contracts\CountriesInterface;
use App\Classes\Countries\Countries;
use Illuminate\Support\ServiceProvider;

class CountriesProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CountriesInterface::class, function ($app) {
            return new Countries();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
