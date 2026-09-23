<?php
declare(strict_types=1);

namespace OpenFiber;

use PDO;
use SoapServer;
use RuntimeException;

class DynamicSoapServer 
{
    public function __construct(
        private PDO $db,
        private string $serviceName
    ) {}

    /**
     * Avvia l'ascolto del server, effettua i controlli e logga la transazione automaticamente.
     */
    public function handle(callable $businessLogicHandler): void 
    {
        // 1. Validazione di Sicurezza IP Open Fiber (Configurati a DB)
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$this->isIpAllowed($clientIp)) {
            header('HTTP/1.1 403 Forbidden');
            echo "Access Denied: IP non autorizzato.";
            return;
        }

        // 2. Recupero del WSDL Raw da DB
        $stmt = $this->db->prepare("SELECT wsdl_raw FROM of_wsdl_storage WHERE service_name = ?");
        $stmt->execute([$this->serviceName]);
        $wsdlRaw = $stmt->fetchColumn();

        if (!$wsdlRaw) {
            header('HTTP/1.1 500 Internal Server Error');
            echo "Errore Configurazione: WSDL Mancante.";
            return;
        }

        $wsdlDataUri = 'data://text/plain;base64,' . base64_encode($wsdlRaw);

        // 3. Cattura dell'XML in ingresso (Request inviata da Open Fiber)
        $requestXml = file_get_contents('php://input');

        // 4. Inizializzazione dell'estensione nativa SoapServer
        $server = new SoapServer($wsdlDataUri, [
            'cache_wsdl' => WSDL_CACHE_NONE,
            'encoding' => 'UTF-8'
        ]);

        // Wrapper dinamico per instradare i dati e preparare l'ACK/NACK
        $serviceWrapper = new class($businessLogicHandler) {
            public ?string $codiceOrdineOlo = null;
            public ?string $idNotifica = null;

            public function __construct(private $handler) {}

            public function __call(string $method, array $arguments): array 
            {
                // Estraiamo i dati dell'oggetto inviato da OF
                $data = $arguments[0] ?? null;
                if ($data) {
                    $this->codiceOrdineOlo = $data->CODICE_ORDINE_OLO ?? null;
                    $this->idNotifica = $data->ID_NOTIFICA ?? null;
                }

                // Esecuzione della logica dell'applicazione (es. salvataggio su CRM locale)
                $result = ($this->handler)($method, $data);

                // Struttura fissa di risposta sincrona (ACK/NACK) richiesta da Open Fiber (Pagina 37)
                return [
                    'ID_NOTIFICA' => $this->idNotifica ?? uniqid(),
                    'ESITO' => ($result['success'] ?? true) ? '0' : '1', // 0 = ACK, 1 = NACK
                    'CODICE_MOTIVAZIONE' => $result['code'] ?? null,
                    'MOTIVAZIONE' => $result['message'] ?? 'OK'
                ];
            }
        };

        $server->setObject($serviceWrapper);

        // Intercettiamo l'XML di risposta generato dal SoapServer tramite Buffer di Output
        ob_start();
        $startTime = microtime(true);
        
        try {
            $server->handle($requestXml);
        } catch (\Throwable $e) {
            // In caso di crash strutturale generiamo un SoapFault standard
            $server->fault("Server", $e->getMessage());
        }

        $responseXml = ob_get_clean();
        $executionTime = (int)((microtime(true) - $startTime) * 1000);

        // Mandiamo la risposta di rete a Open Fiber
        echo $responseXml;

        // =======================================================
        // REGISTRAZIONE AUTOMATICA DEL LOG IN INGRESSO (SERVER_IN)
        // =======================================================
        $stmtLog = $this->db->prepare("
            INSERT INTO of_soap_logs (direction, service_name, codice_ordine_olo, id_notifica, request_xml, response_xml, http_status, execution_time_ms)
            VALUES ('SERVER_IN', ?, ?, ?, ?, ?, 200, ?)
        ");
        $stmtLog->execute([
            $this->serviceName,
            $serviceWrapper->codiceOrdineOlo,
            $serviceWrapper->idNotifica,
            $requestXml,
            $responseXml,
            $executionTime
        ]);
    }

    private function isIpAllowed(string $ip): bool 
    {
        $stmt = $this->db->prepare("SELECT allowed_openfiber_ips FROM of_soap_server_config WHERE service_name = ? AND is_active = 1");
        $stmt->execute([$this->serviceName]);
        $allowedIpsRaw = $stmt->fetchColumn();

        if (!$allowedIpsRaw) return false;

        $allowedIps = array_map('trim', explode(',', $allowedIpsRaw));
        return in_array($ip, $allowedIps, true);
    }
}
