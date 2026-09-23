<?php
declare(strict_types=1);

namespace OpenFiber\Exception;

/**
 * RetryLimitExceededException - Eccezione quando i tentativi di retry sono esauriti
 * 
 * Questa eccezione viene lanciata quando il numero massimo di tentativi di retry
 * è stato raggiunto senza successo.
 * 
 */
class RetryLimitExceededException extends OpenFiberException
{
    private int $maxAttempts = 0;
    private int $actualAttempts = 0;
    private ?OpenFiberException $lastException = null;
    
    /**
     * Crea un'eccezione per limite retry superato
     * 
     * @param int $maxAttempts Numero massimo di tentativi consentiti
     * @param int $actualAttempts Numero effettivo di tentativi eseguiti
     * @param OpenFiberException|null $lastException Ultima eccezione ricevuta
     * @param array $context Contesto aggiuntivo
     * @param int $code Codice di errore
     */
    public function __construct(
        ?int $maxAttempts,
        ?int $actualAttempts,
        ?OpenFiberException $lastException = null,
        ?array $context = [],
        ?int $code = 0
    ) {
        $message = "Limite di tentativi raggiunto: {$actualAttempts}/{$maxAttempts} tentativi falliti";
        
        if ($lastException) {
            $message .= ". Ultimo errore: " . $lastException->getMessage();
        }
        
        parent::__construct($message, $code, $context, $lastException);
        $this->maxAttempts = $maxAttempts;
        $this->actualAttempts = $actualAttempts;
        $this->lastException = $lastException;
    }
    
    /**
     * Restituisce il numero massimo di tentativi
     * 
     * @return int Numero massimo di tentativi
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }
    
    /**
     * Restituisce il numero effettivo di tentativi eseguiti
     * 
     * @return int Numero di tentativi eseguiti
     */
    public function getActualAttempts(): int
    {
        return $this->actualAttempts;
    }
    
    /**
     * Restituisce l'ultima eccezione ricevuta
     * 
     * @return OpenFiberException|null Ultima eccezione o null
     */
    public function getLastException(): ?OpenFiberException
    {
        return $this->lastException;
    }
    
    /**
     * Verifica se l'operazione dovrebbe essere ritentata manualmente
     * 
     * @return bool True se vale la pena ritentare manualmente
     */
    public function shouldRetryManually(): bool
    {
        // Se l'ultimo errore era recuperabile, potrebbe valere la pena ritentare
        if ($this->lastException instanceof SoapCommunicationException) {
            return $this->lastException->isRecoverable();
        }
        
        return false;
    }
    
    /**
     * Fornisce suggerimenti per risolvere il problema
     * 
     * @return array Suggerimenti per la risoluzione
     */
    public function getSuggestions(): array
    {
        $suggestions = [
            "Verificare la stabilità della connessione di rete",
            "Controllare lo stato del gateway Open Fiber",
            "Analizzare i log per identificare pattern negli errori",
        ];
        
        if ($this->shouldRetryManually()) {
            $suggestions[] = "L'errore sembra temporaneo: considera un retry manuale dopo qualche minuto";
        } else {
            $suggestions[] = "L'errore potrebbe richiedere intervento manuale o correzioni al payload";
        }
        
        return $suggestions;
    }
}
