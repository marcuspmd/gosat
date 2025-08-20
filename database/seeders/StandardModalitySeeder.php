<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class StandardModalitySeeder extends Seeder
{
    public function run(): void
    {
        $modalities = [
            [
                'code' => 'PERSONAL_CREDIT',
                'name' => 'Crédito Pessoal',
                'description' => 'Empréstimo pessoal sem garantia específica',
                'risk_level' => 'high',
                'typical_interest_range' => json_encode(['min' => 0.02, 'max' => 0.15]),
                'keywords' => json_encode(['pessoal', 'personal', 'emprestimo', 'loan']),
            ],
            [
                'code' => 'PAYROLL_CREDIT',
                'name' => 'Crédito Consignado',
                'description' => 'Empréstimo com desconto em folha de pagamento',
                'risk_level' => 'low',
                'typical_interest_range' => json_encode(['min' => 0.01, 'max' => 0.03]),
                'keywords' => json_encode(['consignado', 'payroll', 'folha', 'desconto']),
            ],
            [
                'code' => 'VEHICLE_FINANCING',
                'name' => 'Financiamento de Veículos',
                'description' => 'Financiamento para compra de veículos',
                'risk_level' => 'medium',
                'typical_interest_range' => json_encode(['min' => 0.008, 'max' => 0.025]),
                'keywords' => json_encode(['veiculo', 'vehicle', 'auto', 'carro', 'moto', 'financiamento']),
            ],
            [
                'code' => 'REAL_ESTATE_FINANCING',
                'name' => 'Financiamento Imobiliário',
                'description' => 'Financiamento para compra de imóveis',
                'risk_level' => 'medium',
                'typical_interest_range' => json_encode(['min' => 0.006, 'max' => 0.015]),
                'keywords' => json_encode(['imobiliario', 'real estate', 'casa', 'imovel', 'habitacao']),
            ],
            [
                'code' => 'CREDIT_CARD',
                'name' => 'Cartão de Crédito',
                'description' => 'Limite de crédito em cartão',
                'risk_level' => 'high',
                'typical_interest_range' => json_encode(['min' => 0.08, 'max' => 0.20]),
                'keywords' => json_encode(['cartao', 'card', 'credito']),
            ],
            [
                'code' => 'OVERDRAFT',
                'name' => 'Cheque Especial',
                'description' => 'Limite para conta corrente',
                'risk_level' => 'high',
                'typical_interest_range' => json_encode(['min' => 0.10, 'max' => 0.25]),
                'keywords' => json_encode(['especial', 'overdraft', 'cheque', 'limite']),
            ],
            [
                'code' => 'REVOLVING_CREDIT',
                'name' => 'Crédito Rotativo',
                'description' => 'Crédito pré-aprovado renovável',
                'risk_level' => 'high',
                'typical_interest_range' => json_encode(['min' => 0.05, 'max' => 0.18]),
                'keywords' => json_encode(['rotativo', 'revolving', 'renovavel', 'pre-aprovado']),
            ],
        ];

        foreach ($modalities as $modality) {
            DB::table('standard_modalities')->insert([
                'id' => Uuid::uuid4()->toString(),
                'code' => $modality['code'],
                'name' => $modality['name'],
                'description' => $modality['description'],
                'risk_level' => $modality['risk_level'],
                'typical_interest_range' => $modality['typical_interest_range'],
                'keywords' => $modality['keywords'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Standard modalities seeded successfully!');
    }
}
