<?php
/**
 * Esempio 3: Circuit Breaker Pattern
 * 
 * Questo esempio mostra come utilizzare il Circuit Breaker per prevenire
 * cascate di fallimenti quando un servizio esterno non è disponibile.
 * 
 * @package OpenFiber\Examples
 * @author OpenFiber Team
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use OpenFiber\DynamicSoapClient;
use OpenFiber\CircuitBreaker;
use OpenFiber\Storage\FileWsdlStorage;
use OpenFiber\SoapLogger;
use OpenFiber\RetryPolicy;
use OpenFiber\Storage\FileSoapLogStorage;
use OpenFiber\Storage\FileRetryQueueStorage;
use OpenFiber\Interfaces\DynamicDtoInterface;
use OpenFiber\Exception\ServiceUnavailableException;

// ====================================================================
// CONFIGURAZIONE
// ====================================================================

echo "🔌 Circuit Breaker Pattern Example\n";
echo "==================================\n\n";

$storageDir = __DIR__ . '/../storage';

// ====================================================================
// CONFIGURA CIRCUIT BREAKER
// ====================================================================

$circuitBreaker = new CircuitBreaker(
    failureThreshold: 3,        // Apre dopo 3 fallimenti consecutivi
    timeoutSeconds: 30,         // Resetta dopo 30 secondi
    successThreshold: 2         // Richiede 2 successi per chiudere
);

echo "Circuit Breaker configurato:\n";
echo "  • Soglia fallimenti: 3\n";
echo "  • Timeout reset: 30 secondi\n";
echo "  • Soglia successi: 2\n";
echo "  • Stato iniziale: CLOSED\n\n";

// ====================================================================
// CONFIGURA CLIENT
// ====================================================================

$wsdlStorage = new FileWsdlStorage($storageDir . '/wsdl');
$logStorage = new FileSoapLogStorage($storageDir . '/logs');
$retryStorage = new FileRetryQueueStorage($storageDir . '/retry');
$logger = new SoapLogger($logStorage);
$retryPolicy = new RetryPolicy($retryStorage);

// ====================================================================
// DTO DI TEST
// ====================================================================

$testDto = new class implements DynamicDtoInterface {
    public function __construct(
        public int $callNumber = 1
    ) {}
    
    public function toSoapPayload(): array
    {
        return [
            'CALL_NUMBER' => $this->callNumber,
            'TIMESTAMP' => microtime(true),
        ];
    }
    
    public function validate(): bool
    {
        return true;
    }
};

// ====================================================================
// SIMULAZIONE MULTIPLE CHIAMATE CON CIRCUIT BREAKER
// ====================================================================

echo "🧪 Simulazione chiamate multiple\n";
echo "================================\n\n";

$totalCalls = 8;
$successCount = 0;
$failureCount = 0;
$blockedCount = 0;

for ($i = 1; $i <= $totalCalls; $i++) {
    echo "Chiamata #{$i}:\n";
    
    try {
        // Verifica lo stato del circuit breaker PRIMA della chiamata
        if (!$circuitBreaker->isAvailable()) {
            echo "  ⛔ Circuit breaker OPEN - Chiamata bloccata\n";
            echo "  💡 Il servizio verrà ritentato automaticamente dopo il timeout\n";
            $blockedCount++;
            echo "\n";
            continue;
        }
        
        echo "  ✓ Circuit breaker CLOSED - Tentativo chiamata\n";
        
        // Simula chiamata al servizio
        // In una implementazione reale, qui ci sarebbe la chiamata SOAP
        $simulateFailure = ($i >= 3 && $i <= 5); // Simula fallimenti su chiamate 3, 4, 5
        
        if ($simulateFailure) {
            // Simula errore
            $circuitBreaker->recordFailure();
            $failureCount++;
            echo "  ❌ Chiamata fallita!\n";
            echo "  📊 Failure count: {$circuitBreaker->getFailureCount()}\n";
        } else {
            // Simula successo
            $circuitBreaker->recordSuccess();
            $successCount++;
            echo "  ✅ Chiamata riuscita!\n";
        }
        
    } catch (ServiceUnavailableException $e) {
        echo "  ⛔ Servizio non disponibile\n";
        echo "  Messaggio: {$e->getMessage()}\n";
        $blockedCount++;
    }
    
    // Mostra stato corrente
    echo "  📍 Stato: " . $circuitBreaker->getState() . "\n";
    echo "\n";
    
    // Piccolo delay tra le chiamate
    usleep(100000); // 0.1 secondi
}

// ====================================================================
// STATISTICHE FINALI
// ====================================================================

echo "📊 Statistiche Finali\n";
echo "====================\n";
echo "Totale chiamate tentate: {$totalCalls}\n";
echo "Successi: {$successCount}\n";
echo "Fallimenti: {$failureCount}\n";
echo "Bloccate dal Circuit Breaker: {$blockedCount}\n";
echo "Stato finale Circuit Breaker: " . $circuitBreaker->getState() . "\n\n";

// ====================================================================
// TEST RESET AUTOMATICO
// ====================================================================

echo "⏱️  Test Reset Automatico\n";
echo "========================\n";
echo "Il circuit breaker si resetterà automaticamente dopo 30 secondi\n";
echo "e permetterà nuovi tentativi in modalità HALF_OPEN.\n\n";

echo "💡 Vantaggi del Circuit Breaker:\n";
echo "   1. Previene cascate di fallimenti\n";
echo "   2. Risposta immediata quando il servizio è down\n";
echo "   3. Auto-recovery quando il servizio torna disponibile\n";
echo "   4. Riduce il carico sul servizio in difficoltà\n";
echo "   5. Migliora l'esperienza utente con fast-fail\n\n";

echo "✨ Esempio completato!\n";
