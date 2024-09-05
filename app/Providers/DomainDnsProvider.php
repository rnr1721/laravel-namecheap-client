<?php

namespace App\Providers;

use App\Classes\Application\Contracts\DomainDnsServiceInterface;
use App\Classes\Application\Contracts\PhoneUtilsInterface;
use App\Classes\Application\DomainDnsService;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;
use Illuminate\Support\ServiceProvider;

class DomainDnsProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(DomainDnsServiceInterface::class, function ($app) {
            return new DomainDnsService(
                $app->get(ApiWrapperFactoryServiceInterface::class),
                $app->get(PhoneUtilsInterface::class)
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
