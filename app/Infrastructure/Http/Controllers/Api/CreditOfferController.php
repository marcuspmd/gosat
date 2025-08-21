<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Services\CreditOfferApplicationService;
use App\Infrastructure\Http\Resources\CreditRequestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreditOfferController extends Controller
{
    public function __construct(
        private readonly CreditOfferApplicationService $applicationService
    ) {
    }

    public function creditRequest(Request $request): JsonResponse
    {
        $request->validate([
            'cpf' => 'required|string|regex:/^\d{11}$/',
        ]);

        try {
            $result = $this->applicationService->processCreditsRequest($request->cpf);

            return response()->json(
                new CreditRequestResource($result),
                202
            );

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => 'validation_error',
                'message' => $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'internal_error',
                'message' => 'Erro interno do servidor',
            ], 500);
        }
    }

    public function getAllCustomersWithOffers(): JsonResponse
    {
        try {
            // Buscar ofertas agrupadas por CPF, instituição e modalidade
            $customers = DB::table('customers')
                ->join('credit_offers', 'customers.id', '=', 'credit_offers.customer_id')
                ->join('institutions', 'credit_offers.institution_id', '=', 'institutions.id')
                ->join('credit_modalities', 'credit_offers.modality_id', '=', 'credit_modalities.id')
                ->select(
                    'customers.cpf',
                    'institutions.name as institution_name',
                    'credit_modalities.name as modality_name',
                    DB::raw('MAX(credit_offers.max_amount_cents) as max_amount_cents'),
                    DB::raw('MIN(credit_offers.min_amount_cents) as min_amount_cents'),
                    DB::raw('MAX(credit_offers.max_installments) as max_installments'),
                    DB::raw('MIN(credit_offers.min_installments) as min_installments'),
                    DB::raw('MIN(credit_offers.monthly_interest_rate) as monthly_interest_rate'),
                    DB::raw('MAX(credit_offers.created_at) as created_at')
                )
                ->where('customers.is_active', true)
                ->groupBy('customers.cpf', 'institutions.name', 'credit_modalities.name')
                ->orderBy('customers.cpf')
                ->orderBy('created_at', 'desc')
                ->get();

            $groupedCustomers = $customers->groupBy('cpf')->map(function ($offers, $cpf) {
                // Calcular ranges disponíveis para este CPF
                $minAmount = $offers->min('min_amount_cents');
                $maxAmount = $offers->max('max_amount_cents');
                $minInstallments = $offers->min('min_installments');
                $maxInstallments = $offers->max('max_installments');

                return [
                    'cpf' => $cpf,
                    'offers_count' => $offers->count(),
                    'offers' => $offers->toArray(),
                    'available_ranges' => [
                        'min_amount_cents' => $minAmount,
                        'max_amount_cents' => $maxAmount,
                        'min_installments' => $minInstallments,
                        'max_installments' => $maxInstallments
                    ]
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'data' => $groupedCustomers
            ]);

        } catch (\Exception) {
            return response()->json([
                'error' => 'internal_error',
                'message' => 'Erro ao buscar customers e ofertas',
            ], 500);
        }
    }

    public function simulateCredit(Request $request): JsonResponse
    {
        $request->validate([
            'cpf' => 'required|string|regex:/^\d{11}$/',
            'valor_desejado' => 'required|integer|min:100', // em centavos
            'quantidade_parcelas' => 'required|integer|min:1'
        ]);

        try {
            $cpf = $request->input('cpf');
            $valorDesejadoCentavos = $request->input('valor_desejado');
            $quantidadeParcelas = $request->input('quantidade_parcelas');

            // Buscar ofertas disponíveis para o CPF, agrupadas por instituição e modalidade
            $ofertas = DB::table('customers')
                ->join('credit_offers', 'customers.id', '=', 'credit_offers.customer_id')
                ->join('institutions', 'credit_offers.institution_id', '=', 'institutions.id')
                ->join('credit_modalities', 'credit_offers.modality_id', '=', 'credit_modalities.id')
                ->select(
                    'institutions.name as instituicao_financeira',
                    'credit_modalities.name as modalidade_credito',
                    DB::raw('MAX(credit_offers.max_amount_cents) as max_amount_cents'),
                    DB::raw('MIN(credit_offers.min_amount_cents) as min_amount_cents'),
                    DB::raw('MAX(credit_offers.max_installments) as max_installments'),
                    DB::raw('MIN(credit_offers.min_installments) as min_installments'),
                    DB::raw('MIN(credit_offers.monthly_interest_rate) as monthly_interest_rate')
                )
                ->where('customers.cpf', $cpf)
                ->where('customers.is_active', true)
                ->where('credit_offers.min_amount_cents', '<=', $valorDesejadoCentavos)
                ->where('credit_offers.max_amount_cents', '>=', $valorDesejadoCentavos)
                ->where('credit_offers.min_installments', '<=', $quantidadeParcelas)
                ->where('credit_offers.max_installments', '>=', $quantidadeParcelas)
                ->groupBy('institutions.name', 'credit_modalities.name')
                ->get();

            if ($ofertas->isEmpty()) {
                return response()->json([
                    'error' => 'no_offers',
                    'message' => 'Nenhuma oferta disponível para os parâmetros informados'
                ], 404);
            }

            // Calcular simulações para cada oferta
            $simulacoes = [];
            foreach ($ofertas as $oferta) {
                $taxaMensal = floatval($oferta->monthly_interest_rate);
                $valorSolicitado = $valorDesejadoCentavos;
                $numParcelas = $quantidadeParcelas;

                // Calcular parcela usando fórmula de juros compostos
                $parcelaMensal = $this->calcularParcela($valorSolicitado, $taxaMensal, $numParcelas);
                $valorTotal = $parcelaMensal * $numParcelas;
                $totalJuros = $valorTotal - $valorSolicitado;

                // Calcular taxa anual: taxa mensal × 12
                $taxaAnual = $taxaMensal * 12;

                $simulacoes[] = [
                    'instituicaoFinanceira' => $oferta->instituicao_financeira,
                    'modalidadeCredito' => $oferta->modalidade_credito,
                    'valorSolicitado' => $valorSolicitado,
                    'valorAPagar' => $valorTotal,
                    'taxaJurosMensal' => $taxaMensal,
                    'taxaJurosAnual' => $taxaAnual,
                    'qntParcelas' => $numParcelas,
                    'parcelaMensal' => $parcelaMensal,
                    'totalJuros' => $totalJuros,
                    // Limites disponíveis para esta modalidade
                    'limites' => [
                        'valorMinimo' => $oferta->min_amount_cents,
                        'valorMaximo' => $oferta->max_amount_cents,
                        'parcelasMinima' => $oferta->min_installments,
                        'parcelasMaxima' => $oferta->max_installments
                    ],
                    'taxaJuros' => $taxaMensal
                ];
            }

            // Ordenar por valor total a pagar (menor = mais vantajoso)
            usort($simulacoes, function ($a, $b) {
                return $a['valorAPagar'] <=> $b['valorAPagar'];
            });

            // Retornar até 3 melhores ofertas
            $melhoresOfertas = array_slice($simulacoes, 0, 3);

            return response()->json([
                'status' => 'success',
                'cpf' => $cpf,
                'parametros' => [
                    'valor_desejado' => $valorDesejadoCentavos,
                    'quantidade_parcelas' => $quantidadeParcelas
                ],
                'ofertas' => $melhoresOfertas,
                'total_ofertas_encontradas' => count($simulacoes)
            ]);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => 'validation_error',
                'message' => $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'internal_error',
                'message' => 'Erro interno do servidor',
            ], 500);
        }
    }

    private function calcularParcela(int $valorCentavos, float $taxaMensal, int $numParcelas): int
    {
        if ($taxaMensal == 0) {
            // Se taxa é zero, é divisão simples (sem juros)
            return intval($valorCentavos / $numParcelas);
        }

        // Fórmula de JUROS COMPOSTOS para pagamento (PMT)
        // PMT = PV * [i * (1+i)^n] / [(1+i)^n - 1]
        // Onde:
        // PV = Valor Presente (valor do empréstimo)
        // i = taxa de juros por período (mensal)
        // n = número de períodos (parcelas)

        $fator = pow(1 + $taxaMensal, $numParcelas); // (1+i)^n
        $parcela = $valorCentavos * ($taxaMensal * $fator) / ($fator - 1);

        return intval(round($parcela));
    }

    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'credit-offer-api',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
