<?php
/**
 * Esempio 1: Client Base
 * 
 * Questo esempio mostra come configurare e utilizzare il client SOAP base
 * per comunicare con il Gateway di Open Fiber.
 * 
 * @package OpenFiber\Examples
 * @author OpenFiber Team
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use OpenFiber\DynamicSoapClient;
use OpenFiber\Storage\FileWsdlStorage;
use OpenFiber\SoapLogger;
use OpenFiber\RetryPolicy;
use OpenFiber\Storage\FileSoapLogStorage;
use OpenFiber\Storage\FileRetryQueueStorage;
use OpenFiber\Interfaces\DynamicDtoInterface;
use OpenFiber\Exception\SoapCommunicationException;
use OpenFiber\Exception\ConfigurationException;

// ====================================================================
// CONFIGURAZIONE
// ====================================================================

// Definisci le directory per lo storage
$storageDir = __DIR__ . '/../storage';
$wsdlDir = $storageDir . '/wsdl';
$logsDir = $storageDir . '/logs';
$retryDir = $storageDir . '/retry';

// Crea le directory se non esistono
foreach ([$storageDir, $wsdlDir, $logsDir, $retryDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ====================================================================
// CONFIGURA GLI STORAGE
// ====================================================================

$wsdlStorage = new FileWsdlStorage($wsdlDir);
$logStorage = new FileSoapLogStorage($logsDir);
$retryStorage = new FileRetryQueueStorage($retryDir);

// ====================================================================
// CONFIGURA LOGGER E RETRY POLICY
// ====================================================================

$logger = new SoapLogger($logStorage);
$retryPolicy = new RetryPolicy(
    retryStorage: $retryStorage,
    maxAttempts: 3,
    retryableErrors: ['Server.Timeout', 'Server.Unavailable']
);

// ====================================================================
// PREPARA IL DTO (Data Transfer Object)
// ====================================================================

// Crea un DTO anonimo con property hooks (PHP 8.4)
$activationDto = new class implements DynamicDtoInterface {
    public function __construct(
        public string $orderId = 'ORD-12345',
        public string $customerCode = 'CUST-001',
        public string $serviceType = 'FTTH',
        public string $address = 'Via Roma 1, Milano'
    ) {
        // Property hooks per validazione automatica
        $this->orderId = trim($orderId);
        if (empty($this->orderId)) {
            throw new InvalidArgumentException('Order ID cannot be empty');
        }
    }
    
    public function toSoapPayload(): array
    {
        return [
            'CODICE_ORDINE_OLO' => $this->orderId,
            'CODICE_CLIENTE' => $this->customerCode,
            'TIPO_SERVIZIO' => $this->serviceType,
            'INDIRIZZO' => $this->address,
        ];
    }
    
    public function validate(): bool
    {
        return !empty($this->orderId) && !empty($this->customerCode);
    }
};

// ====================================================================
// CREA ED UTILIZZA IL CLIENT
// ====================================================================

try {
    // Inizializza il client SOAP
    $client = new DynamicSoapClient(
        serviceName: 'activation_service',
        wsdlStorage: $wsdlStorage,
        logger: $logger,
        retryPolicy: $retryPolicy,
        dynamicDto: $activationDto
    );
    
    echo "🚀 Invio richiesta di attivazione...\n";
    echo "   Order ID: {$activationDto->orderId}\n";
    echo "   Customer: {$activationDto->customerCode}\n";
    echo "   Service: {$activationDto->serviceType}\n\n";
    
    // Invia la richiesta
    $response = $client->send();
    
    echo "✅ Attivazione completata con successo!\n";
    echo "Risposta: " . print_r($response, true) . "\n";
    
} catch (SoapCommunicationException $e) {
    echo "❌ Errore di comunicazione SOAP:\n";
    echo "   Messaggio: {$e->getMessage()}\n";
    echo "   Fault Code: {$e->getFaultCode()}\n";
    echo "   Suggerimento: {$e->getSuggestion()}\n";
    exit(1);
    
} catch (ConfigurationException $e) {
    echo "❌ Errore di configurazione:\n";
    echo "   Messaggio: {$e->getMessage()}\n";
    echo "   Parametro mancante: {$e->getMissingParameter()}\n";
    echo "   Suggerimento: {$e->getSuggestion()}\n";
    exit(1);
    
} catch (Exception $e) {
    echo "❌ Errore generico:\n";
    echo "   Messaggio: {$e->getMessage()}\n";
    exit(1);
}

echo "\n✨ Esempio completato!\n";
