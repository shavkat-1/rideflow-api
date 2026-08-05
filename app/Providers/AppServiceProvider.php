<?php

namespace App\Providers;

use App\Contracts\TripEventPublisherInterface;
use App\Contracts\TripRepositoryInterface;
use App\Repositories\TripRepository;
use App\Services\KafkaTripEventPublisher;
use Illuminate\Support\ServiceProvider;

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

        $this->app->bind(
            RequestCounter::class);

        $this->app->bind(
            TripEventPublisherInterface::class,
            KafkaTripEventPublisher::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
