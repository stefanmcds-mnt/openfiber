<?php
/**
 * AWS Lambda Handler per OpenFiber
 * 
 * Questo handler gestisce le richieste Lambda e le inoltra al client OpenFiber.
 * Compatibile con AWS Lambda Runtime API per PHP.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use OpenFiber\DynamicSoapClient;
use OpenFiber\Storage\FileWsdlStorage;
use OpenFiber\Storage\FileSoapLogStorage;
use OpenFiber\Storage\FileRetryQueueStorage;
use OpenFiber\SoapLogger;
use OpenFiber\RetryPolicy;
use OpenFiber\Interfaces\DynamicDtoInterface;
use OpenFiber\Exception\SoapCommunicationException;
use OpenFiber\Exception\ServiceUnavailableException;

// ====================================================================
// CONFIGURAZIONE
// ====================================================================

$tmpDir = '/tmp/openfiber';
$storageDir = $tmpDir . '/storage';
$wsdlDir = $storageDir . '/wsdl';
$logsDir = $storageDir . '/logs';
$retryDir = $storageDir . '/retry';

// Crea directory
foreach ([$tmpDir, $storageDir, $wsdlDir, $logsDir, $retryDir] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// ====================================================================
// INIZIALIZZA COMPONENTI (COLD START)
// ====================================================================

$wsdlStorage = new FileWsdlStorage($wsdlDir);
$logStorage = new FileSoapLogStorage($logsDir);
$retryStorage = new FileRetryQueueStorage($retryDir);
$logger = new SoapLogger($logStorage);
$retryPolicy = new RetryPolicy(
    retryStorage: $retryStorage,
    maxAttempts: (int)(getenv('OPENFIBER_RETRY_MAX_ATTEMPTS') ?: 3)
);

// ====================================================================
// HANDLER FUNCTION
// ====================================================================

/**
 * Main Lambda handler function
 * 
 * @param array $event Event data from Lambda
 * @param mixed $context Lambda context
 * @return array Response
 */
function handler(array $event, $context = null): array
{
    global $wsdlStorage, $logger, $retryPolicy;
    
    try {
        // Parse event body
        $body = $event['body'] ?? '{}';
        if (is_string($body)) {
            $body = json_decode($body, true);
        }
        
        // Validate input
        if (empty($body['service_name']) || empty($body['payload'])) {
            return [
                'statusCode' => 400,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode([
                    'success' => false,
                    'error' => 'Missing required parameters: service_name, payload',
                ]),
            ];
        }
        
        // Create DTO
        $dto = new class($body['payload']) implements DynamicDtoInterface {
            public function __construct(private array $payload) {}
            
            public function toSoapPayload(): array
            {
                return $this->payload;
            }
            
            public function validate(): bool
            {
                return !empty($this->payload);
            }
        };
        
        // Create client
        $client = new DynamicSoapClient(
            serviceName: $body['service_name'],
            wsdlStorage: $wsdlStorage,
            logger: $logger,
            retryPolicy: $retryPolicy,
            dynamicDto: $dto
        );
        
        // Send request
        $response = $client->send();
        
        // Success response
        return [
            'statusCode' => 200,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'success' => true,
                'data' => $response,
            ]),
        ];
        
    } catch (SoapCommunicationException $e) {
        return [
            'statusCode' => 502,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'success' => false,
                'error' => 'soap_communication_error',
                'message' => $e->getMessage(),
            ]),
        ];
        
    } catch (ServiceUnavailableException $e) {
        return [
            'statusCode' => 503,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'success' => false,
                'error' => 'service_unavailable',
                'message' => 'Service temporarily unavailable',
            ]),
        ];
        
    } catch (Exception $e) {
        error_log('Lambda error: ' . $e->getMessage());
        
        return [
            'statusCode' => 500,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'success' => false,
                'error' => 'internal_error',
                'message' => 'An internal error occurred',
            ]),
        ];
    }
}

// Export handler for Bref runtime
return 'handler';
