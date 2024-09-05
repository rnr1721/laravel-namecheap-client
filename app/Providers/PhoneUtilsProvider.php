<?php

namespace App\Providers;

use App\Classes\Application\Contracts\PhoneUtilsInterface;
use App\Classes\Application\PhoneUtils;
use Illuminate\Support\ServiceProvider;

class PhoneUtilsProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PhoneUtilsInterface::class, function ($app) {
            return new PhoneUtils();
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
