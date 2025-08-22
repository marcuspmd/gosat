<?php

declare(strict_types=1);

use App\Domain\Integration\Services\ExternalCreditApiService;
use App\Domain\Shared\Dtos\ExternalCreditDto;
use App\Domain\Shared\ValueObjects\CPF;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createMockHttpClient(array $responses = []): Client
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);

    return new Client(['handler' => $handlerStack]);
}

function createSampleCreditApiResponse(): array
{
    return [
        'instituicoes' => [
            [
                'id' => 1,
                'nome' => 'Banco PingApp',
                'modalidades' => [
                    [
                        'nome' => 'crédito pessoal',
                        'cod' => '3',
                    ],
                    [
                        'nome' => 'crédito consignado',
                        'cod' => '13',
                    ],
                ],
            ],
            [
                'id' => 2,
                'nome' => 'Financeira Assert',
                'modalidades' => [
                    [
                        'nome' => 'crédito pessoal',
                        'cod' => 'a50ed2ed-2b8b-4cc7-ac95-71a5568b34ce',
                    ],
                ],
            ],
        ],
    ];
}

function createSampleOfferApiResponse(): array
{
    return [
        'QntParcelaMin' => 12,
        'QntParcelaMax' => 48,
        'valorMin' => 3000,
        'valorMax' => 7000,
        'jurosMes' => 0.0365,
    ];
}

describe('ExternalCreditApiService', function () {
    beforeEach(function () {
        // Note: Log facade will use the default logger configured in Laravel
    });

    it('can fetch credit data successfully', function () {
        $creditResponse = new Response(200, [], json_encode(createSampleCreditApiResponse()));
        $offerResponse1 = new Response(200, [], json_encode(createSampleOfferApiResponse()));
        $offerResponse2 = new Response(200, [], json_encode([
            'QntParcelaMin' => 6,
            'QntParcelaMax' => 36,
            'valorMin' => 1000,
            'valorMax' => 5000,
            'jurosMes' => 0.025,
        ]));
        $offerResponse3 = new Response(200, [], json_encode([
            'QntParcelaMin' => 24,
            'QntParcelaMax' => 60,
            'valorMin' => 5000,
            'valorMax' => 15000,
            'jurosMes' => 0.018,
        ]));

        $httpClient = createMockHttpClient([
            $creditResponse,
            $offerResponse1,
            $offerResponse2,
            $offerResponse3,
        ]);

        $service = new ExternalCreditApiService($httpClient);

        $inputDto = new ExternalCreditDto(
            cpf: new CPF('11144477735'),
            creditRequestId: 'req-123'
        );

        $result = $service->fetchCredit($inputDto);

        expect($result)->toBeInstanceOf(ExternalCreditDto::class)
            ->and($result->cpf->value)->toBe('11144477735')
            ->and($result->creditRequestId)->toBe('req-123')
            ->and($result->institutions)->toHaveCount(2);

        // Check first institution
        $firstInstitution = $result->institutions[0];
        expect($firstInstitution->id)->toBe('1')
            ->and($firstInstitution->name)->toBe('Banco PingApp')
            ->and($firstInstitution->modalities)->toHaveCount(2);

        // Check first modality of first institution
        $firstModality = $firstInstitution->modalities[0];
        expect($firstModality->name)->toBe('crédito pessoal')
            ->and($firstModality->id)->toBe('3')
            ->and($firstModality->offer->minInstallments)->toBe(12)
            ->and($firstModality->offer->maxInstallments)->toBe(48)
            ->and($firstModality->offer->minAmountInCents)->toBe(300000) // 3000 * 100
            ->and($firstModality->offer->maxAmountInCents)->toBe(700000) // 7000 * 100
            ->and($firstModality->offer->interestRate)->toBe(0.0365);
    });

    it('handles failed offer requests gracefully', function () {
        $creditResponse = new Response(200, [], json_encode([
            'instituicoes' => [
                [
                    'id' => 1,
                    'nome' => 'Banco Teste',
                    'modalidades' => [
                        [
                            'nome' => 'crédito pessoal',
                            'cod' => '3',
                        ],
                    ],
                ],
            ],
        ]));

        $failedOfferResponse = new RequestException(
            'Connection timeout',
            new Request('POST', 'test'),
            new Response(500, [], 'Internal Server Error')
        );

        $httpClient = createMockHttpClient([
            $creditResponse,
            $failedOfferResponse,
        ]);

        $service = new ExternalCreditApiService($httpClient);

        $inputDto = new ExternalCreditDto(
            cpf: new CPF('11144477735'),
            creditRequestId: 'req-456'
        );

        $result = $service->fetchCredit($inputDto);

        expect($result->institutions)->toHaveCount(1);

        $institution = $result->institutions[0];
        expect($institution->modalities)->toHaveCount(1);

        $modality = $institution->modalities[0];
        // Should have default empty offer when request fails
        expect($modality->offer->minInstallments)->toBe(1)
            ->and($modality->offer->maxInstallments)->toBe(1)
            ->and($modality->offer->interestRate)->toBe(0.0)
            ->and($modality->offer->minAmountInCents)->toBe(0)
            ->and($modality->offer->maxAmountInCents)->toBe(0);
    });

    it('throws exception for invalid JSON response', function () {
        $invalidJsonResponse = new Response(200, [], 'invalid json');

        $httpClient = createMockHttpClient([
            $invalidJsonResponse,
        ]);

        $service = new ExternalCreditApiService($httpClient);

        $inputDto = new ExternalCreditDto(
            cpf: new CPF('11144477735'),
            creditRequestId: 'req-789'
        );

        expect(fn () => $service->fetchCredit($inputDto))
            ->toThrow(\RuntimeException::class, 'Falha na comunicação com API externa');
    });

    it('throws exception for non-200 HTTP status', function () {
        $errorResponse = new Response(400, [], json_encode(['error' => 'Bad Request']));

        $httpClient = createMockHttpClient([
            $errorResponse,
        ]);

        $service = new ExternalCreditApiService($httpClient);

        $inputDto = new ExternalCreditDto(
            cpf: new CPF('11144477735'),
            creditRequestId: 'req-error'
        );

        expect(fn () => $service->fetchCredit($inputDto))
            ->toThrow(\RuntimeException::class, 'Falha na comunicação com API externa');
    });

    it('throws exception for request failures', function () {
        $requestException = new RequestException(
            'Connection failed',
            new Request('POST', 'test')
        );

        $httpClient = createMockHttpClient([
            $requestException,
        ]);

        $service = new ExternalCreditApiService($httpClient);

        $inputDto = new ExternalCreditDto(
            cpf: new CPF('11144477735'),
            creditRequestId: 'req-fail'
        );

        expect(fn () => $service->fetchCredit($inputDto))
            ->toThrow(\RuntimeException::class, 'Falha na comunicação com API externa');
    });

    it('handles empty institutions response', function () {
        $emptyResponse = new Response(200, [], json_encode(['instituicoes' => []]));

        $httpClient = createMockHttpClient([
            $emptyResponse,
        ]);

        $service = new ExternalCreditApiService($httpClient);

        $inputDto = new ExternalCreditDto(
            cpf: new CPF('11144477735'),
            creditRequestId: 'req-empty'
        );

        $result = $service->fetchCredit($inputDto);

        expect($result->institutions)->toHaveCount(0);
    });

    it('handles institutions without modalities', function () {
        $responseWithoutModalities = new Response(200, [], json_encode([
            'instituicoes' => [
                [
                    'id' => 1,
                    'nome' => 'Banco Sem Modalidades',
                    'modalidades' => [],
                ],
            ],
        ]));

        $httpClient = createMockHttpClient([
            $responseWithoutModalities,
        ]);

        $service = new ExternalCreditApiService($httpClient);

        $inputDto = new ExternalCreditDto(
            cpf: new CPF('11144477735'),
            creditRequestId: 'req-no-modalities'
        );

        $result = $service->fetchCredit($inputDto);

        expect($result->institutions)->toHaveCount(1)
            ->and($result->institutions[0]->modalities)->toHaveCount(0);
    });

    it('can be configured with custom options', function () {
        $response = new Response(200, [], json_encode(createSampleCreditApiResponse()));
        $offerResponse = new Response(200, [], json_encode(createSampleOfferApiResponse()));

        $httpClient = createMockHttpClient([
            $response,
            $offerResponse,
        ]);

        $customOptions = [
            'timeout' => 30,
            'headers' => [
                'Custom-Header' => 'Custom-Value',
            ],
        ];

        $service = new ExternalCreditApiService($httpClient, $customOptions);

        $inputDto = new ExternalCreditDto(
            cpf: new CPF('11144477735'),
            creditRequestId: 'req-custom'
        );

        // Should not throw any exceptions
        $result = $service->fetchCredit($inputDto);

        expect($result)->toBeInstanceOf(ExternalCreditDto::class);
    });

    it('correctly maps API response fields to DTO', function () {
        $creditResponse = new Response(200, [], json_encode([
            'instituicoes' => [
                [
                    'id' => 999,
                    'nome' => 'Banco Mapeamento',
                    'modalidades' => [
                        [
                            'nome' => 'Modalidade Teste',
                            'cod' => 'MOD-999',
                        ],
                    ],
                ],
            ],
        ]));

        $offerResponse = new Response(200, [], json_encode([
            'QntParcelaMin' => 1,
            'QntParcelaMax' => 120,
            'valorMin' => 500,
            'valorMax' => 50000,
            'jurosMes' => 0.0199,
        ]));

        $httpClient = createMockHttpClient([
            $creditResponse,
            $offerResponse,
        ]);

        $service = new ExternalCreditApiService($httpClient);

        $inputDto = new ExternalCreditDto(
            cpf: new CPF('11144477735'),
            creditRequestId: 'req-mapping'
        );

        $result = $service->fetchCredit($inputDto);

        $institution = $result->institutions[0];
        $modality = $institution->modalities[0];

        expect($institution->id)->toBe('999')
            ->and($institution->name)->toBe('Banco Mapeamento')
            ->and($institution->slug)->toBe('banco-mapeamento')
            ->and($modality->id)->toBe('MOD-999')
            ->and($modality->name)->toBe('Modalidade Teste')
            ->and($modality->slug)->toBe('modalidade-teste')
            ->and($modality->offer->minInstallments)->toBe(1)
            ->and($modality->offer->maxInstallments)->toBe(120)
            ->and($modality->offer->minAmountInCents)->toBe(50000) // 500 * 100
            ->and($modality->offer->maxAmountInCents)->toBe(5000000) // 50000 * 100
            ->and($modality->offer->interestRate)->toBe(0.0199);
    });

    it('handles malformed offer response gracefully', function () {
        $creditResponse = new Response(200, [], json_encode([
            'instituicoes' => [
                [
                    'id' => 1,
                    'nome' => 'Banco Teste',
                    'modalidades' => [
                        [
                            'nome' => 'crédito pessoal',
                            'cod' => '3',
                        ],
                    ],
                ],
            ],
        ]));

        $malformedOfferResponse = new Response(200, [], 'not json');

        $httpClient = createMockHttpClient([
            $creditResponse,
            $malformedOfferResponse,
        ]);

        $service = new ExternalCreditApiService($httpClient);

        $inputDto = new ExternalCreditDto(
            cpf: new CPF('11144477735'),
            creditRequestId: 'req-malformed'
        );

        $result = $service->fetchCredit($inputDto);

        // Should return default offer when JSON parsing fails
        $modality = $result->institutions[0]->modalities[0];
        expect($modality->offer->minInstallments)->toBe(1)
            ->and($modality->offer->maxInstallments)->toBe(1)
            ->and($modality->offer->interestRate)->toBe(0.0);
    });
});

afterEach(function () {
    Mockery::close();
});
