<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\DTOs\CreditSimulationRequestDTO;
use App\Application\Services\CreditOfferApplicationService;
use App\Infrastructure\Http\Requests\CreditSimulationRequest;
use App\Infrastructure\Http\Resources\CreditOfferResource;
use App\Infrastructure\Http\Resources\CreditRequestResource;
use App\Infrastructure\Http\Resources\CreditSimulationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;

class CreditOfferController extends Controller
{
    public function __construct(
        private readonly CreditOfferApplicationService $applicationService
    ) {}

    /**
     * Iniciar uma nova consulta de crédito.
     */
    public function search(Request $request): JsonResponse
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

    /**
     * Verificar o status de uma consulta de crédito.
     */
    public function status(string $requestId): JsonResponse
    {
        try {
            $result = $this->applicationService->getCreditOfferStatus($requestId);

            $statusCode = match ($result['status']) {
                'processing' => 202,
                'completed', 'completed_with_failures' => 200,
                'not_found' => 404,
                default => 200
            };

            return response()->json($result, $statusCode);

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

    /**
     * Simular uma oferta de crédito específica.
     */
    public function simulate(CreditSimulationRequest $request): JsonResponse
    {
        try {
            $simulationRequest = CreditSimulationRequestDTO::fromArray(
                $request->validated()
            );

            $result = $this->applicationService->simulateCreditOffer($simulationRequest);

            return response()->json(
                new CreditSimulationResource($result)
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

    /**
     * Listar ofertas de crédito para um CPF específico.
     */
    public function offers(Request $request): JsonResponse
    {
        $request->validate([
            'cpf' => 'required|string|regex:/^\d{11}$/',
            'limit' => 'integer|min:1|max:10',
        ]);

        try {
            $offers = $this->applicationService->getCreditOffersByCpf(
                $request->cpf,
                $request->integer('limit', 3)
            );

            return response()->json([
                'cpf' => $request->cpf,
                'offers' => CreditOfferResource::collection($offers),
                'total_offers' => count($offers),
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

    /**
     * Health check para verificar se o serviço está funcionando.
     */
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
