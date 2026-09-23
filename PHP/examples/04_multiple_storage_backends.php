<?php
/**
 * Esempio 4: Multiple Storage Backends
 * 
 * Questo esempio mostra come utilizzare diversi backend di storage
 * (File, In-Memory, Database) per WSDL, logs e retry queue.
 * 
 * @package OpenFiber\Examples
 * @author OpenFiber Team
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use OpenFiber\DynamicSoapClient;
use OpenFiber\Storage\FileWsdlStorage;
use OpenFiber\Storage\InMemoryWsdlStorage;
use OpenFiber\Storage\FileSoapLogStorage;
use OpenFiber\Storage\FileRetryQueueStorage;
use OpenFiber\SoapLogger;
use OpenFiber\RetryPolicy;
use OpenFiber\Interfaces\DynamicDtoInterface;

// ====================================================================
// ESEMPIO 4.1: FILE-BASED STORAGE (Produzione)
// ====================================================================

echo "📁 Storage Backend: FILE SYSTEM\n";
echo "===============================\n\n";

$storageDir = __DIR__ . '/../storage';

$fileWsdlStorage = new FileWsdlStorage($storageDir . '/wsdl');
$fileLogStorage = new FileSoapLogStorage($storageDir . '/logs');
$fileRetryStorage = new FileRetryQueueStorage($storageDir . '/retry');

echo "✓ File WSDL Storage: {$storageDir}/wsdl\n";
echo "✓ File Log Storage: {$storageDir}/logs\n";
echo "✓ File Retry Storage: {$storageDir}/retry\n";
echo "  💡 Ideale per: Produzione, persistenza garantita\n\n";

// ====================================================================
// ESEMPIO 4.2: IN-MEMORY STORAGE (Testing)
// ====================================================================

echo "💾 Storage Backend: IN-MEMORY\n";
echo "=============================\n\n";

$memoryWsdlStorage = new InMemoryWsdlStorage();

// Pre-carica un WSDL in memoria per test
$sampleWsdl = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<definitions xmlns="http://schemas.xmlsoap.org/wsdl/"
             targetNamespace="http://example.com/test">
    <message name="TestRequest">
        <part name="data" type="xsd:string"/>
    </message>
</definitions>
XML;

$memoryWsdlStorage->saveWsdl('test_service', 'http://example.com/test.wsdl', $sampleWsdl);

echo "✓ In-Memory WSDL Storage configurato\n";
echo "✓ WSDL pre-caricato per: test_service\n";
echo "  💡 Ideale per: Unit testing, sviluppo rapido\n";
echo "  ⚠️  Attenzione: I dati vengono persi al riavvio\n\n";

// ====================================================================
// COMPARAZIONE STORAGE BACKENDS
// ====================================================================

echo "📊 Comparazione Storage Backends\n";
echo "================================\n\n";

echo "1. FILE STORAGE\n";
echo "   ✓ Persistenza garantita\n";
echo "   ✓ Nessuna dipendenza da database\n";
echo "   ✓ Facile backup e restore\n";
echo "   ✗ Performance leggermente inferiore\n";
echo "   💰 Costo: Basso (solo filesystem)\n\n";

echo "2. IN-MEMORY STORAGE\n";
echo "   ✓ Performance massime\n";
echo "   ✓ Zero I/O overhead\n";
echo "   ✓ Perfetto per testing\n";
echo "   ✗ Dati non persistenti\n";
echo "   💰 Costo: Zero\n\n";

echo "3. DATABASE STORAGE (PDO)\n";
echo "   ✓ Query complesse possibili\n";
echo "   ✓ Scalabilità orizzontale\n";
echo "   ✓ Transazioni ACID\n";
echo "   ✗ Richiede database configurato\n";
echo "   💰 Costo: Medio-Alto (server DB)\n\n";

// ====================================================================
// ESEMPIO PRATICO: SWITCH TRA AMBIENTI
// ====================================================================

echo "🔄 Switch Automatico tra Ambienti\n";
echo "=================================\n\n";

// Determina l'ambiente
$environment = getenv('APP_ENV') ?: 'development';

echo "Ambiente rilevato: {$environment}\n\n";

// Seleziona storage in base all'ambiente
$wsdlStorage = match($environment) {
    'production' => new FileWsdlStorage($storageDir . '/wsdl'),
    'testing' => new InMemoryWsdlStorage(),
    'development' => new FileWsdlStorage($storageDir . '/wsdl'),
    default => new InMemoryWsdlStorage(),
};

$logStorage = match($environment) {
    'production' => new FileSoapLogStorage($storageDir . '/logs'),
    'testing' => new FileSoapLogStorage('/tmp/openfiber_test_logs'),
    'development' => new FileSoapLogStorage($storageDir . '/logs'),
    default => new FileSoapLogStorage('/tmp/openfiber_logs'),
};

echo "✓ WSDL Storage: " . get_class($wsdlStorage) . "\n";
echo "✓ Log Storage: " . get_class($logStorage) . "\n\n";

// ====================================================================
// DTO DI TEST
// ====================================================================

$dto = new class implements DynamicDtoInterface {
    public function toSoapPayload(): array
    {
        return ['test' => 'storage_backends'];
    }
    
    public function validate(): bool
    {
        return true;
    }
};

// ====================================================================
// CLIENT CONFIGURATO CON STORAGE SELEZIONATO
// ====================================================================

$logger = new SoapLogger($logStorage);
$retryPolicy = new RetryPolicy(new FileRetryQueueStorage($storageDir . '/retry'));

$client = new DynamicSoapClient(
    serviceName: 'storage_test_service',
    wsdlStorage: $wsdlStorage,
    logger: $logger,
    retryPolicy: $retryPolicy,
    dynamicDto: $dto
);

echo "✅ Client configurato con storage per ambiente: {$environment}\n\n";

// ====================================================================
// BEST PRACTICES
// ====================================================================

echo "💡 Best Practices\n";
echo "=================\n\n";

echo "1. SVILUPPO:\n";
echo "   → Usa In-Memory Storage per test rapidi\n";
echo "   → Usa File Storage per debugging\n\n";

echo "2. TESTING:\n";
echo "   → Sempre In-Memory Storage\n";
echo "   → Dati isolati per ogni test\n\n";

echo "3. STAGING:\n";
echo "   → File Storage o Database\n";
echo "   → Replica configurazione produzione\n\n";

echo "4. PRODUZIONE:\n";
echo "   → File Storage per piccole/medie applicazioni\n";
echo "   → Database Storage per grandi volumi\n";
echo "   → Considera cache distribuita (Redis/Memcached)\n\n";

echo "✨ Esempio completato!\n";
