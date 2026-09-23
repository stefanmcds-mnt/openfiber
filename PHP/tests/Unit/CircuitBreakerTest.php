<?php

declare(strict_types=1);

namespace OpenFiber\Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpenFiber\CircuitBreaker;
use OpenFiber\Exception\ServiceUnavailableException;

/**
 * Test per CircuitBreaker
 * 
 * Fase 3: Testabilità e Mockabilità ✓
 */
class CircuitBreakerTest extends TestCase
{
    private CircuitBreaker $circuitBreaker;

    protected function setUp(): void
    {
        // Circuit breaker con soglia bassa per i test
        $this->circuitBreaker = new CircuitBreaker(
            serviceName: 'test_service',
            failureThreshold: 3,
            resetTimeoutSeconds: 5
        );
    }

    public function testInitiallyClosedState(): void
    {
        $this->assertTrue($this->circuitBreaker->isClosed());
        $this->assertFalse($this->circuitBreaker->isOpen());
        $this->assertFalse($this->circuitBreaker->isHalfOpen());
        $this->assertEquals('closed', $this->circuitBreaker->getState());
    }

    public function testSuccessfulExecutionKeepsCircuitClosed(): void
    {
        $result = $this->circuitBreaker->execute(function() {
            return 'success';
        });

        $this->assertEquals('success', $result);
        $this->assertTrue($this->circuitBreaker->isClosed());
        $this->assertEquals(0, $this->circuitBreaker->getFailureCount());
    }

    public function testOpensAfterThresholdFailures(): void
    {
        // Simula 3 fallimenti consecutivi (threshold)
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->execute(function() {
                    throw new \Exception('Test failure');
                });
            } catch (\Exception $e) {
                // Ignora l'eccezione per continuare
            }
        }

        // Dopo 3 fallimenti, il circuit breaker dovrebbe essere aperto
        $this->assertTrue($this->circuitBreaker->isOpen());
        $this->assertEquals('open', $this->circuitBreaker->getState());
    }

    public function testThrowsServiceUnavailableWhenOpen(): void
    {
        // Forza l'apertura del circuit breaker
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->execute(function() {
                    throw new \Exception('Test failure');
                });
            } catch (\Exception $e) {
                // Ignora
            }
        }

        // Ora dovrebbe lanciare ServiceUnavailableException
        $this->expectException(ServiceUnavailableException::class);
        $this->circuitBreaker->execute(function() {
            return 'should not reach here';
        });
    }

    public function testRecordSuccessResetsFailureCount(): void
    {
        // Registra 2 fallimenti
        $this->circuitBreaker->recordFailure(new \Exception('Failure 1'));
        $this->circuitBreaker->recordFailure(new \Exception('Failure 2'));
        $this->assertEquals(2, $this->circuitBreaker->getFailureCount());

        // Un successo dovrebbe azzerare il contatore
        $this->circuitBreaker->recordSuccess();
        $this->assertEquals(0, $this->circuitBreaker->getFailureCount());
    }

    public function testResetForcesClosed(): void
    {
        // Apri il circuit breaker
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->execute(function() {
                    throw new \Exception('Test failure');
                });
            } catch (\Exception $e) {
                // Ignora
            }
        }

        $this->assertTrue($this->circuitBreaker->isOpen());

        // Reset dovrebbe riportarlo a closed
        $this->circuitBreaker->reset();
        $this->assertTrue($this->circuitBreaker->isClosed());
        $this->assertEquals(0, $this->circuitBreaker->getFailureCount());
    }

    public function testExecuteReturnsFunctionResult(): void
    {
        $expectedResult = ['data' => 'test', 'status' => 'ok'];
        
        $result = $this->circuitBreaker->execute(function() use ($expectedResult) {
            return $expectedResult;
        });

        $this->assertEquals($expectedResult, $result);
    }

    public function testRecordFailureIncrementsCounter(): void
    {
        $this->assertEquals(0, $this->circuitBreaker->getFailureCount());

        $this->circuitBreaker->recordFailure(new \Exception('Error 1'));
        $this->assertEquals(1, $this->circuitBreaker->getFailureCount());

        $this->circuitBreaker->recordFailure(new \Exception('Error 2'));
        $this->assertEquals(2, $this->circuitBreaker->getFailureCount());
    }
}
