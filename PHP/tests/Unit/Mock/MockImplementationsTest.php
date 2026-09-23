<?php

declare(strict_types=1);

namespace OpenFiber\Tests\Unit\Mock;

use PHPUnit\Framework\TestCase;
use OpenFiber\Tests\Mock\MockWsdlStorage;
use OpenFiber\Tests\Mock\MockSoapCommunicator;
use OpenFiber\Tests\Mock\MockLogger;
use OpenFiber\Tests\Mock\MockRetryPolicy;
use OpenFiber\Tests\Mock\MockCircuitBreaker;
use OpenFiber\Exception\SoapCommunicationException;
use OpenFiber\Exception\ServiceUnavailableException;

/**
 * Test per le implementazioni Mock
 * 
 * Verifica che tutti i mock funzionino correttamente e implementino
 * le interfacce richieste in modo verificabile
 * 
 * Fase 3: Testabilità e Mockabilità ✓
 */
class MockImplementationsTest extends TestCase
{
    public function testMockWsdlStorageFunctionality(): void
    {
        $storage = new MockWsdlStorage();
        
        // Test salvataggio e recupero
        $storage->saveWsdl('test', 'http://url.com', 'content');
        $this->assertEquals('content', $storage->getWsdl('test'));
        
        // Test helper methods
        $this->assertTrue($storage->hasWsdl('test'));
        $this->assertFalse($storage->hasWsdl('nonexistent'));
        
        // Test failure mode
        $storage->setShouldFail(true);
        $this->assertNull($storage->getWsdl('test'));
        $this->assertFalse($storage->saveWsdl('new', 'url', 'content'));
    }

    public function testMockSoapCommunicatorFunctionality(): void
    {
        $communicator = new MockSoapCommunicator();
        
        // Test successful call
        $response = $communicator->send('test_service', ['key' => 'value']);
        $this->assertIsString($response);
        $this->assertEquals(1, $communicator->getCallCount());
        
        // Test call tracking
        $this->assertTrue($communicator->wasCalled('test_service'));
        $this->assertFalse($communicator->wasCalled('other_service'));
        
        // Test failure mode
        $communicator->setShouldFail(true);
        $this->expectException(SoapCommunicationException::class);
        $communicator->send('failing_service', []);
    }

    public function testMockLoggerFunctionality(): void
    {
        $logger = new MockLogger();
        $mockDto = $this->createMockDto('test_service');
        
        // Test success logging
        $logger->logSuccess('service1', $mockDto, 'response1');
        $this->assertEquals(1, $logger->getLogCount());
        $this->assertEquals(1, count($logger->getSuccessLogs()));
        
        // Test failure logging
        $logger->logFailure('service2', $mockDto, new \Exception('Error'));
        $this->assertEquals(2, $logger->getLogCount());
        $this->assertEquals(1, count($logger->getErrorLogs()));
        
        // Test service tracking
        $this->assertTrue($logger->hasLogForService('service1'));
        $this->assertTrue($logger->hasLogForService('service2'));
    }

    public function testMockRetryPolicyFunctionality(): void
    {
        $policy = new MockRetryPolicy();
        
        // Test shouldRetry
        $this->assertFalse($policy->shouldRetry(new \Exception('test')));
        $policy->setShouldRetry(true);
        $this->assertTrue($policy->shouldRetry(new \Exception('test')));
        
        // Test queueing
        $policy->queueForRetry('service', 'code123', 'notif456', ['data'], new \Exception('error'));
        $this->assertEquals(1, $policy->getQueueCount());
        $this->assertTrue($policy->hasQueuedService('service'));
    }

    public function testMockCircuitBreakerFunctionality(): void
    {
        $cb = new MockCircuitBreaker();
        
        // Test initial state
        $this->assertTrue($cb->isClosed());
        $this->assertFalse($cb->isOpen());
        $this->assertEquals('closed', $cb->getState());
        
        // Test state changes
        $cb->setState('open');
        $this->assertTrue($cb->isOpen());
        $this->assertFalse($cb->isClosed());
        
        // Test execute with open circuit breaker
        $cb->setForceOpen(true);
        $this->expectException(ServiceUnavailableException::class);
        $cb->execute(fn() => 'test');
    }

    public function testMockCircuitBreakerExecuteSuccess(): void
    {
        $cb = new MockCircuitBreaker();
        
        $result = $cb->execute(function() {
            return 'success_result';
        });
        
        $this->assertEquals('success_result', $result);
        $this->assertEquals(1, count($cb->getCalls()));
        $this->assertEquals(0, $cb->getFailureCount());
    }

    public function testMockCircuitBreakerExecuteFailure(): void
    {
        $cb = new MockCircuitBreaker();
        
        try {
            $cb->execute(function() {
                throw new \Exception('Test failure');
            });
            $this->fail('Should have thrown exception');
        } catch (\Exception $e) {
            $this->assertEquals('Test failure', $e->getMessage());
            $this->assertEquals(1, $cb->getFailureCount());
        }
    }

    private function createMockDto(string $serviceName): object
    {
        return new class($serviceName) implements \OpenFiber\Interfaces\DynamicDtoInterface {
            public function __construct(private string $serviceName) {}
            public function toSoapPayload(): array { return ['service' => $this->serviceName]; }
        };
    }
}
