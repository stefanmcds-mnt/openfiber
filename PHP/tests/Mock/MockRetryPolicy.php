<?php

declare(strict_types=1);

namespace OpenFiber\Tests\Mock;

use OpenFiber\Interfaces\RetryPolicyInterface;

/**
 * Mock implementation of RetryPolicyInterface for testing
 */
class MockRetryPolicy implements RetryPolicyInterface
{
    private bool $shouldRetry = false;
    private array $queuedItems = [];

    public function shouldRetry(\Exception $e): bool
    {
        return $this->shouldRetry;
    }

    public function queueForRetry(string $serviceName, string $codiceOrdineOlo, string $idNotifica, array $payload, \Exception $e): void
    {
        $this->queuedItems[] = [
            'serviceName' => $serviceName,
            'codiceOrdineOlo' => $codiceOrdineOlo,
            'idNotifica' => $idNotifica,
            'payload' => $payload,
            'exception' => $e,
            'timestamp' => time()
        ];
    }

    /**
     * Test helper: Set whether retries should be attempted
     */
    public function setShouldRetry(bool $shouldRetry): void
    {
        $this->shouldRetry = $shouldRetry;
    }

    /**
     * Test helper: Get all queued items
     */
    public function getQueuedItems(): array
    {
        return $this->queuedItems;
    }

    /**
     * Test helper: Get number of queued items
     */
    public function getQueueCount(): int
    {
        return count($this->queuedItems);
    }

    /**
     * Test helper: Clear the queue
     */
    public function clearQueue(): void
    {
        $this->queuedItems = [];
    }

    /**
     * Test helper: Check if a specific service was queued
     */
    public function hasQueuedService(string $serviceName): bool
    {
        foreach ($this->queuedItems as $item) {
            if ($item['serviceName'] === $serviceName) {
                return true;
            }
        }
        return false;
    }
}
