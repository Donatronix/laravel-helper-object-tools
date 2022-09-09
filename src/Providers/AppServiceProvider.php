<?php

namespace LaravelHelperObjectTools\Providers;

use LaravelHelperObjectTools\Services\BaseService;
use LaravelHelperObjectTools\Services\Interfaces\BaseServiceInterface;
use LaravelHelperObjectTools\Services\Interfaces\UserServiceInterface;
use LaravelHelperObjectTools\Services\UserService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected array $services = [
        BaseServiceInterface::class => BaseService::class,
        UserServiceInterface::class => UserService::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        foreach ($this->services as $interface => $service) {
            $this->app->bind($interface, $service);
        }
    }
}
