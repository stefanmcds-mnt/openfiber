<?php
declare(strict_types=1);

namespace OpenFiber\Storage;

use OpenFiber\Interfaces\WsdlStorageInterface;

class InMemoryWsdlStorage implements WsdlStorageInterface
{
    private array $wsdls = [];

    public function getWsdl(string $serviceName): ?string
    {
        return $this->wsdls[$serviceName] ?? null;
    }

    public function getWsdlVar(string $serviceName): array
    {
        return $this->wsdls[$serviceName] ?? null;
    }

    public function saveWsdl(string $serviceName, string $url, string $content, string $variablesJson = ''): bool
    {
        $this->wsdls[$serviceName] = $content;
        if ($variablesJson !== '') {
            $this->wsdls[$serviceName . ':vars'] = $variablesJson;
        }
        return true;
    }
}