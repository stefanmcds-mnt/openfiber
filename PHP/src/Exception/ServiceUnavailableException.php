<?php
declare(strict_types=1);

namespace OpenFiber\Exception;

/**
 * ServiceUnavailableException - Eccezione quando il servizio non è disponibile (Circuit Breaker aperto)
 * 
 * Questa eccezione viene lanciata quando il Circuit Breaker è aperto e
 * le richieste vengono bloccate per prevenire ulteriori fallimenti.
 * 
 */
class ServiceUnavailableException extends OpenFiberException
{
    private ?string $serviceName = '';
    private ?int $failureCount = 0;
    private ?int $resetTimeSeconds = null;
    
    /**
     * Crea un'eccezione per servizio non disponibile
     * 
     * @param string $serviceName Nome del servizio non disponibile
     * @param int $failureCount Numero di fallimenti consecutivi
     * @param int|null $resetTimeSeconds Secondi prima del reset del circuit breaker
     * @param array $context Contesto aggiuntivo
     * @param int $code Codice di errore
     */
    public function __construct(
        ?string $serviceName,
        ?int $failureCount = 0,
        ?int $resetTimeSeconds = null,
        ?array $context = [],
        ?int $code = 503
    ) {
        $message = "Servizio '{$serviceName}' temporaneamente non disponibile (Circuit Breaker APERTO)";
        
        if ($failureCount > 0) {
            $message .= ". Fallimenti consecutivi: {$failureCount}";
        }
        
        if ($resetTimeSeconds !== null) {
            $message .= ". Riprova tra {$resetTimeSeconds} secondi";
        }
        
        parent::__construct($message, $code, $context);
        $this->serviceName = $serviceName;
        $this->failureCount = $failureCount;
        $this->resetTimeSeconds = $resetTimeSeconds;
    }
    
    /**
     * Restituisce il nome del servizio non disponibile
     * 
     * @return string Nome del servizio
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }
    
    /**
     * Restituisce il numero di fallimenti consecutivi
     * 
     * @return int Numero di fallimenti
     */
    public function getFailureCount(): int
    {
        return $this->failureCount;
    }
    
    /**
     * Restituisce i secondi prima del reset del circuit breaker
     * 
     * @return int|null Secondi o null se non disponibile
     */
    public function getResetTimeSeconds(): ?int
    {
        return $this->resetTimeSeconds;
    }
    
    /**
     * Calcola il timestamp stimato per il reset del circuit breaker
     * 
     * @return int|null Timestamp Unix del reset o null se non disponibile
     */
    public function getEstimatedResetTimestamp(): ?int
    {
        if ($this->resetTimeSeconds === null) {
            return null;
        }
        
        return time() + $this->resetTimeSeconds;
    }
    
    /**
     * Fornisce suggerimenti per risolvere il problema
     * 
     * @return array Suggerimenti per la risoluzione
     */
    public function getSuggestions(): array
    {
        $suggestions = [
            "Attendere che il Circuit Breaker si resetti automaticamente",
            "Verificare lo stato del servizio '{$this->serviceName}'",
            "Controllare i log per identificare la causa dei fallimenti ripetuti",
        ];
        
        if ($this->resetTimeSeconds) {
            $suggestions[] = "Ritentare tra circa {$this->resetTimeSeconds} secondi";
        }
        
        return $suggestions;
    }
}
