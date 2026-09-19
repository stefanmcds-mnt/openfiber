<?php
declare(strict_types=1);

namespace OpenFiber;

use PDO;
use OpenFiber\DynamicSoapClient;
use OpenFiber\DynamicDtoInterface;
use DateTimeImmutable;
use Throwable;

class AsyncRetryWorker 
{
    public function __construct(private PDO $db) {}

    public function processNextJob(): void 
    {
        // Seleziona e blocca un record (FOR UPDATE per evitare race conditions tra processi concorrenti)
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            SELECT * FROM of_queue_retry 
            WHERE status = 'pending' AND next_attempt_at <= NOW() 
            LIMIT 1 FOR UPDATE
        ");
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            $this->db->rollBack();
            return;
        }

        // Cambia lo stato in lavorazione
        $this->db->prepare("UPDATE of_queue_retry SET status = 'processing', attempts = attempts + 1 WHERE id = ?")
                 ->execute([$job['id']]);
        $this->db->commit();

        $payload = json_decode($job['payload_json'], true);

        // Politica Par 5.1.4: Se è un retry per NACK, dobbiamo generare un NUOVO ID_NOTIFICA
        if ($job['retry_type'] === 'NACK') {
            $payload['ID_NOTIFICA'] = 'NOT-RETRY-' . uniqid();
        }

        // Creazione del DTO anonimo "al volo" partendo dai dati salvati
        $dynamicDto = new class($payload) implements DynamicDtoInterface {
            public function __construct(private array $data) {}
            public function toSoapPayload(): array { return $this->data; }
        };

        try {
            $client = new DynamicSoapClient($this->db, $job['service_name']);
            $client->send($dynamicDto);

            // Completato con successo
            $this->db->prepare("UPDATE of_queue_retry SET status = 'completed' WHERE id = ?")->execute([$job['id']]);
        } catch (Throwable $e) {
            // Se fallisce di nuovo, calcoliamo un backoff esponenziale (es. attesa raddoppiata)
            if ($job['attempts'] < $job['max_attempts']) {
                $delay = 60 * ($job['attempts'] + 1);
                $nextAttempt = (new DateTimeImmutable())->modify("+{$delay} seconds")->format('Y-m-d H:i:s');
                
                $this->db->prepare("
                    UPDATE of_queue_retry 
                    SET status = 'pending', payload_json = ?, next_attempt_at = ? 
                    WHERE id = ?
                ")->execute([json_encode($payload), $nextAttempt, $job['id']]);
            } else {
                // Raggiunto il limite massimo di tentativi falliti
                $this->db->prepare("UPDATE of_queue_retry SET status = 'failed' WHERE id = ?")->execute([$job['id']]);
            }
        }
    }
}

