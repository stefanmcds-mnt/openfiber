<?php
declare(strict_types=1);

namespace OpenFiber\Interfaces;

use OpenFiber\Exception\ServiceUnavailableException;

/**
 * CircuitBreakerInterface - Interfaccia per implementazioni di Circuit Breaker
 * 
 * Il Circuit Breaker è un pattern di resilienza che previene cascate di fallimenti
 * bloccando temporaneamente le richieste a un servizio che sta fallendo ripetutamente.
 * 
 * Stati del Circuit Breaker:
 * - CLOSED: Tutto normale, le richieste passano
 * - OPEN: Troppe fallimenti, le richieste vengono bloccate
 * - HALF_OPEN: Test per vedere se il servizio si è ripreso
 * 
 */
interface CircuitBreakerInterface
{
    /**
     * Verifica se il circuit breaker è aperto
     * 
     * @return bool True se è aperto (richieste bloccate)
     */
    public function isOpen(): bool;
    
    /**
     * Verifica se il circuit breaker è chiuso
     * 
     * @return bool True se è chiuso (richieste consentite)
     */
    public function isClosed(): bool;
    
    /**
     * Verifica se il circuit breaker è in stato half-open
     * 
     * @return bool True se è in half-open (modalità test)
     */
    public function isHalfOpen(): bool;
    
    /**
     * Esegue un'operazione attraverso il circuit breaker
     * 
     * @param callable $callback Funzione da eseguire
     * @return mixed Risultato dell'operazione
     * @throws ServiceUnavailableException Se il circuit breaker è aperto
     */
    public function execute(callable $callback): mixed;
    
    /**
     * Registra un successo
     * 
     * @return void
     */
    public function recordSuccess(): void;
    
    /**
     * Registra un fallimento
     * 
     * @param \Exception $exception Eccezione ricevuta
     * @return void
     */
    public function recordFailure(\Exception $exception): void;
    
    /**
     * Forza il reset del circuit breaker allo stato chiuso
     * 
     * @return void
     */
    public function reset(): void;
    
    /**
     * Restituisce il numero di fallimenti consecutivi
     * 
     * @return int Numero di fallimenti
     */
    public function getFailureCount(): int;
    
    /**
     * Restituisce lo stato corrente del circuit breaker
     * 
     * @return string Stato (open, closed, half_open)
     */
    public function getState(): string;
}
