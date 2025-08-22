<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queue\Jobs;

use App\Infrastructure\Queue\Jobs\FetchCreditOffersJob;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

class FetchCreditOffersJobTest extends TestCase
{
    private FetchCreditOffersJob $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->job = new FetchCreditOffersJob('12345678901', 'test-request-id');
    }

    /**
     * Test the shouldFailImmediately method using reflection.
     */
    private function callShouldFailImmediately(\Throwable $exception): bool
    {
        $reflection = new ReflectionClass($this->job);
        $method = $reflection->getMethod('shouldFailImmediately');
        $method->setAccessible(true);

        return $method->invoke($this->job, $exception);
    }

    public function test_should_fail_immediately_with_401_error(): void
    {
        $exception = new RuntimeException('Falha na comunicação com API externa: 401 Unauthorized');

        $result = $this->callShouldFailImmediately($exception);

        $this->assertTrue($result, 'Job should fail immediately for 401 errors');
    }

    public function test_should_fail_immediately_with_404_error(): void
    {
        $exception = new RuntimeException('Falha na comunicação com API externa: 404 Not Found');

        $result = $this->callShouldFailImmediately($exception);

        $this->assertTrue($result, 'Job should fail immediately for 404 errors');
    }

    public function test_should_fail_immediately_with_422_error(): void
    {
        $exception = new RuntimeException('Falha na comunicação com API externa: 422 Unprocessable Content');

        $result = $this->callShouldFailImmediately($exception);

        $this->assertTrue($result, 'Job should fail immediately for 422 errors');
    }

    public function test_should_fail_immediately_with_400_error(): void
    {
        $exception = new RuntimeException('Falha na comunicação com API externa: 400 Bad Request');

        $result = $this->callShouldFailImmediately($exception);

        $this->assertTrue($result, 'Job should fail immediately for 400 errors');
    }

    public function test_should_fail_immediately_with_403_error(): void
    {
        $exception = new RuntimeException('Falha na comunicação com API externa: 403 Forbidden');

        $result = $this->callShouldFailImmediately($exception);

        $this->assertTrue($result, 'Job should fail immediately for 403 errors');
    }

    public function test_should_fail_immediately_with_cpf_not_found_error(): void
    {
        $exception = new RuntimeException('CPF não encontrado');

        $result = $this->callShouldFailImmediately($exception);

        $this->assertTrue($result, 'Job should fail immediately for CPF not found errors');
    }

    public function test_should_fail_immediately_with_invalid_cpf_error(): void
    {
        $exception = new RuntimeException('CPF inválido');

        $result = $this->callShouldFailImmediately($exception);

        $this->assertTrue($result, 'Job should fail immediately for invalid CPF errors');
    }

    public function test_should_retry_with_500_error(): void
    {
        $exception = new RuntimeException('Falha na comunicação com API externa: 500 Internal Server Error');

        $result = $this->callShouldFailImmediately($exception);

        $this->assertFalse($result, 'Job should retry for 500 errors');
    }

    public function test_should_retry_with_502_error(): void
    {
        $exception = new RuntimeException('Falha na comunicação com API externa: 502 Bad Gateway');

        $result = $this->callShouldFailImmediately($exception);

        $this->assertFalse($result, 'Job should retry for 502 errors');
    }

    public function test_should_retry_with_503_error(): void
    {
        $exception = new RuntimeException('Falha na comunicação com API externa: 503 Service Unavailable');

        $result = $this->callShouldFailImmediately($exception);

        $this->assertFalse($result, 'Job should retry for 503 errors');
    }

    public function test_should_retry_with_timeout_error(): void
    {
        $exception = new RuntimeException('Connection timed out');

        $result = $this->callShouldFailImmediately($exception);

        $this->assertFalse($result, 'Job should retry for timeout errors');
    }

    public function test_should_retry_with_curl_error(): void
    {
        $exception = new RuntimeException('cURL error 28: Operation timed out');

        $result = $this->callShouldFailImmediately($exception);

        $this->assertFalse($result, 'Job should retry for cURL errors');
    }

    public function test_should_retry_unknown_errors_by_default(): void
    {
        $exception = new RuntimeException('Some unknown error');

        $result = $this->callShouldFailImmediately($exception);

        $this->assertFalse($result, 'Job should retry unknown errors by default');
    }
}
