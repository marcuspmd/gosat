<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface QueueServiceInterface
{
    public function dispatch(object $job): void;

    public function dispatchAfter(object $job, int $delayInSeconds): void;

    public function dispatchToQueue(object $job, string $queueName): void;
}
