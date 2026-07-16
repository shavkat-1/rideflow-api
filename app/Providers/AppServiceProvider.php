<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\TripRepositoryInterface;
use App\Repositories\TripRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            TripRepositoryInterface::class, 
            TripRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
