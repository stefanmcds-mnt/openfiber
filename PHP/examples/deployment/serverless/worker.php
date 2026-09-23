<?php
/**
 * AWS Lambda Worker per Retry Queue
 * 
 * Questo worker viene eseguito periodicamente da CloudWatch Events
 * per elaborare la coda di retry.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use OpenFiber\RetryQueueManager;
use OpenFiber\Storage\FileRetryQueueStorage;
use OpenFiber\DynamicSoapClient;
use OpenFiber\Storage\FileWsdlStorage;
use OpenFiber\SoapLogger;
use OpenFiber\RetryPolicy;
use OpenFiber\Storage\FileSoapLogStorage;

/**
 * Worker handler function
 */
function worker(array $event, $context = null): array
{
    $tmpDir = '/tmp/openfiber';
    $retryDir = $tmpDir . '/storage/retry';
    
    try {
        // Initialize storage
        $retryStorage = new FileRetryQueueStorage($retryDir);
        $wsdlStorage = new FileWsdlStorage($tmpDir . '/storage/wsdl');
        $logStorage = new FileSoapLogStorage($tmpDir . '/storage/logs');
        $logger = new SoapLogger($logStorage);
        $retryPolicy = new RetryPolicy(retryStorage: $retryStorage);
        
        // Process retry queue
        $processed = 0;
        $maxToProcess = 10; // Limit per invocation
        
        // This is a simplified version - in production, you'd use
        // the full RetryQueueManager or AsyncRetryWorker
        
        // Count processed items
        $processed = 0; // Would be calculated from actual processing
        
        return [
            'statusCode' => 200,
            'body' => json_encode([
                'success' => true,
                'processed' => $processed,
                'timestamp' => time(),
            ]),
        ];
        
    } catch (Exception $e) {
        error_log('Worker error: ' . $e->getMessage());
        
        return [
            'statusCode' => 500,
            'body' => json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]),
        ];
    }
}

// Export worker for Bref runtime
return 'worker';
