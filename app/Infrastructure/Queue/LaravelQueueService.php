<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue;

use App\Application\Contracts\QueueServiceInterface;
use Illuminate\Support\Facades\Queue;

final readonly class LaravelQueueService implements QueueServiceInterface
{
    public function dispatch(object $job): void
    {
        Queue::push($job);
    }

    public function dispatchAfter(object $job, int $delayInSeconds): void
    {
        Queue::later($delayInSeconds, $job);
    }

    public function dispatchToQueue(object $job, string $queueName): void
    {
        Queue::pushOn($queueName, $job);
    }
}
