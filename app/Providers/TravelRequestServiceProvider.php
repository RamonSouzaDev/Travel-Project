<?php

namespace App\Providers;

use App\Repositories\Interfaces\TravelRequestRepositoryInterface;
use App\Repositories\TravelRequestRepository;
use App\Services\Interfaces\TravelRequestServiceInterface;
use App\Services\TestTravelRequestService;
use App\Services\TravelRequestService;
use Illuminate\Support\ServiceProvider;

class TravelRequestServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind para o repositório
        $this->app->bind(TravelRequestRepositoryInterface::class, TravelRequestRepository::class);
        
        // Bind para o serviço, baseado no ambiente
        if ($this->app->environment('testing')) {
            $this->app->bind(TravelRequestServiceInterface::class, TestTravelRequestService::class);
        } else {
            $this->app->bind(TravelRequestServiceInterface::class, TravelRequestService::class);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}