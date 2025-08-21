<?php

declare(strict_types=1);

namespace App\Domain\Shared\Dtos;

use App\Domain\Shared\ValueObjects\CPF;

class ExternalCreditDto
{
    public function __construct(
        public CPF $cpf,
        public string $creditRequestId,
        /**
         * @var ExternalCreditInstitutionDto[]
         */
        public array $institutions = []
    ) {
    }
}

final class ExternalCreditInstitutionDto
{
    public function __construct(
        public string $id = '',
        public string $name = '',
        public string $slug = '',
        /**
         * @var ExternalCreditModalityDto[]
         */
        public array $modalities = []
    ) {
    }
}

final class ExternalCreditModalityDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ExternalCreditOfferDto $offer,
    ) {
    }
}

final class ExternalCreditOfferDto
{
    public function __construct(
        public int $minInstallments = 1,
        public int $maxInstallments = 1,
        public float $interestRate = 0,
        public int $minAmountInCents = 0,
        public int $maxAmountInCents = 0,
    ) {
    }
}
