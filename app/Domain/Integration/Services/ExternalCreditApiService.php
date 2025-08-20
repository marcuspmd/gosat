<?php

declare(strict_types=1);

namespace App\Domain\Integration\Services;

use App\Domain\Shared\ValueObjects\CPF;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;

final readonly class ExternalCreditApiService
{
    private const API_BASE_URL = 'https://dev.gosat.org/api/v1';
    private const TIMEOUT_SECONDS = 30;

    public function __construct(
        private Client $httpClient
    ) {}

    public function fetchCredit(CPF $cpf): array
    {
        $endpoint = sprintf('%s/simulacao/credito', self::API_BASE_URL);

        $requestData = [
            'cpf' => $cpf->value,
        ];

        return $this->makeApiRequest('POST', $endpoint, $requestData);
    }

    public function fetchOffer(
        CPF $cpf,
        int $institutionId,
        string $modalityCode
    ): ?array {
        $endpoint = sprintf('%s/simulacao/oferta', self::API_BASE_URL);

        $requestData = [
            'cpf' => $cpf->value,
            'instituicao_id' => $institutionId,
            'codModalidade' => $modalityCode,
        ];

        try {
            $response = $this->makeApiRequest('POST', $endpoint, $requestData);

            return ! empty($response) ? $response[0] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function makeApiRequest(string $method, string $url, array $data = []): array
    {
        try {
            $options = [
                'timeout' => self::TIMEOUT_SECONDS,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'GoSat-CreditSystem/1.0',
                ],
            ];

            if (! empty($data)) {
                if (strtoupper($method) === 'GET') {
                    $options['query'] = $data;
                } else {
                    $options['json'] = $data;
                }
            }

            $response = $this->httpClient->request($method, $url, $options);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $body = $response->getBody()->getContents();
                $decoded = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new InvalidArgumentException('Resposta da API não é um JSON válido');
                }

                return $this->normalizeApiResponse($decoded);
            }

            if ($statusCode >= 400 && $statusCode < 500) {
                throw new InvalidArgumentException(
                    sprintf('Erro na requisição à API externa: HTTP %d', $statusCode)
                );
            }

            throw new \RuntimeException(
                sprintf('Erro interno da API externa: HTTP %d', $statusCode)
            );

        } catch (RequestException $e) {
            if ($e->getCode() >= 400 && $e->getCode() < 500) {
                throw new InvalidArgumentException(
                    sprintf('Erro do cliente na API externa: %s', $e->getMessage()),
                    $e->getCode(),
                    $e
                );
            }

            throw new \RuntimeException(
                sprintf('Falha na comunicação com API externa: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );

        } catch (GuzzleException $e) {
            throw new \RuntimeException(
                sprintf('Erro na requisição HTTP: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    private function normalizeApiResponse(array $response): array
    {
        // A API pode retornar diferentes formatos - normalizar para formato padrão
        if (isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }

        if (isset($response['ofertas']) && is_array($response['ofertas'])) {
            return $response['ofertas'];
        }

        if (isset($response['results']) && is_array($response['results'])) {
            return $response['results'];
        }

        // Se já é um array de ofertas diretamente
        if (is_array($response)) {
            return $response;
        }

        return [];
    }
}
