<?php
declare(strict_types=1);

namespace OpenFiber\Exception;

use Exception;

/**
 * OpenFiberException - Eccezione base per tutte le eccezioni del dominio OpenFiber
 * 
 * Questa classe funge da eccezione base dalla quale derivano tutte le eccezioni
 * specifiche del dominio. Permette di catturare tutte le eccezioni OpenFiber con
 * un singolo catch block se necessario.
 * 
 */
class OpenFiberException extends Exception
{
    protected array $context = [];
    
    /**
     * Costruttore che supporta il contesto aggiuntivo per fornire informazioni
     * dettagliate sull'errore.
     * 
     * @param string $message Messaggio di errore descrittivo
     * @param int $code Codice di errore numerico
     * @param array $context Contesto aggiuntivo (es. parametri, stato dell'applicazione)
     * @param Exception|null $previous Eccezione precedente nella catena
     */
    public function __construct(
        ?string $message = "",
        ?int $code = 0,
        ?array $context = [],
        ?Exception $previous = null
    ) {
        parent::__construct(message:$message, code:$code, previous:$previous);
        $this->context = $context;
    }
    
    /**
     * Restituisce il contesto aggiuntivo associato all'eccezione
     * 
     * @return array Contesto con informazioni dettagliate sull'errore
     */
    public function getContext(): array
    {
        return $this->context;
    }
    
    /**
     * Imposta il contesto dell'eccezione
     * 
     * @param array $context Contesto da associare all'eccezione
     * @return self
     */
    public function setContext(?array $context): self
    {
        $this->context = $context;
        return $this;
    }
    
    /**
     * Restituisce una rappresentazione completa dell'errore includendo il contesto
     * 
     * @return string Rappresentazione dettagliata dell'errore
     */
    public function getDetailedMessage(): string
    {
        $message = $this->getMessage();
        
        if (!empty($this->context)) {
            $message .= "\nContesto: " . json_encode($this->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        return $message;
    }

    public function getSuggestions(): array
    {
        return $this->generateSuggestions();
    }
    
    protected function generateSuggestions(): array
    {
        return [
            'Check configuration settings',
            'Verify service availability',
            'Review logs for detailed error information',
            'Contact support if issue persists'
        ];
    }
    
    protected function addSpecificSuggestions(array $suggestions): array
    {
        return array_merge($this->generateSuggestions(), $suggestions);
    }    
}
