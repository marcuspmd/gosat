<?php

declare(strict_types=1);

namespace App\Domain\Integration\Contracts;

use App\Domain\Shared\Dtos\ExternalCreditDto;

interface ExternalCreditMapperInterface
{
    /**
     * Maps external credit data to domain credit offers.
     *
     * @return \App\Domain\Credit\Entities\CreditOfferEntity[]
     */
    public function mapToCreditOffers(ExternalCreditDto $externalDto): array;
}
