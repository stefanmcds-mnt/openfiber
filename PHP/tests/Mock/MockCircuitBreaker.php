<?php

declare(strict_types=1);

namespace OpenFiber\Tests\Mock;

use OpenFiber\Interfaces\CircuitBreakerInterface;
use OpenFiber\Exception\ServiceUnavailableException;

/**
 * Mock implementation of CircuitBreakerInterface for testing
 */
class MockCircuitBreaker implements CircuitBreakerInterface
{
    private string $state = 'closed';
    private array $calls = [];
    private int $failureCount = 0;
    private bool $forceOpen = false;

    public function isOpen(): bool
    {
        return $this->forceOpen || $this->state === 'open';
    }

    public function isClosed(): bool
    {
        return !$this->forceOpen && $this->state === 'closed';
    }

    public function isHalfOpen(): bool
    {
        return !$this->forceOpen && $this->state === 'half_open';
    }

    public function execute(callable $callback): mixed
    {
        if ($this->isOpen()) {
            throw new ServiceUnavailableException(
                'test_service',
                $this->failureCount,
                30
            );
        }

        try {
            $result = $callback();
            $this->recordSuccess();
            return $result;
        } catch (\Exception $e) {
            $this->recordFailure($e);
            throw $e;
        }
    }

    public function recordSuccess(): void
    {
        $this->calls[] = ['type' => 'success', 'timestamp' => time()];
        $this->failureCount = 0;
        if ($this->state === 'half_open') {
            $this->state = 'closed';
        }
    }

    public function recordFailure(\Exception $exception): void
    {
        $this->calls[] = ['type' => 'failure', 'exception' => $exception, 'timestamp' => time()];
        $this->failureCount++;
    }

    public function reset(): void
    {
        $this->state = 'closed';
        $this->calls = [];
        $this->failureCount = 0;
        $this->forceOpen = false;
    }

    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Test helper: Set the circuit breaker state
     */
    public function setState(string $state): void
    {
        if (!in_array($state, ['closed', 'open', 'half_open'])) {
            throw new \InvalidArgumentException("Invalid state: $state");
        }
        $this->state = $state;
    }

    /**
     * Test helper: Force the circuit breaker to be open
     */
    public function setForceOpen(bool $forceOpen): void
    {
        $this->forceOpen = $forceOpen;
    }

    /**
     * Test helper: Get all recorded calls
     */
    public function getCalls(): array
    {
        return $this->calls;
    }

    /**
     * Test helper: Set failure count
     */
    public function setFailureCount(int $count): void
    {
        $this->failureCount = $count;
    }
}
