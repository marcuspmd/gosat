<?php

declare(strict_types=1);

namespace App\Domain\Credit\ValueObjects;

enum StandardModalityCode: string
{
    case PERSONAL_CREDIT = 'PERSONAL_CREDIT';
    case PAYROLL_CREDIT = 'PAYROLL_CREDIT';
    case VEHICLE_FINANCING = 'VEHICLE_FINANCING';
    case REAL_ESTATE_FINANCING = 'REAL_ESTATE_FINANCING';
    case CREDIT_CARD = 'CREDIT_CARD';
    case OVERDRAFT = 'OVERDRAFT';
    case REVOLVING_CREDIT = 'REVOLVING_CREDIT';

    public function label(): string
    {
        return match ($this) {
            self::PERSONAL_CREDIT => 'Crédito Pessoal',
            self::PAYROLL_CREDIT => 'Crédito Consignado',
            self::VEHICLE_FINANCING => 'Financiamento de Veículos',
            self::REAL_ESTATE_FINANCING => 'Financiamento Imobiliário',
            self::CREDIT_CARD => 'Cartão de Crédito',
            self::OVERDRAFT => 'Cheque Especial',
            self::REVOLVING_CREDIT => 'Crédito Rotativo',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PERSONAL_CREDIT => 'Empréstimo pessoal sem garantia específica',
            self::PAYROLL_CREDIT => 'Empréstimo com desconto em folha de pagamento',
            self::VEHICLE_FINANCING => 'Financiamento para compra de veículos',
            self::REAL_ESTATE_FINANCING => 'Financiamento para compra de imóveis',
            self::CREDIT_CARD => 'Limite de crédito em cartão',
            self::OVERDRAFT => 'Limite para conta corrente',
            self::REVOLVING_CREDIT => 'Crédito pré-aprovado renovável',
        };
    }

    public function riskLevel(): string
    {
        return match ($this) {
            self::PERSONAL_CREDIT, self::CREDIT_CARD, self::OVERDRAFT, self::REVOLVING_CREDIT => 'high',
            self::PAYROLL_CREDIT => 'low',
            self::VEHICLE_FINANCING, self::REAL_ESTATE_FINANCING => 'medium',
        };
    }

    public function typicalInterestRange(): array
    {
        return match ($this) {
            self::PERSONAL_CREDIT => ['min' => 0.02, 'max' => 0.15], // 2% a 15% a.m.
            self::PAYROLL_CREDIT => ['min' => 0.01, 'max' => 0.03], // 1% a 3% a.m.
            self::VEHICLE_FINANCING => ['min' => 0.008, 'max' => 0.025], // 0.8% a 2.5% a.m.
            self::REAL_ESTATE_FINANCING => ['min' => 0.006, 'max' => 0.015], // 0.6% a 1.5% a.m.
            self::CREDIT_CARD => ['min' => 0.08, 'max' => 0.20], // 8% a 20% a.m.
            self::OVERDRAFT => ['min' => 0.10, 'max' => 0.25], // 10% a 25% a.m.
            self::REVOLVING_CREDIT => ['min' => 0.05, 'max' => 0.18], // 5% a 18% a.m.
        };
    }
}
