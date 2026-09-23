<?php
/**
 * Esempio 2: Configurazione Avanzata con Retry
 * 
 * Questo esempio mostra come configurare il sistema di retry con politiche
 * personalizzate e backoff esponenziale per gestire errori temporanei.
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
use OpenFiber\Exception\RetryLimitExceededException;

// ====================================================================
// CONFIGURAZIONE STORAGE
// ====================================================================

$storageDir = __DIR__ . '/../storage';
$wsdlStorage = new FileWsdlStorage($storageDir . '/wsdl');
$logStorage = new FileSoapLogStorage($storageDir . '/logs');
$retryStorage = new FileRetryQueueStorage($storageDir . '/retry');

// ====================================================================
// CONFIGURA RETRY POLICY CON BACKOFF ESPONENZIALE
// ====================================================================

echo "🔧 Configurazione Retry Policy Avanzata\n";
echo "======================================\n\n";

$retryPolicy = new RetryPolicy(
    retryStorage: $retryStorage,
    maxAttempts: 5,              // Massimo 5 tentativi
    retryableErrors: [
        'Server.Timeout',         // Timeout del server
        'Server.Unavailable',     // Server non disponibile
        'Network.ConnectionLost', // Connessione persa
        'HTTP.502',              // Bad Gateway
        'HTTP.503',              // Service Unavailable
    ]
);

echo "✓ Tentativi massimi: 5\n";
echo "✓ Errori recuperabili configurati: 5 tipi\n";
echo "✓ Strategia: Backoff esponenziale\n\n";

// ====================================================================
// LOGGER CONFIGURATO
// ====================================================================

$logger = new SoapLogger($logStorage);

echo "✓ Logger configurato per: {$storageDir}/logs\n\n";

// ====================================================================
// DTO DI ESEMPIO
// ====================================================================

$testDto = new class implements DynamicDtoInterface {
    public function __construct(
        public string $orderId = 'ORD-RETRY-TEST-001',
        public string $operation = 'check_availability'
    ) {}
    
    public function toSoapPayload(): array
    {
        return [
            'CODICE_ORDINE_OLO' => $this->orderId,
            'OPERAZIONE' => $this->operation,
            'TIMESTAMP' => date('Y-m-d H:i:s'),
        ];
    }
    
    public function validate(): bool
    {
        return !empty($this->orderId);
    }
};

// ====================================================================
// SIMULAZIONE DI RETRY
// ====================================================================

try {
    $client = new DynamicSoapClient(
        serviceName: 'availability_service',
        wsdlStorage: $wsdlStorage,
        logger: $logger,
        retryPolicy: $retryPolicy,
        dynamicDto: $testDto
    );
    
    echo "🚀 Avvio richiesta con retry automatico...\n";
    echo "   Order ID: {$testDto->orderId}\n";
    echo "   Operazione: {$testDto->operation}\n\n";
    
    // Questa chiamata potrebbe fallire e essere ritentata automaticamente
    $response = $client->send();
    
    echo "✅ Richiesta completata!\n";
    echo "Risposta ricevuta.\n\n";
    
} catch (RetryLimitExceededException $e) {
    echo "❌ Limite di tentativi raggiunto!\n";
    echo "   Tentativi effettuati: {$e->getAttemptsMade()}\n";
    echo "   Ultimo errore: {$e->getLastError()}\n";
    echo "   Suggerimento: {$e->getSuggestion()}\n\n";
    
    echo "💡 La richiesta è stata accodata per un retry successivo.\n";
    echo "   Esegui il worker di retry per elaborarla:\n";
    echo "   php worker/worker.php\n\n";
    
} catch (Exception $e) {
    echo "❌ Errore: {$e->getMessage()}\n\n";
}

// ====================================================================
// VISUALIZZA STATISTICHE RETRY
// ====================================================================

echo "📊 Statistiche Retry Queue\n";
echo "=========================\n";

// Simula lettura delle statistiche (in una implementazione reale)
$pendingRetries = 0; // Questo valore verrebbe letto dallo storage

echo "Retry in coda: {$pendingRetries}\n";
echo "Directory retry: {$storageDir}/retry\n\n";

// ====================================================================
// GESTIONE MANUALE DELLA CODA
// ====================================================================

echo "🔄 Gestione Manuale Retry Queue\n";
echo "===============================\n\n";

// Esempio di accodamento manuale
$manualRetryData = [
    'service_name' => 'manual_test_service',
    'payload' => ['test' => 'data'],
    'attempt' => 1,
    'max_attempts' => 3,
    'last_error' => 'Connection timeout',
    'created_at' => date('Y-m-d H:i:s'),
];

if ($retryPolicy->shouldRetry('Server.Timeout')) {
    echo "✓ L'errore 'Server.Timeout' è recuperabile\n";
    echo "✓ Verrà ritentato automaticamente\n";
} else {
    echo "✗ Errore non recuperabile, non verrà ritentato\n";
}

echo "\n✨ Esempio completato!\n";
echo "\n💡 Suggerimenti:\n";
echo "   1. Configura il worker di retry per elaborazione background\n";
echo "   2. Monitora i log in: {$storageDir}/logs\n";
echo "   3. Controlla la coda in: {$storageDir}/retry\n";
