<?php

namespace App\Providers;

use App\Repositories\Interfaces\TravelRequestRepositoryInterface;
use App\Repositories\TravelRequestRepository;
use Illuminate\Support\ServiceProvider;

class TravelRequestServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(TravelRequestRepositoryInterface::class, TravelRequestRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}