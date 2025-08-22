<?php

declare(strict_types=1);

use App\Domain\Shared\Enums\CreditOfferStatus;

describe('CreditOfferStatus', function () {
    it('has correct values', function () {
        expect(CreditOfferStatus::ACTIVE->value)->toBe('active')
            ->and(CreditOfferStatus::INACTIVE->value)->toBe('inactive')
            ->and(CreditOfferStatus::ERROR->value)->toBe('error');
    });

    it('returns correct labels', function () {
        expect(CreditOfferStatus::ACTIVE->label())->toBe('Ativa')
            ->and(CreditOfferStatus::INACTIVE->label())->toBe('Inativa')
            ->and(CreditOfferStatus::ERROR->label())->toBe('Erro');
    });

    it('checks active status correctly', function () {
        expect(CreditOfferStatus::ACTIVE->isActive())->toBeTrue()
            ->and(CreditOfferStatus::INACTIVE->isActive())->toBeFalse()
            ->and(CreditOfferStatus::ERROR->isActive())->toBeFalse();
    });

    it('checks error status correctly', function () {
        expect(CreditOfferStatus::ERROR->hasError())->toBeTrue()
            ->and(CreditOfferStatus::ACTIVE->hasError())->toBeFalse()
            ->and(CreditOfferStatus::INACTIVE->hasError())->toBeFalse();
    });

    it('can be used in match expressions', function () {
        $statuses = [
            CreditOfferStatus::ACTIVE,
            CreditOfferStatus::INACTIVE,
            CreditOfferStatus::ERROR,
        ];

        foreach ($statuses as $status) {
            $result = match ($status) {
                CreditOfferStatus::ACTIVE => 'active',
                CreditOfferStatus::INACTIVE => 'inactive',
                CreditOfferStatus::ERROR => 'error',
            };

            expect($result)->toBe($status->value);
        }
    });

    it('can be compared with other enum values', function () {
        $activeStatus = CreditOfferStatus::ACTIVE;
        $inactiveStatus = CreditOfferStatus::INACTIVE;

        expect($activeStatus)->toBe(CreditOfferStatus::ACTIVE)
            ->and($activeStatus)->not->toBe($inactiveStatus)
            ->and($inactiveStatus)->toBe(CreditOfferStatus::INACTIVE);
    });

    it('can get all enum cases', function () {
        $cases = CreditOfferStatus::cases();

        expect($cases)->toHaveCount(3)
            ->and($cases[0])->toBe(CreditOfferStatus::ACTIVE)
            ->and($cases[1])->toBe(CreditOfferStatus::INACTIVE)
            ->and($cases[2])->toBe(CreditOfferStatus::ERROR);
    });
});
