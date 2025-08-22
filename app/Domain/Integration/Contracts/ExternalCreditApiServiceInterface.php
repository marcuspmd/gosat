<?php

declare(strict_types=1);

namespace App\Domain\Integration\Contracts;

use App\Domain\Shared\Dtos\ExternalCreditDto;

interface ExternalCreditApiServiceInterface
{
    /**
     * Fetch credit data from external API.
     */
    public function fetchCredit(ExternalCreditDto $dto): ExternalCreditDto;
}
