<?php
declare(strict_types=1);

namespace OpenFiber;

use SoapClient;
use SoapFault;
use Throwable;
use OpenFiber\Interfaces\DynamicDtoInterface;
use OpenFiber\Interfaces\SoapCommunicatorInterface;
use OpenFiber\Interfaces\LoggerInterface;
use OpenFiber\Interfaces\RetryPolicyInterface;
use OpenFiber\Interfaces\WsdlStorageInterface;
use OpenFiber\Exception\ConfigurationException;
use OpenFiber\Exception\WsdlNotFoundException;
use OpenFiber\Exception\SoapCommunicationException;

/**
 * DynamicSoapClient - Cliente SOAP
 *
 * Questo client implementa il pattern di Dependency Injection e separa chiaramente:
 * - Comunicazione SOAP (SoapCommunicatorInterface)
 * - Logging delle transazioni (LoggerInterface)
 * - Gestione retry (RetryPolicyInterface)
 *
 */
class DynamicSoapClient
{
    private ?SoapClient $client = null;
    private array $soapOptions;

    public function __construct(
        private string $serviceName,
        private WsdlStorageInterface $wsdlStorage,
        private LoggerInterface $logger,
        private RetryPolicyInterface $retryPolicy,
        private ?DynamicDtoInterface $dynamicDto = null,
        array $soapOptions = []
    ) {
        $this->soapOptions = array_merge([
            'trace' => 1,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'connection_timeout' => 30,
        ], $soapOptions);
    }

    public function send(?DynamicDtoInterface $specificDto = null): mixed
    {
        $dto = $specificDto ?? $this->dynamicDto;
        if (!$dto) {
            throw ConfigurationException::missingParameter(
                'payload',
                ['service_name' => $this->serviceName]
            );
        }

        $payload = $dto->toSoapPayload();
        $codiceOrdineOlo = $payload['CODICE_ORDINE_OLO'] ?? null;
        $idNotifica = $payload['ID_NOTIFICA'] ?? null;

        // Recupera WSDL attraverso l'interfaccia di storage
        $wsdl = $this->wsdlStorage->getWsdl($this->serviceName);
        if (empty($wsdl)) {
            throw WsdlNotFoundException::notFoundInStorage(
                $this->serviceName,
                get_class($this->wsdlStorage)
            );
        }

        // Variabili per catturare request/response e timing
        $requestXml = '';
        $responseXml = '';
        $executionTime = 0;
        $httpStatus = 200;

        // Estendiamo SoapClient anonimamente per intercettare l'XML al volo (__doRequest)
        $extendedClient = new class(
            'data://text/plain;base64,' . base64_encode($wsdl),
            $this->soapOptions
        ) extends SoapClient {
            public string $capturedRequest = '';
            public string $capturedResponse = '';
            public int $capturedExecutionTime = 0;
            public int $capturedHttpStatus = 200;
            
            #[\Override]
            public function __doRequest(string $request, string $location, string $action, int $version, bool $oneWay = false): ?string
            {
                $this->capturedRequest = $request;
                $startTime = microtime(true);

                try {
                    // Esegue la reale chiamata di rete verso Open Fiber
                    $response = parent::__doRequest($request, $location, $action, $version, $oneWay);
                    $this->capturedResponse = $response ?? '';
                    return $response;
                } catch (Throwable $e) {
                    $this->capturedHttpStatus = 500;
                    throw $e;
                } finally {
                    $this->capturedExecutionTime = (int)((microtime(true) - $startTime) * 1000);
                }
            }
        };
        
        $this->client = $extendedClient;

        try {
            $response = $extendedClient->__soapCall($this->serviceName, [$payload]);
            
            // Delega il logging al componente Logger (Separazione delle Preoccupazioni)
            $this->logger->logSuccess($this->serviceName, $dto, $extendedClient->capturedResponse);
            
            return $response;
        } catch (SoapFault $fault) {
            // Delega il logging dell'errore al componente Logger
            $this->logger->logFailure($this->serviceName, $dto, $fault);
            
            // Delega la gestione del retry alla Policy (Separazione delle Preoccupazioni)
            $this->retryPolicy->queueForRetry(
                $this->serviceName,
                $codiceOrdineOlo ?? '',
                $idNotifica ?? '',
                $payload,
                $fault
            );
            
            // Crea un'eccezione specifica con informazioni dettagliate
            $exception = SoapCommunicationException::fromSoapFault($fault, [
                'service_name' => $this->serviceName,
                'codice_ordine_olo' => $codiceOrdineOlo,
                'id_notifica' => $idNotifica,
                'queued_for_retry' => true,
            ]);
            
            // Aggiungi gli XML di richiesta/risposta se disponibili
            if (!empty($extendedClient->capturedRequest)) {
                $exception->setRequestXml($extendedClient->capturedRequest);
            }
            if (!empty($extendedClient->capturedResponse)) {
                $exception->setResponseXml($extendedClient->capturedResponse);
            }
            
            throw $exception;
        }
    }
}
