<?php
declare(strict_types=1);

namespace OpenFiber\Exception;

/**
 * WsdlNotFoundException - Eccezione quando il WSDL non viene trovato
 * 
 * Questa eccezione viene lanciata quando il WSDL richiesto per un servizio
 * non può essere recuperato né dallo storage né scaricato dall'URL remoto.
 * 
 */
class WsdlNotFoundException extends OpenFiberException
{
    private ?string $serviceName = '';
    private ?string $wsdlUrl = null;
    
    /**
     * Crea un'eccezione per WSDL non trovato
     * 
     * @param string $serviceName Nome del servizio
     * @param string|null $wsdlUrl URL del WSDL (se disponibile)
     * @param array $context Contesto aggiuntivo
     * @param int $code Codice di errore
     */
    public function __construct(
        ?string $serviceName,
        ?string $wsdlUrl = null,
        ?array $context = [],
        ?int $code = 0
    ) {
        $message = "WSDL non trovato per il servizio: {$serviceName}";
        
        if ($wsdlUrl) {
            $message .= " (URL: {$wsdlUrl})";
        }
        
        parent::__construct($message, $code, $context);
        $this->serviceName = $serviceName;
        $this->wsdlUrl = $wsdlUrl;
    }
    
    /**
     * Crea un'eccezione per WSDL non trovato nello storage
     * 
     * @param string $serviceName Nome del servizio
     * @param string $storageType Tipo di storage (database, file, memory)
     * @return self
     */
    public static function notFoundInStorage(?string $serviceName, ?string $storageType = 'database'): self
    {
        return new self(
            $serviceName,
            null,
            ['storage_type' => $storageType, 'reason' => 'not_in_storage']
        );
    }
    
    /**
     * Crea un'eccezione per errore durante il download del WSDL
     * 
     * @param string $serviceName Nome del servizio
     * @param string $wsdlUrl URL del WSDL
     * @param string $reason Motivo del fallimento
     * @return self
     */
    public static function downloadFailed(?string $serviceName, ?string $wsdlUrl, ?string $reason = ''): self
    {
        return new self(
            $serviceName,
            $wsdlUrl,
            ['reason' => 'download_failed', 'details' => $reason]
        );
    }
    
    /**
     * Restituisce il nome del servizio
     * 
     * @return string Nome del servizio
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }
    
    /**
     * Restituisce l'URL del WSDL
     * 
     * @return string|null URL del WSDL o null se non disponibile
     */
    public function getWsdlUrl(): ?string
    {
        return $this->wsdlUrl;
    }
    
    /**
     * Fornisce suggerimenti per risolvere il problema
     * 
     * @return array Suggerimenti per la risoluzione
     */
    public function getSuggestions(): array
    {
        return [
            "Verificare che il servizio '{$this->serviceName}' sia configurato correttamente",
            "Controllare la connessione di rete se si tenta di scaricare il WSDL",
            "Verificare che il WSDL sia presente nello storage configurato",
            "Consultare la documentazione di Open Fiber per l'URL corretto del WSDL",
        ];
    }
}
