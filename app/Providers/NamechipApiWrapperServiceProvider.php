<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Classes\NamecheapWrapper\ApiWrapperFactoryService;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;

class NamechipApiWrapperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ApiWrapperFactoryServiceInterface::class, function ($app) {
            return new ApiWrapperFactoryService(
                env('NAMECHEAP_CLIENT_IP', ''),
                env('NAMECHEAP_SANDBOX')
            );
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
