<?php
declare(strict_types=1);

namespace OpenFiber\Exception;

/**
 * ConfigurationException - Eccezione per errori di configurazione
 * 
 * Questa eccezione viene lanciata quando si verificano problemi con la
 * configurazione dell'applicazione (parametri mancanti, valori non validi, ecc.).
 * 
 */
class ConfigurationException extends OpenFiberException
{
    //private ?string $configKey = '';
    //private mixed $invalidValue = null;
    
    /**
     * Crea un'eccezione di configurazione
     * 
     * @param string $message Messaggio di errore
     * @param string $configKey Chiave di configurazione problematica
     * @param mixed $invalidValue Valore non valido (se applicabile)
     * @param array $context Contesto aggiuntivo
     * @param int $code Codice di errore
     */
    public function __construct(
        public ?string $message,
        private ?string $configKey = '',
        private mixed $invalidValue = null,
        ?array $context = [],
        public ?int $code = 0
    ) {
        parent::__construct($message, $code, $context);
        //$this->configKey = $configKey;
        //$this->invalidValue = $invalidValue;
    }
    
    /**
     * Crea un'eccezione per un parametro mancante
     * 
     * @param string $configKey Chiave di configurazione mancante
     * @param array $context Contesto aggiuntivo
     * @return self
     */
    public static function missingParameter(?string $configKey, ?array $context = []): ?self
    {
        return new self(
            "Parametro di configurazione mancante: {$configKey}",
            $configKey,
            null,
            $context
        );
    }
    
    /**
     * Crea un'eccezione per un valore non valido
     * 
     * @param string $configKey Chiave di configurazione
     * @param mixed $invalidValue Valore non valido
     * @param string $expectedType Tipo atteso (opzionale)
     * @param array $context Contesto aggiuntivo
     * @return self
     */
    public static function invalidValue(?string $configKey,mixed $invalidValue,?string $expectedType = '',?array $context = []): ?self 
    {
        $message = "Valore non valido per '{$configKey}': " . json_encode($invalidValue);
        
        if ($expectedType) {
            $message .= " (atteso: {$expectedType})";
        }
        
        return new self($message, $configKey, $invalidValue, $context);
    }
    
    /**
     * Restituisce la chiave di configurazione problematica
     * 
     * @return string Chiave di configurazione
     */
    public function getConfigKey(): string
    {
        return $this->configKey;
    }
    
    /**
     * Restituisce il valore non valido
     * 
     * @return mixed Valore non valido o null
     */
    public function getInvalidValue(): mixed
    {
        return $this->invalidValue;
    }
}
