<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Services\CreditOfferApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreditOfferController extends Controller
{
    public function __construct(
        private readonly CreditOfferApplicationService $applicationService
    ) {}

    public function creditRequest(Request $request): JsonResponse
    {
        $request->validate([
            'cpf' => 'required|string|regex:/^\d{11}$/',
        ]);

        try {
            $requestId = $this->applicationService->processCreditsRequest($request->cpf);

            return response()->json([
                'request_id' => $requestId,
                'status' => 'processing',
                'message' => 'Request in progress. Use the request_id to check status.',
            ], 202);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => 'validation_error',
                'message' => $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'internal_error',
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function getAllCustomersWithOffers(): JsonResponse
    {
        try {
            // Buscar ofertas agrupadas por CPF, instituição e modalidade (excludindo soft deleted)
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
                ->whereNull('credit_offers.deleted_at')
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
                        'max_installments' => $maxInstallments,
                    ],
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'data' => $groupedCustomers,
            ]);

        } catch (\Exception) {
            return response()->json([
                'error' => 'internal_error',
                'message' => 'Error fetching customers and offers',
            ], 500);
        }
    }

    public function getRequestStatus(string $requestId): JsonResponse
    {
        try {
            // Validate UUID format
            if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $requestId)) {
                return response()->json([
                    'error' => 'invalid_request_id',
                    'message' => 'Invalid request ID',
                ], 400);
            }

            // Check if there are any jobs running for this request_id
            $pendingJob = DB::table('jobs')
                ->where('payload', 'like', '%"creditRequestId":"' . $requestId . '"%')
                ->first();

            if ($pendingJob) {
                return response()->json([
                    'request_id' => $requestId,
                    'status' => 'processing',
                    'message' => 'Request still processing',
                    'created_at' => date('c', $pendingJob->created_at),
                    'attempts' => $pendingJob->attempts,
                ]);
            }

            // Check if there are any failed jobs for this request_id
            $failedJob = DB::table('failed_jobs')
                ->where('payload', 'like', '%"creditRequestId":"' . $requestId . '"%')
                ->first();

            if ($failedJob) {
                return response()->json([
                    'request_id' => $requestId,
                    'status' => 'failed',
                    'message' => 'Request failed - processing error',
                    'failed_at' => $failedJob->failed_at,
                    'error' => 'Internal error during processing',
                ]);
            }

            // Check if we have offers that were created with this request_id
            $offersCount = DB::table('credit_offers')
                ->where('request_id', $requestId)
                ->whereNull('deleted_at')
                ->count();

            if ($offersCount > 0) {
                return response()->json([
                    'request_id' => $requestId,
                    'status' => 'completed',
                    'message' => 'Request completed successfully',
                    'offers_found' => $offersCount,
                ]);
            }

            // If no job found and no offers, the request was probably completed but with no results
            // Or it's an invalid request_id
            return response()->json([
                'request_id' => $requestId,
                'status' => 'completed',
                'message' => 'Request completed - no offers found',
                'offers_found' => 0,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'internal_error',
                'message' => 'Error checking request status',
            ], 500);
        }
    }

    public function getCreditOffers(Request $request): JsonResponse
    {
        $request->validate([
            'cpf' => 'required|string|regex:/^\d{11}$/',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $cpf = $request->query('cpf');
            $limit = $request->query('limit', 10);

            // Buscar ofertas para o CPF específico
            $query = DB::table('customers')
                ->join('credit_offers', 'customers.id', '=', 'credit_offers.customer_id')
                ->join('institutions', 'credit_offers.institution_id', '=', 'institutions.id')
                ->join('credit_modalities', 'credit_offers.modality_id', '=', 'credit_modalities.id')
                ->select(
                    'institutions.name as institution_name',
                    'credit_modalities.name as modality_name',
                    'credit_offers.max_amount_cents',
                    'credit_offers.min_amount_cents',
                    'credit_offers.max_installments',
                    'credit_offers.min_installments',
                    'credit_offers.monthly_interest_rate',
                    'credit_offers.created_at'
                )
                ->where('customers.cpf', $cpf)
                ->where('customers.is_active', true)
                ->whereNull('credit_offers.deleted_at')
                ->orderBy('credit_offers.created_at', 'desc')
                ->limit($limit);

            $offers = $query->get();

            return response()->json([
                'cpf' => $cpf,
                'offers' => $offers->toArray(),
                'total_offers' => $offers->count(),
                'limit' => $limit,
            ]);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => 'validation_error',
                'message' => $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'internal_error',
                'message' => 'Error fetching offers',
            ], 500);
        }
    }

    public function simulateCredit(Request $request): JsonResponse
    {
        $request->validate([
            'cpf' => 'required|string|regex:/^\d{11}$/',
            'amount' => 'required|integer|min:100', // in cents
            'installments' => 'required|integer|min:1',
            'modality' => 'nullable|string',
        ]);

        try {
            $cpf = $request->input('cpf');
            $amountCents = $request->input('amount');
            $installments = $request->input('installments');
            $modality = $request->input('modality');

            // Find available offers for the CPF, grouped by institution and modality (excluding soft deleted)
            $offers = DB::table('customers')
                ->join('credit_offers', 'customers.id', '=', 'credit_offers.customer_id')
                ->join('institutions', 'credit_offers.institution_id', '=', 'institutions.id')
                ->join('credit_modalities', 'credit_offers.modality_id', '=', 'credit_modalities.id')
                ->select(
                    'institutions.name as financial_institution',
                    'credit_modalities.name as credit_modality',
                    DB::raw('MAX(credit_offers.max_amount_cents) as max_amount_cents'),
                    DB::raw('MIN(credit_offers.min_amount_cents) as min_amount_cents'),
                    DB::raw('MAX(credit_offers.max_installments) as max_installments'),
                    DB::raw('MIN(credit_offers.min_installments) as min_installments'),
                    DB::raw('MIN(credit_offers.monthly_interest_rate) as monthly_interest_rate')
                )
                ->where('customers.cpf', $cpf)
                ->where('customers.is_active', true)
                ->whereNull('credit_offers.deleted_at')
                ->where('credit_offers.min_amount_cents', '<=', $amountCents)
                ->where('credit_offers.max_amount_cents', '>=', $amountCents)
                ->where('credit_offers.min_installments', '<=', $installments)
                ->where('credit_offers.max_installments', '>=', $installments);

            // Add modality filter if specified
            if ($modality) {
                $offers->where('credit_modalities.name', $modality);
            }

            $offers = $offers->groupBy('institutions.name', 'credit_modalities.name')
                ->get();

            if ($offers->isEmpty()) {
                return response()->json([
                    'error' => 'no_offers',
                    'message' => 'No offers available for the provided parameters',
                ], 404);
            }

            // Calculate simulations for each offer
            $simulations = [];
            foreach ($offers as $offer) {
                $monthlyRate = floatval($offer->monthly_interest_rate);
                $requestedAmount = $amountCents;
                $numInstallments = $installments;

                // Calculate installment using compound interest formula
                $monthlyPayment = $this->calculateInstallment($requestedAmount, $monthlyRate, $numInstallments);
                $totalAmount = $monthlyPayment * $numInstallments;
                $totalInterest = $totalAmount - $requestedAmount;

                // Calculate annual rate: monthly rate × 12
                $annualRate = $monthlyRate * 12;

                $simulations[] = [
                    'financial_institution' => $offer->financial_institution,
                    'credit_modality' => $offer->credit_modality,
                    'requested_amount' => $requestedAmount,
                    'total_amount' => $totalAmount,
                    'monthly_interest_rate' => $monthlyRate,
                    'annual_interest_rate' => $annualRate,
                    'installments' => $numInstallments,
                    'monthly_payment' => $monthlyPayment,
                    'total_interest' => $totalInterest,
                    // Available limits for this modality
                    'limits' => [
                        'min_amount' => $offer->min_amount_cents,
                        'max_amount' => $offer->max_amount_cents,
                        'min_installments' => $offer->min_installments,
                        'max_installments' => $offer->max_installments,
                    ],
                ];
            }

            // Sort by total amount to pay (lower = better)
            usort($simulations, function ($a, $b) {
                return $a['total_amount'] <=> $b['total_amount'];
            });

            // Return up to 3 best offers
            $bestOffers = array_slice($simulations, 0, 3);

            return response()->json([
                'status' => 'success',
                'cpf' => $cpf,
                'parameters' => [
                    'amount' => $amountCents,
                    'installments' => $installments,
                ],
                'offers' => $bestOffers,
                'total_offers_found' => count($simulations),
            ]);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => 'validation_error',
                'message' => $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'internal_error',
                'message' => 'Internal server error',
            ], 500);
        }
    }

    private function calculateInstallment(int $amountCents, float $monthlyRate, int $numInstallments): int
    {
        if ($monthlyRate == 0) {
            // If rate is zero, simple division (no interest)
            return intval($amountCents / $numInstallments);
        }

        // COMPOUND INTEREST formula for payment (PMT)
        // PMT = PV * [i * (1+i)^n] / [(1+i)^n - 1]
        // Where:
        // PV = Present Value (loan amount)
        // i = interest rate per period (monthly)
        // n = number of periods (installments)

        $factor = pow(1 + $monthlyRate, $numInstallments); // (1+i)^n
        $installment = $amountCents * ($monthlyRate * $factor) / ($factor - 1);

        return intval(round($installment));
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
