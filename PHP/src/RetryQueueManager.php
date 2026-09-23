<?php
declare(strict_types=1);

namespace OpenFiber;

use PDO;
use DateTimeImmutable;

class RetryQueueManager 
{
    public function __construct(private PDO $db) {}

    /**
     * Inserisce un bando di trasmissione fallito nella coda di retry asincrono
     */
    public function push(
        string $serviceName, 
        string $codiceOrdineOlo, 
        string $idNotifica, 
        array $payload, 
        string $retryType,
        int $delaySeconds = 60
    ): bool {
        $nextAttempt = (new DateTimeImmutable())->modify("+{$delaySeconds} seconds")->format('Y-m-d H:i:s');
        
        $stmt = $this->db->prepare("
            INSERT INTO of_queue_retry (service_name, codice_ordine_olo, id_notifica, payload_json, retry_type, next_attempt_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $serviceName,
            $codiceOrdineOlo,
            $idNotifica,
            json_encode($payload),
            $retryType,
            $nextAttempt
        ]);
    }
}

