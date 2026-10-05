<?php
namespace OpenFiber\Storage;

use OpenFiber\Interfaces\WsdlStorageInterface;

abstract class BaseWsdlStorage implements WsdlStorageInterface
{
    protected array $storage = [];
    
    public function getWsdl(string $serviceName): ?string
    {
        return $this->storage[$serviceName] ?? null;
    }
    
    public function getWsdlVar(string $serviceName): array
    {
        $wsdl = $this->getWsdl($serviceName);
        return $wsdl ? $this->extractVariables($wsdl) : null;
    }
    
    public function saveWsdl(string $serviceName, string $url, string $content, string $variablesJson = ''): bool
    {
        $this->storage[$serviceName] = $content;
        $this->saveVariables($serviceName, $variablesJson);
        return true;
    }
    
    protected function extractVariables(string $wsdl): array
    {
        // Common variable extraction logic
        return $this->parseWsdlVariables($wsdl);
    }
    
    protected function saveVariables(string $serviceName, string $variablesJson): void
    {
        // Common variable saving logic
        if ($variablesJson) {
            $this->storage[$serviceName . '_vars'] = $variablesJson;
        }
    }
    
    protected function parseWsdlVariables(string $wsdl): array
    {
        // Abstract method for variable parsing
        return [];
    }
}