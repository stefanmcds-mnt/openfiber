<?php
declare(strict_types=1);

namespace OpenFiber;

use PDO;
use SoapClient;
use SoapFault;
use RuntimeException;
use Throwable;
use OpenFiber\DynamicDtoInterface;
use OpenFiber\RetryQueueManager;


class DynamicSoapClient 
{
    private ?SoapClient $client = null;

    public function __construct(
        private PDO $db,
        private string $serviceName,
        private ?DynamicDtoInterface $dynamicDto = null
    ) {}

    public function send(?DynamicDtoInterface $specificDto = null): mixed 
    {
        $dto = $specificDto ?? $this->dynamicDto;
        if (!$dto) {
            throw new RuntimeException("Nessun payload fornito.");
        }

        $payload = $dto->toSoapPayload();
        $codiceOrdineOlo = $payload['CODICE_ORDINE_OLO'] ?? null;
        $idNotifica = $payload['ID_NOTIFICA'] ?? null;

        // Estendiamo SoapClient anonimamente per intercettare l'XML al volo (__doRequest)
        $this->client = new class('data://text/plain;base64,' . base64_encode($this->getWsdlRaw()), $this->getSoapOptions()) extends SoapClient {
            public PDO $dbEx;
            public string $sName;
            public ?string $cOrdine;
            public ?string $iNotifica;
            
            #[\Override]
            public function __doRequest(string $request, string $location, string $action, int $version, bool $oneWay = false): ?string 
            {
                $startTime = microtime(true);
                $responseXml = null;
                $httpStatus = 200;

                try {
                    // Esegue la reale chiamata di rete verso Open Fiber
                    $responseXml = parent::__doRequest($request, $location, $action, $version, $oneWay);
                    return $responseXml;
                } catch (Throwable $e) {
                    $httpStatus = 500;
                    throw $e;
                } finally {
                    $executionTime = (int)((microtime(true) - $startTime) * 1000);
                    
                    // =======================================================
                    // REGISTRAZIONE AUTOMATICA A DATABASE (Punto richiesto)
                    // =======================================================
                    $stmtLog = $this->dbEx->prepare("
                        INSERT INTO of_soap_logs (direction, service_name, codice_ordine_olo, id_notifica, request_xml, response_xml, http_status, execution_time_ms)
                        VALUES ('CLIENT_OUT', ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtLog->execute([
                        $this->sName, $this->cOrdine, $this->iNotifica, $request, $responseXml, $httpStatus, $executionTime
                    ]);
                }
            }
        };

        // Iniezione delle dipendenze necessarie dentro la classe anonima estesa
        $this->client->dbEx = $this->db;
        $this->client->sName = $this->serviceName;
        $this->client->cOrdine = $codiceOrdineOlo;
        $this->client->iNotifica = $idNotifica;

        try {
            return $this->client->__soapCall($this->serviceName, [$payload]);
        } catch (SoapFault $fault) {
            // =======================================================
            // ATTIVAZIONE POLITICA RETRY IN CASO DI ERRORE O TIMEOUT
            // =======================================================
            $queue = new RetryQueueManager($this->db);
            
            // Determina la tipologia in base al Fault
            $retryType = (str_contains(strtolower($fault->getMessage()), 'timeout') || str_contains(strtolower($fault->getMessage()), 'could not connect')) 
                ? 'AUTOMATIC' 
                : 'NACK';

            $queue->push($this->serviceName, $codiceOrdineOlo, $idNotifica, $payload, $retryType);
            
            throw new RuntimeException("Chiamata fallita. Inserita in coda di Retry Asincrono. Dettaglio: " . $fault->getMessage());
        }
    }

    private function getWsdlRaw(): string 
    {
        $stmt = $this->db->prepare("SELECT wsdl_raw FROM of_wsdl_storage WHERE service_name = ?");
        $stmt->execute([$this->serviceName]);
        return $stmt->fetchColumn() ?: '';
    }

    private function getSoapOptions(): array 
    {
        $stmtConfig = $this->db->prepare("SELECT * FROM of_soap_client_config LIMIT 1");
        $stmtConfig->execute();
        $config = $stmtConfig->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'trace' => 1,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'connection_timeout' => $config['timeout'] ?? 30,
            'local_cert' => $config['client_cert_path'] ?? null
        ];
    }
}
