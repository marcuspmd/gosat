<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\DTOs\CreditSimulationResponseDTO;
use App\Application\Services\CreditOfferApplicationService;
use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Credit\Services\CreditCalculatorService;
use App\Domain\Shared\ValueObjects\CPF;
use App\Domain\Shared\ValueObjects\InstallmentCount;
use App\Domain\Shared\ValueObjects\InterestRate;
use App\Domain\Shared\ValueObjects\Money;
use App\Infrastructure\Http\Resources\CreditSimulationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;

class CreditOfferController extends Controller
{
    public function __construct(
        private readonly CreditOfferApplicationService $applicationService,
        private readonly CreditOfferRepositoryInterface $creditOfferRepository,
        private readonly CreditCalculatorService $creditCalculatorService
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
            $customersData = $this->creditOfferRepository->getAllCustomersWithOffers();

            return response()->json([
                'status' => 'success',
                'data' => $customersData,
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
            // Check if there are any jobs running for this request_id
            $pendingJob = $this->creditOfferRepository->findPendingJobByRequestId($requestId);

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
            $failedJob = $this->creditOfferRepository->findFailedJobByRequestId($requestId);

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
            $offersCount = $this->creditOfferRepository->countOffersByRequestId($requestId);

            if ($offersCount > 0) {
                return response()->json([
                    'request_id' => $requestId,
                    'status' => 'completed',
                    'message' => 'Request completed successfully',
                    'offers_found' => $offersCount,
                ]);
            }

            // Não há jobs nem ofertas com este request_id
            return response()->json([
                'error' => 'not_found',
                'message' => 'Request not found',
            ], 404);

        } catch (\Exception) {
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
            $cpf = new CPF($request->query('cpf'));
            $limit = (int) $request->query('limit', 10);

            $offers = $this->creditOfferRepository->getOffersForCpf($cpf, $limit);

            return response()->json([
                'cpf' => $cpf->asString(),
                'offers' => $offers,
                'total_offers' => count($offers),
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
        \Illuminate\Support\Facades\Log::info('Simulate request data:', $request->all());

        $request->validate([
            'cpf' => 'required|string|regex:/^\d{11}$/',
            'amount' => 'required|integer|min:100', // in cents
            'installments' => 'required|integer|min:1',
            'modality' => 'nullable|string',
        ]);

        try {
            $cpf = new CPF($request->input('cpf'));
            $amountCents = $request->input('amount');
            $installments = $request->input('installments');
            $modality = $request->input('modality');

            $offers = $this->creditOfferRepository->getSimulationOffers($cpf, $amountCents, $installments, $modality);

            if (empty($offers)) {
                return response()->json([
                    'error' => 'no_offers',
                    'message' => 'No offers available for the provided parameters',
                ], 404);
            }

            // Calculate simulations for each offer using domain service
            $simulations = [];
            foreach ($offers as $offer) {
                $money = Money::fromCents($amountCents);
                $interestRate = new InterestRate((float) $offer->monthly_interest_rate);
                $installmentCount = new InstallmentCount($installments);

                $monthlyPayment = $this->creditCalculatorService->calculateMonthlyPayment(
                    $money,
                    $interestRate,
                    $installmentCount
                );

                $totalAmount = $this->creditCalculatorService->calculateTotalAmount(
                    $money,
                    $interestRate,
                    $installmentCount
                );

                $totalInterest = $this->creditCalculatorService->calculateTotalInterest(
                    $money,
                    $interestRate,
                    $installmentCount
                );

                $simulations[] = [
                    'financial_institution' => $offer->financial_institution,
                    'credit_modality' => $offer->credit_modality,
                    'requested_amount' => $amountCents,
                    'total_amount' => $totalAmount->amountInCents,
                    'monthly_interest_rate' => (float) $offer->monthly_interest_rate,
                    'annual_interest_rate' => $interestRate->monthlyRate * 12,
                    'installments' => $installments,
                    'monthly_payment' => $monthlyPayment->amountInCents,
                    'total_interest' => $totalInterest->amountInCents,
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

            $simulationDTO = new CreditSimulationResponseDTO(
                cpf: $cpf->asString(),
                requestedAmount: $amountCents / 100, // Convert to currency unit
                requestedInstallments: $installments,
                simulations: $bestOffers,
                status: 'success'
            );

            return (new CreditSimulationResource($simulationDTO))->response();

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
