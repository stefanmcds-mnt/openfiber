<?php
declare(strict_types=1);

namespace OpenFiber;

use OpenFiber\Interfaces\CircuitBreakerInterface;
use OpenFiber\Exception\ServiceUnavailableException;
use Exception;

/**
 * CircuitBreaker - Implementazione del pattern Circuit Breaker
 * 
 * Previene cascate di fallimenti bloccando temporaneamente le richieste
 * a servizi che stanno fallendo ripetutamente.
 * 
 * Funzionamento:
 * 1. CLOSED: Richieste passano normalmente. Fallimenti vengono contati.
 * 2. OPEN: Dopo N fallimenti, il breaker si apre. Richieste vengono bloccate.
 * 3. HALF_OPEN: Dopo un timeout, permette una richiesta di test.
 * 4. CLOSED/OPEN: In base al risultato del test, torna a CLOSED o OPEN.
 * 
 * Fase 2: Miglioramento della Gestione degli Errori ✓
 */
class CircuitBreaker implements CircuitBreakerInterface
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';
    
    private string $state = self::STATE_CLOSED;
    private int $failureCount = 0;
    private ?int $lastFailureTime = null;
    private ?int $openedAt = null;
    
    /**
     * Costruttore del Circuit Breaker
     * 
     * @param string $serviceName Nome del servizio protetto
     * @param int $failureThreshold Numero di fallimenti prima di aprire (default: 5)
     * @param int $resetTimeoutSeconds Secondi prima di tentare un reset (default: 60)
     * @param int $halfOpenMaxAttempts Tentativi massimi in half-open (default: 1)
     */
    public function __construct(
        private string $serviceName,
        private int $failureThreshold = 5,
        private int $resetTimeoutSeconds = 60,
        private int $halfOpenMaxAttempts = 1
    ) {
    }
    
    /**
     * {@inheritdoc}
     */
    public function isOpen(): bool
    {
        $this->updateState();
        return $this->state === self::STATE_OPEN;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isClosed(): bool
    {
        $this->updateState();
        return $this->state === self::STATE_CLOSED;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isHalfOpen(): bool
    {
        $this->updateState();
        return $this->state === self::STATE_HALF_OPEN;
    }
    
    /**
     * {@inheritdoc}
     */
    public function execute(callable $callback): mixed
    {
        $this->updateState();
        
        if ($this->state === self::STATE_OPEN) {
            $remainingTime = $this->getRemainingResetTime();
            throw new ServiceUnavailableException(
                $this->serviceName,
                $this->failureCount,
                $remainingTime,
                [
                    'state' => $this->state,
                    'opened_at' => $this->openedAt,
                ]
            );
        }
        
        try {
            $result = $callback();
            $this->recordSuccess();
            return $result;
        } catch (Exception $exception) {
            $this->recordFailure($exception);
            throw $exception;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function recordSuccess(): void
    {
        $this->failureCount = 0;
        $this->lastFailureTime = null;
        
        if ($this->state === self::STATE_HALF_OPEN) {
            $this->state = self::STATE_CLOSED;
            $this->openedAt = null;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function recordFailure(Exception $exception): void
    {
        $this->failureCount++;
        $this->lastFailureTime = time();
        
        if ($this->state === self::STATE_HALF_OPEN) {
            // Torna subito in OPEN se fallisce durante half-open
            $this->state = self::STATE_OPEN;
            $this->openedAt = time();
        } elseif ($this->failureCount >= $this->failureThreshold) {
            // Apre il breaker se si raggiunge la soglia
            $this->state = self::STATE_OPEN;
            $this->openedAt = time();
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
        $this->state = self::STATE_CLOSED;
        $this->failureCount = 0;
        $this->lastFailureTime = null;
        $this->openedAt = null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFailureCount(): int
    {
        return $this->failureCount;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getState(): string
    {
        $this->updateState();
        return $this->state;
    }
    
    /**
     * Aggiorna lo stato del circuit breaker in base al tempo trascorso
     * 
     * @return void
     */
    private function updateState(): void
    {
        if ($this->state === self::STATE_OPEN && $this->openedAt !== null) {
            $elapsedTime = time() - $this->openedAt;
            
            if ($elapsedTime >= $this->resetTimeoutSeconds) {
                // Passa a half-open per tentare un test
                $this->state = self::STATE_HALF_OPEN;
            }
        }
    }
    
    /**
     * Calcola il tempo rimanente prima del reset (in secondi)
     * 
     * @return int|null Secondi rimanenti o null se non applicabile
     */
    private function getRemainingResetTime(): ?int
    {
        if ($this->state !== self::STATE_OPEN || $this->openedAt === null) {
            return null;
        }
        
        $elapsedTime = time() - $this->openedAt;
        $remaining = $this->resetTimeoutSeconds - $elapsedTime;
        
        return max(0, $remaining);
    }
    
    /**
     * Restituisce il nome del servizio protetto
     * 
     * @return string Nome del servizio
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }
    
    /**
     * Restituisce la soglia di fallimenti configurata
     * 
     * @return int Soglia di fallimenti
     */
    public function getFailureThreshold(): int
    {
        return $this->failureThreshold;
    }
    
    /**
     * Restituisce il timeout di reset configurato
     * 
     * @return int Timeout in secondi
     */
    public function getResetTimeoutSeconds(): int
    {
        return $this->resetTimeoutSeconds;
    }
}
