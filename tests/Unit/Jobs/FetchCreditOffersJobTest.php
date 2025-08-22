<?php

declare(strict_types=1);

use App\Infrastructure\Queue\Jobs\FetchCreditOffersJob;

describe('FetchCreditOffersJob', function () {
    it('sets correct queue name', function () {
        $job = new FetchCreditOffersJob('12345678901', 'req123');

        expect($job->queue)->toBe('credit_offers');
    });

    it('can be instantiated with valid parameters', function () {
        $job = new FetchCreditOffersJob('12345678901', 'req123');

        expect($job)->toBeInstanceOf(FetchCreditOffersJob::class);
    });

    it('implements ShouldQueue interface', function () {
        $job = new FetchCreditOffersJob('12345678901', 'req123');

        expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
    });

    it('has required traits', function () {
        $job = new FetchCreditOffersJob('12345678901', 'req123');
        $reflection = new ReflectionClass($job);
        $traits = $reflection->getTraitNames();

        expect($traits)->toContain('Illuminate\Bus\Queueable')
            ->and($traits)->toContain('Illuminate\Foundation\Bus\Dispatchable')
            ->and($traits)->toContain('Illuminate\Queue\InteractsWithQueue')
            ->and($traits)->toContain('Illuminate\Queue\SerializesModels');
    });
});
