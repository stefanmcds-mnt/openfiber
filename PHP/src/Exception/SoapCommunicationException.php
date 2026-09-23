<?php
declare(strict_types=1);

namespace OpenFiber\Exception;

use SoapFault;

/**
 * SoapCommunicationException - Eccezione per errori di comunicazione SOAP
 * 
 * Questa eccezione viene lanciata quando si verificano errori durante la
 * comunicazione SOAP con il gateway di Open Fiber. Include informazioni
 * dettagliate sul fault SOAP e il contesto della richiesta.
 * 
 */
class SoapCommunicationException extends OpenFiberException
{
    private ?string $soapFaultCode = '';
    private ?string $soapFaultString = '';
    private ?string $requestXml = null;
    private ?string $responseXml = null;
    
    /**
     * Crea un'eccezione SOAP con informazioni dettagliate
     * 
     * @param string $message Messaggio di errore descrittivo
     * @param string $soapFaultCode Codice del fault SOAP
     * @param string $soapFaultString Stringa del fault SOAP
     * @param array $context Contesto aggiuntivo (service_name, codice_ordine, ecc.)
     * @param int $code Codice di errore numerico
     * @param SoapFault|null $previous SoapFault originale
     */
    public function __construct(
        ?string $message,
        ?string $soapFaultCode = '',
        ?string $soapFaultString = '',
        ?array $context = [],
        ?int $code = 0,
        ?SoapFault $previous = null
    ) {
        parent::__construct($message, $code, $context, $previous);
        $this->soapFaultCode = $soapFaultCode;
        $this->soapFaultString = $soapFaultString;
    }
    
    /**
     * Crea un'eccezione da un SoapFault
     * 
     * @param SoapFault $soapFault Il SoapFault originale
     * @param array $context Contesto aggiuntivo
     * @return self
     */
    public static function fromSoapFault(?SoapFault $soapFault, ?array $context = []): self
    {
        return new self(
            "Errore di comunicazione SOAP: " . $soapFault->getMessage(),
            $soapFault->faultcode ?? '',
            $soapFault->faultstring ?? $soapFault->getMessage(),
            $context,
            (int)$soapFault->getCode(),
            $soapFault
        );
    }
    
    /**
     * Restituisce il codice del fault SOAP
     * 
     * @return string Codice del fault
     */
    public function getSoapFaultCode(): string
    {
        return $this->soapFaultCode;
    }
    
    /**
     * Restituisce la stringa del fault SOAP
     * 
     * @return string Descrizione del fault
     */
    public function getSoapFaultString(): string
    {
        return $this->soapFaultString;
    }
    
    /**
     * Imposta l'XML della richiesta SOAP
     * 
     * @param string $requestXml XML della richiesta
     * @return self
     */
    public function setRequestXml(?string $requestXml): self
    {
        $this->requestXml = $requestXml;
        return $this;
    }
    
    /**
     * Restituisce l'XML della richiesta SOAP
     * 
     * @return string|null XML della richiesta o null se non disponibile
     */
    public function getRequestXml(): ?string
    {
        return $this->requestXml;
    }
    
    /**
     * Imposta l'XML della risposta SOAP
     * 
     * @param string $responseXml XML della risposta
     * @return self
     */
    public function setResponseXml(?string $responseXml): self
    {
        $this->responseXml = $responseXml;
        return $this;
    }
    
    /**
     * Restituisce l'XML della risposta SOAP
     * 
     * @return string|null XML della risposta o null se non disponibile
     */
    public function getResponseXml(): ?string
    {
        return $this->responseXml;
    }
    
    /**
     * Verifica se l'errore è recuperabile (timeout, connessione, ecc.)
     * 
     * @return bool True se l'errore è recuperabile
     */
    public function isRecoverable(): bool
    {
        $recoverablePatterns = [
            'timeout',
            'timed out',
            'connection refused',
            'could not connect',
            'network',
            'temporarily unavailable'
        ];
        
        $message = strtolower($this->getMessage() . ' ' . $this->soapFaultString);
        
        foreach ($recoverablePatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }
        
        return false;
    }
}
