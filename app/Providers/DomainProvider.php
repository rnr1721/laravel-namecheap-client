<?php

namespace App\Providers;

use App\Classes\Application\DomainService;
use App\Classes\Application\Contracts\DomainServiceInterface;
use App\Classes\Application\Contracts\PhoneUtilsInterface;
use App\Classes\Application\PhoneUtils;
use App\Classes\Countries\Contracts\CountriesInterface;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;
use Illuminate\Support\ServiceProvider;

class DomainProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(DomainServiceInterface::class, function ($app) {
            return new DomainService(
                $app->get(ApiWrapperFactoryServiceInterface::class),
                $app->get(PhoneUtilsInterface::class),
                $app->get(CountriesInterface::class)
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
