<?php
declare(strict_types=1);

namespace OpenFiber;

use OpenFiber\Interfaces\LoggerInterface;
use OpenFiber\Interfaces\SoapLogStorageInterface;
use OpenFiber\Interfaces\DynamicDtoInterface;

class SoapLogger implements LoggerInterface
{
    private SoapLogStorageInterface $storage;

    public function __construct(SoapLogStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function logSuccess(string $serviceName, DynamicDtoInterface $dto, string $response): void
    {
        $payload = $dto->toSoapPayload();
        $codiceOrdineOlo = $payload['CODICE_ORDINE_OLO'] ?? null;
        $idNotifica = $payload['ID_NOTIFICA'] ?? null;

        $this->storage->logSoapTransaction([
            'direction' => 'CLIENT_OUT',
            'service_name' => $serviceName,
            'codice_ordine_olo' => $codiceOrdineOlo,
            'id_notifica' => $idNotifica,
            'request_xml' => '',
            'response_xml' => $response,
            'http_status' => 200,
            'execution_time_ms' => 0,
            'timestamp' => date('c'),
        ]);
    }

    public function logFailure(string $serviceName, DynamicDtoInterface $dto, \Exception $e): void
    {
        $payload = $dto->toSoapPayload();
        $codiceOrdineOlo = $payload['CODICE_ORDINE_OLO'] ?? null;
        $idNotifica = $payload['ID_NOTIFICA'] ?? null;

        $this->storage->logSoapTransaction([
            'direction' => 'CLIENT_OUT',
            'service_name' => $serviceName,
            'codice_ordine_olo' => $codiceOrdineOlo,
            'id_notifica' => $idNotifica,
            'request_xml' => '',
            'response_xml' => '',
            'http_status' => 500,
            'execution_time_ms' => 0,
            'error_message' => $e->getMessage(),
            'timestamp' => date('c'),
        ]);
    }
}