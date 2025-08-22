<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Contracts\QueueServiceInterface;
use App\Domain\Credit\Repositories\CreditModalityRepositoryInterface;
use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Credit\Repositories\InstitutionRepositoryInterface;
use App\Domain\Customer\Repositories\CustomerRepositoryInterface;
use App\Domain\Integration\Contracts\ExternalCreditApiServiceInterface;
use App\Domain\Integration\Contracts\ExternalCreditMapperInterface;
use App\Domain\Integration\Mappers\ExternalCreditMapper;
use App\Domain\Integration\Services\ExternalCreditApiService;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCreditModalityRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCreditOfferRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentInstitutionRepository;
use App\Infrastructure\Queue\LaravelQueueService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Queue Service
        $this->app->bind(
            QueueServiceInterface::class,
            LaravelQueueService::class
        );

        // Repository bindings
        $this->app->bind(
            CreditOfferRepositoryInterface::class,
            EloquentCreditOfferRepository::class
        );

        $this->app->bind(
            InstitutionRepositoryInterface::class,
            EloquentInstitutionRepository::class
        );

        $this->app->bind(
            CreditModalityRepositoryInterface::class,
            EloquentCreditModalityRepository::class
        );

        $this->app->bind(
            CustomerRepositoryInterface::class,
            EloquentCustomerRepository::class
        );

        // Integration Services
        $this->app->bind(
            ExternalCreditApiServiceInterface::class,
            function ($app) {
                return new ExternalCreditApiService(
                    new Client
                );
            }
        );

        $this->app->bind(
            ExternalCreditMapperInterface::class,
            ExternalCreditMapper::class
        );
    }

    public function boot(): void {}
}
