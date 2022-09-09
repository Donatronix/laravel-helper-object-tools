<?php

declare(strict_types=1);

namespace LaravelHelperObjectTools\Providers;

use LaravelHelperObjectTools\Repositories\Interfaces\UserRepository;
use LaravelHelperObjectTools\Repositories\UserRepositoryEloquent;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->bind(UserRepository::class, UserRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Interfaces\AddressRepository::class, \App\Repositories\AddressRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Interfaces\DescriptionRepository::class, \App\Repositories\DescriptionRepositoryEloquent::class);
        //:end-bindings:
    }
}
