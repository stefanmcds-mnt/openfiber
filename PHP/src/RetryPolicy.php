<?php
declare(strict_types=1);

namespace OpenFiber;

use OpenFiber\Interfaces\RetryPolicyInterface;
use OpenFiber\Interfaces\RetryQueueStorageInterface;
use OpenFiber\Exception\SoapCommunicationException;
use Exception;

class RetryPolicy implements RetryPolicyInterface
{
    private RetryQueueStorageInterface $storage;

    public function __construct(RetryQueueStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function shouldRetry(Exception $e): bool
    {
        // Se è una SoapCommunicationException, usa il metodo isRecoverable()
        if ($e instanceof SoapCommunicationException) {
            return $e->isRecoverable();
        }
        
        // Fallback: controlla i pattern comuni di errori recuperabili
        $recoverablePatterns = [
            'timeout',
            'timed out',
            'could not connect',
            'connection refused',
            'network',
            'temporarily unavailable'
        ];
        
        $message = strtolower($e->getMessage());
        foreach ($recoverablePatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    public function queueForRetry(string $serviceName, string $codiceOrdineOlo, string $idNotifica, array $payload, Exception $e): void
    {
        $retryType = $this->shouldRetry($e) ? 'AUTOMATIC' : 'NACK';

        $this->storage->enqueueRetry([
            'service_name' => $serviceName,
            'codice_ordine_olo' => $codiceOrdineOlo,
            'id_notifica' => $idNotifica,
            'dto' => $payload,
            'error_message' => $e->getMessage(),
            'retry_type' => $retryType,
            'created_at' => date('c'),
        ]);
    }
}