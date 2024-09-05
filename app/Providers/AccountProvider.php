<?php

namespace App\Providers;

use App\Classes\Application\AccountService;
use App\Classes\Application\Contracts\AccountServiceInterface;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;
use Illuminate\Support\ServiceProvider;

class AccountProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AccountServiceInterface::class, function ($app) {
            return new AccountService($app->get(ApiWrapperFactoryServiceInterface::class));
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
