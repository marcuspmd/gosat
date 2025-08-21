<?php

declare(strict_types=1);

namespace App\Domain\Integration\Services;

use App\Domain\Shared\Dtos\ExternalCreditDto;
use App\Domain\Shared\Dtos\ExternalCreditInstitutionDto;
use App\Domain\Shared\Dtos\ExternalCreditModalityDto;
use App\Domain\Shared\Dtos\ExternalCreditOfferDto;
use App\Domain\Shared\ValueObjects\CPF;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final readonly class ExternalCreditApiService
{
    private const API_BASE_URL = 'https://dev.gosat.org/api/v1';
    private const TIMEOUT_SECONDS = 10;

    private array $defaultOptions;

    public function __construct(
        private Client $httpClient,
        array $defaultOptions = []
    ) {
        $this->defaultOptions = array_replace_recursive([
            'timeout' => self::TIMEOUT_SECONDS,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'GoSat-CreditSystem/1.0',
            ],
        ], $defaultOptions);
    }

    public function fetchCredit(ExternalCreditDto $data): ExternalCreditDto
    {
        $endpoint = sprintf('%s/simulacao/credito', self::API_BASE_URL);

        $requestData = [
            'cpf' => $data->cpf->value,
        ];

        Log::info('Iniciando requisição de crédito', [
            'endpoint' => $endpoint,
            'cpf' => $data->cpf->value,
            'request_data' => $requestData,
        ]);

        $result = $this->makeApiRequest('POST', $endpoint, $requestData);

        Log::info('Resposta da API de crédito recebida', [
            'response_size' => count($result),
            'institutions_count' => count($result['instituicoes'] ?? []),
        ]);

        $this->populateInstitutionData($data, $result);

        Log::info('Dados de instituições populados', [
            'final_institutions_count' => count($data->institutions),
        ]);

        return $data;

    }

    private function populateInstitutionData(
        ExternalCreditDto $data,
        array $result
    ): ExternalCreditDto {
        $promises = [];
        $institutionModalityMap = [];

        Log::info('Iniciando população de dados de instituições', [
            'institutions_received' => count($result['instituicoes'] ?? []),
        ]);

        // Collect all offer requests as promises
        foreach ($result['instituicoes'] as $institutionIndex => $institution) {
            $modalities = $institution['modalidades'] ?? [];
            Log::info('Processando instituição', [
                'institution_index' => $institutionIndex,
                'institution_name' => $institution['nome'] ?? 'N/A',
                'modalities_count' => count($modalities),
            ]);

            foreach ($modalities as $modalityIndex => $modalityItem) {
                $cpf = $data->cpf;
                $institutionId = (int) ($institution['id'] ?? 0);
                $modalityCode = (string) ($modalityItem['cod'] ?? '');

                $key = "{$institutionIndex}_{$modalityIndex}";
                $institutionModalityMap[$key] = [
                    'institution' => $institution,
                    'modality' => $modalityItem,
                    'institutionIndex' => $institutionIndex,
                    'modalityIndex' => $modalityIndex,
                ];

                Log::info('Criando promise para modalidade', [
                    'key' => $key,
                    'institution_id' => $institutionId,
                    'modality_code' => $modalityCode,
                    'modality_name' => $modalityItem['nome'] ?? 'N/A',
                ]);

                $promises[$key] = $this->fetchOfferAsync($cpf, $institutionId, $modalityCode);
            }
        }

        Log::info('Total de promises criadas', [
            'promises_count' => count($promises),
        ]);

        // Wait for all promises to settle
        $responses = Utils::settle($promises)->wait();

        Log::info('Todas as promises foram resolvidas', [
            'responses_count' => count($responses),
        ]);

        // Group responses by institution
        $institutionData = [];
        foreach ($responses as $key => $response) {
            $map = $institutionModalityMap[$key];
            $institutionIndex = $map['institutionIndex'];
            $modalityIndex = $map['modalityIndex'];

            Log::info('Processando resposta de modalidade', [
                'key' => $key,
                'state' => $response['state'],
                'institution_index' => $institutionIndex,
                'modality_index' => $modalityIndex,
                'modality_name' => $map['modality']['nome'] ?? 'N/A',
            ]);

            if (! isset($institutionData[$institutionIndex])) {
                $institutionData[$institutionIndex] = [
                    'institution' => $map['institution'],
                    'modalities' => [],
                ];
            }

            if ($response['state'] === 'fulfilled') {
                $offer = $response['value'];
                Log::info('Promise fulfilled com sucesso', [
                    'key' => $key,
                    'offer_data' => [
                        'min_installments' => $offer->minInstallments ?? 'N/A',
                        'max_installments' => $offer->maxInstallments ?? 'N/A',
                        'interest_rate' => $offer->interestRate ?? 'N/A',
                        'min_amount' => $offer->minAmountInCents ?? 'N/A',
                        'max_amount' => $offer->maxAmountInCents ?? 'N/A',
                    ],
                ]);
            } else {
                Log::warning('Promise rejeitada', [
                    'key' => $key,
                    'reason' => $response['reason'] ?? 'Unknown',
                ]);
                $offer = new ExternalCreditOfferDto;
            }

            $institutionData[$institutionIndex]['modalities'][$modalityIndex] = new ExternalCreditModalityDto(
                id: (string) ($map['modality']['cod'] ?? ''),
                name: $map['modality']['nome'] ?? '',
                slug: Str::slug($map['modality']['nome'] ?? ''),
                offer: $offer
            );
        }

        // Build final institution DTOs
        Log::info('Construindo DTOs finais de instituições', [
            'institution_data_count' => count($institutionData),
        ]);

        foreach ($institutionData as $instIndex => $instData) {
            $institution = $instData['institution'];
            $name = $institution['nome'] ?? '';

            $institutionDto = new ExternalCreditInstitutionDto(
                id: (string) ($institution['id'] ?? ''),
                name: $name,
                slug: Str::slug($name),
                modalities: array_values($instData['modalities']) // Reindex array
            );

            Log::info('DTO de instituição criado', [
                'institution_index' => $instIndex,
                'institution_id' => $institutionDto->id,
                'institution_name' => $institutionDto->name,
                'modalities_count' => count($institutionDto->modalities),
            ]);

            $data->institutions[] = $institutionDto;
        }

        Log::info('Finalização da população de dados', [
            'total_institutions_added' => count($data->institutions),
        ]);

        return $data;
    }

    private function fetchOfferAsync(
        CPF $cpf,
        int $institutionId,
        string $modalityCode
    ): \GuzzleHttp\Promise\PromiseInterface {
        $endpoint = sprintf('%s/simulacao/oferta', self::API_BASE_URL);

        $requestData = [
            'cpf' => $cpf->value,
            'instituicao_id' => $institutionId,
            'codModalidade' => $modalityCode,
        ];

        $options = $this->defaultOptions;
        $options = array_replace_recursive($options, [
            'json' => $requestData,
        ]);

        return $this->httpClient->requestAsync('POST', $endpoint, $options)
            ->then(
                function ($response) use ($cpf, $institutionId, $modalityCode) {
                    $statusCode = $response->getStatusCode();

                    Log::info('Resposta de oferta recebida', [
                        'cpf' => $cpf->value,
                        'institution_id' => $institutionId,
                        'modality_code' => $modalityCode,
                        'status_code' => $statusCode,
                    ]);

                    if ($statusCode !== 200) {
                        Log::error('Erro na requisição de oferta', [
                            'cpf' => $cpf->value,
                            'institution_id' => $institutionId,
                            'modality_code' => $modalityCode,
                            'status_code' => $statusCode,
                            'response_body' => $response->getBody()->getContents(),
                        ]);

                        throw new RuntimeException(
                            sprintf('Erro na requisição HTTP: %s', $response->getBody()->getContents()),
                            $statusCode
                        );
                    }

                    $body = $response->getBody()->getContents();

                    Log::debug('Body da resposta de oferta', [
                        'cpf' => $cpf->value,
                        'institution_id' => $institutionId,
                        'modality_code' => $modalityCode,
                        'body' => $body,
                    ]);

                    $decoded = json_decode($body, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error('Erro ao decodificar JSON da oferta', [
                            'cpf' => $cpf->value,
                            'institution_id' => $institutionId,
                            'modality_code' => $modalityCode,
                            'json_error' => json_last_error_msg(),
                            'body' => $body,
                        ]);

                        throw new InvalidArgumentException('Resposta da API não é um JSON válido');
                    }

                    $offerDto = $this->populateExternalCreditOfferDto($decoded);

                    Log::info('DTO de oferta criado com sucesso', [
                        'cpf' => $cpf->value,
                        'institution_id' => $institutionId,
                        'modality_code' => $modalityCode,
                        'offer' => [
                            'min_installments' => $offerDto->minInstallments,
                            'max_installments' => $offerDto->maxInstallments,
                            'interest_rate' => $offerDto->interestRate,
                            'min_amount' => $offerDto->minAmountInCents,
                            'max_amount' => $offerDto->maxAmountInCents,
                        ],
                    ]);

                    return $offerDto;
                },
                function ($reason) use ($cpf, $institutionId, $modalityCode) {
                    Log::error('Falha na requisição de oferta', [
                        'cpf' => $cpf->value,
                        'institution_id' => $institutionId,
                        'modality_code' => $modalityCode,
                        'reason' => $reason instanceof Throwable ? $reason->getMessage() : (string) $reason,
                    ]);

                    return new ExternalCreditOfferDto;
                }
            );
    }

    private function populateExternalCreditOfferDto(array $data): ExternalCreditOfferDto
    {
        Log::debug('Populando DTO de oferta externa', [
            'raw_data' => $data,
            'parsed_fields' => [
                'QntParcelaMin' => $data['QntParcelaMin'] ?? 'not_found',
                'QntParcelaMax' => $data['QntParcelaMax'] ?? 'not_found',
                'jurosMes' => $data['jurosMes'] ?? 'not_found',
                'valorMin' => $data['valorMin'] ?? 'not_found',
                'valorMax' => $data['valorMax'] ?? 'not_found',
            ],
        ]);

        $dto = new ExternalCreditOfferDto(
            minInstallments: (int) ($data['QntParcelaMin'] ?? 1),
            maxInstallments: (int) ($data['QntParcelaMax'] ?? 1),
            interestRate: (float) ($data['jurosMes'] ?? 0.0),
            minAmountInCents: (int) ($data['valorMin'] ?? 0) * 100,
            maxAmountInCents: (int) ($data['valorMax'] ?? 0) * 100
        );

        Log::info('DTO de oferta externa criado', [
            'dto' => [
                'min_installments' => $dto->minInstallments,
                'max_installments' => $dto->maxInstallments,
                'interest_rate' => $dto->interestRate,
                'min_amount_cents' => $dto->minAmountInCents,
                'max_amount_cents' => $dto->maxAmountInCents,
            ],
        ]);

        return $dto;
    }

    private function makeApiRequest(string $method, string $url, array $data = []): array
    {
        try {
            $options = $this->defaultOptions;

            if (! empty($data)) {
                if (strtoupper($method) === 'GET') {
                    $options['query'] = $data;
                } else {
                    $options['json'] = $data;
                }
            }

            $response = $this->httpClient->request($method, $url, $options);
            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw new InvalidArgumentException(
                    sprintf('Erro na requisição à API externa: HTTP %d', $statusCode)
                );
            }

            $body = $response->getBody()->getContents();

            Log::debug('Body da resposta da API', [
                'method' => $method,
                'url' => $url,
                'body' => $body,
            ]);

            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Erro ao decodificar JSON da API', [
                    'method' => $method,
                    'url' => $url,
                    'json_error' => json_last_error_msg(),
                    'body' => $body,
                ]);

                throw new InvalidArgumentException('Resposta da API não é um JSON válido');
            }

            Log::info('Resposta da API decodificada com sucesso', [
                'method' => $method,
                'url' => $url,
                'response_keys' => array_keys($decoded ?? []),
            ]);

            return $decoded ?? [];

        } catch (Throwable $e) {
            throw new RuntimeException(
                sprintf('Falha na comunicação com API externa: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }
}
