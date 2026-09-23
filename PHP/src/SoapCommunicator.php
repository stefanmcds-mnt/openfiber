<?php
declare(strict_types=1);

namespace OpenFiber;

use OpenFiber\Interfaces\SoapCommunicatorInterface;
use OpenFiber\Interfaces\WsdlStorageInterface;
use OpenFiber\Exception\WsdlNotFoundException;
use OpenFiber\Exception\SoapCommunicationException;
use SoapClient;
use SoapFault;

class SoapCommunicator implements SoapCommunicatorInterface
{

    public function __construct(
        private WsdlStorageInterface $wsdlStorage,
        private ?SoapClient $client = null
    ) {
        $this->wsdlStorage = $wsdlStorage;
    }

    public function send(string $serviceName, array $payload): string
    {
        $wsdl = $this->getWsdl($serviceName);
        if (empty($wsdl)) {
            throw WsdlNotFoundException::notFoundInStorage(
                $serviceName,
                get_class($this->wsdlStorage)
            );
        }

        $this->client = new class('data://text/plain;base64,' . base64_encode($wsdl), $this->getSoapOptions()) extends SoapClient {
            public function __doRequest(string $request, string $location, string $action, int $version, bool $oneWay = false): ?string
            {
                $startTime = microtime(true);
                try {
                    return parent::__doRequest($request, $location, $action, $version, $oneWay);
                } finally {
                    $executionTime = (int)((microtime(true) - $startTime) * 1000);
                    // Logging is handled by the Logger
                }
            }
        };

        try {
            return $this->client->__soapCall($serviceName, [$payload]);
        } catch (SoapFault $fault) {
            // Converti SoapFault in SoapCommunicationException con contesto
            throw SoapCommunicationException::fromSoapFault($fault, [
                'service_name' => $serviceName,
                'payload_keys' => array_keys($payload),
            ]);
        }
    }

    public function getWsdl(string $serviceName): ?string
    {
        return $this->wsdlStorage->getWsdl($serviceName);
    }

    private function getSoapOptions(): array
    {
        return [
            'trace' => 1,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'connection_timeout' => 30,
        ];
    }
}