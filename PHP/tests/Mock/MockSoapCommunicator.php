<?php

declare(strict_types=1);

namespace OpenFiber\Tests\Mock;

use OpenFiber\Interfaces\SoapCommunicatorInterface;
use OpenFiber\Exception\SoapCommunicationException;

/**
 * Mock implementation of SoapCommunicatorInterface for testing
 */
class MockSoapCommunicator implements SoapCommunicatorInterface
{
    private string $mockResponse = 'mock response';
    private bool $shouldFail = false;
    private array $calls = [];
    private ?\Throwable $exceptionToThrow = null;
    private ?string $mockWsdl = null;

    public function send(string $serviceName, array $payload): string
    {
        // Record the call
        $this->calls[] = [
            'serviceName' => $serviceName,
            'payload' => $payload,
            'timestamp' => time()
        ];

        if ($this->exceptionToThrow) {
            throw $this->exceptionToThrow;
        }

        if ($this->shouldFail) {
            throw new SoapCommunicationException(
                'Mock SOAP failure',
                'Server',
                'Mock error',
                ['service' => $serviceName]
            );
        }

        return $this->mockResponse;
    }

    public function getWsdl(string $serviceName): ?string
    {
        return $this->mockWsdl;
    }

    /**
     * Test helper: Set the response to return
     */
    public function setMockResponse(string $response): void
    {
        $this->mockResponse = $response;
    }

    /**
     * Test helper: Set the mock WSDL
     */
    public function setMockWsdl(?string $wsdl): void
    {
        $this->mockWsdl = $wsdl;
    }

    /**
     * Test helper: Make the communicator fail
     */
    public function setShouldFail(bool $shouldFail): void
    {
        $this->shouldFail = $shouldFail;
    }

    /**
     * Test helper: Set a custom exception to throw
     */
    public function setExceptionToThrow(?\Throwable $exception): void
    {
        $this->exceptionToThrow = $exception;
    }

    /**
     * Test helper: Get all calls made
     */
    public function getCalls(): array
    {
        return $this->calls;
    }

    /**
     * Test helper: Get number of calls made
     */
    public function getCallCount(): int
    {
        return count($this->calls);
    }

    /**
     * Test helper: Clear call history
     */
    public function clearCalls(): void
    {
        $this->calls = [];
    }

    /**
     * Test helper: Check if a specific service was called
     */
    public function wasCalled(string $serviceName): bool
    {
        foreach ($this->calls as $call) {
            if ($call['serviceName'] === $serviceName) {
                return true;
            }
        }
        return false;
    }
}
