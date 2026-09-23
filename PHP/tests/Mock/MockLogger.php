<?php

declare(strict_types=1);

namespace OpenFiber\Tests\Mock;

use OpenFiber\Interfaces\LoggerInterface;
use OpenFiber\Interfaces\DynamicDtoInterface;

/**
 * Mock implementation of LoggerInterface for testing
 */
class MockLogger implements LoggerInterface
{
    private array $logs = [];
    private bool $shouldFail = false;

    public function logSuccess(string $serviceName, DynamicDtoInterface $dto, string $response): void
    {
        if ($this->shouldFail) {
            return;
        }

        $this->logs[] = [
            'type' => 'success',
            'service' => $serviceName,
            'dto' => $dto,
            'response' => $response,
            'timestamp' => time()
        ];
    }

    public function logFailure(string $serviceName, DynamicDtoInterface $dto, \Exception $e): void
    {
        if ($this->shouldFail) {
            return;
        }

        $this->logs[] = [
            'type' => 'error',
            'service' => $serviceName,
            'dto' => $dto,
            'error' => $e->getMessage(),
            'exception' => $e,
            'timestamp' => time()
        ];
    }

    /**
     * Test helper: Get all logs
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Test helper: Get number of logs
     */
    public function getLogCount(): int
    {
        return count($this->logs);
    }

    /**
     * Test helper: Get success logs
     */
    public function getSuccessLogs(): array
    {
        return array_filter($this->logs, fn($log) => $log['type'] === 'success');
    }

    /**
     * Test helper: Get error logs
     */
    public function getErrorLogs(): array
    {
        return array_filter($this->logs, fn($log) => $log['type'] === 'error');
    }

    /**
     * Test helper: Clear all logs
     */
    public function clear(): void
    {
        $this->logs = [];
    }

    /**
     * Test helper: Make the logger fail
     */
    public function setShouldFail(bool $shouldFail): void
    {
        $this->shouldFail = $shouldFail;
    }

    /**
     * Test helper: Check if a specific service was logged
     */
    public function hasLogForService(string $serviceName): bool
    {
        foreach ($this->logs as $log) {
            if ($log['service'] === $serviceName) {
                return true;
            }
        }
        return false;
    }
}
