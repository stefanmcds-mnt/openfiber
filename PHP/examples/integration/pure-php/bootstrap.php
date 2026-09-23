<?php
/**
 * Bootstrap per Pure PHP Application
 * 
 * Questo file mostra come configurare OpenFiber in una applicazione
 * PHP pura senza framework.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use OpenFiber\DynamicSoapClient;
use OpenFiber\Storage\FileWsdlStorage;
use OpenFiber\Storage\FileSoapLogStorage;
use OpenFiber\Storage\FileRetryQueueStorage;
use OpenFiber\SoapLogger;
use OpenFiber\RetryPolicy;
use OpenFiber\CircuitBreaker;

// ====================================================================
// CARICA CONFIGURAZIONE DA ENVIRONMENT o FILE
// ====================================================================

// Carica variabili d'ambiente da .env se disponibile
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Helper per leggere configurazione
function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $default;
}

// ====================================================================
// CONFIGURAZIONE PERCORSI
// ====================================================================

$baseDir = __DIR__;
$storageDir = $baseDir . '/storage';
$wsdlDir = $storageDir . '/wsdl';
$logsDir = $storageDir . '/logs';
$retryDir = $storageDir . '/retry';

// Crea directory se non esistono
foreach ([$storageDir, $wsdlDir, $logsDir, $retryDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ====================================================================
// INIZIALIZZA COMPONENTI
// ====================================================================

// Storage
$wsdlStorage = new FileWsdlStorage($wsdlDir);
$logStorage = new FileSoapLogStorage($logsDir);
$retryStorage = new FileRetryQueueStorage($retryDir);

// Logger
$logger = new SoapLogger($logStorage);

// Retry Policy
$retryPolicy = new RetryPolicy(
    retryStorage: $retryStorage,
    maxAttempts: (int)env('OPENFIBER_RETRY_MAX_ATTEMPTS', 3),
    retryableErrors: [
        'Server.Timeout',
        'Server.Unavailable',
        'Network.ConnectionLost',
    ]
);

// Circuit Breaker (opzionale)
$circuitBreaker = new CircuitBreaker(
    failureThreshold: (int)env('OPENFIBER_CB_FAILURE_THRESHOLD', 5),
    timeoutSeconds: (int)env('OPENFIBER_CB_TIMEOUT', 60)
);

// ====================================================================
// CREA CLIENT GLOBALE (SINGLETON PATTERN)
// ====================================================================

class OpenFiberClientFactory
{
    private static ?DynamicSoapClient $instance = null;
    
    public static function getInstance(): DynamicSoapClient
    {
        if (self::$instance === null) {
            global $wsdlStorage, $logger, $retryPolicy;
            
            self::$instance = new DynamicSoapClient(
                serviceName: env('OPENFIBER_SERVICE_NAME', 'default'),
                wsdlStorage: $wsdlStorage,
                logger: $logger,
                retryPolicy: $retryPolicy
            );
        }
        
        return self::$instance;
    }
}

// ====================================================================
// FUNZIONI HELPER
// ====================================================================

/**
 * Ottieni il client OpenFiber
 */
function openfiber(): DynamicSoapClient
{
    return OpenFiberClientFactory::getInstance();
}

/**
 * Invia una richiesta SOAP
 */
function openfiber_send(array $data): mixed
{
    $dto = new class($data) implements \OpenFiber\Interfaces\DynamicDtoInterface {
        public function __construct(private array $data) {}
        
        public function toSoapPayload(): array
        {
            return $this->data;
        }
        
        public function validate(): bool
        {
            return !empty($this->data);
        }
    };
    
    return openfiber()->send($dto);
}

// ====================================================================
// AUTOLOADER AGGIUNTIVO PER CLASSI CUSTOM (opzionale)
// ====================================================================

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// ====================================================================
// INIZIALIZZAZIONE COMPLETATA
// ====================================================================

// Opzionalmente registra un error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    return false;
});

// Opzionalmente registra un exception handler
set_exception_handler(function($exception) {
    error_log("Uncaught exception: " . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'internal_error',
        'message' => 'Si è verificato un errore interno',
    ]);
});
