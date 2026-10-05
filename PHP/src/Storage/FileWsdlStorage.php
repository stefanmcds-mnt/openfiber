<?php
declare(strict_types=1);

namespace OpenFiber\Storage;

use OpenFiber\Interfaces\WsdlStorageInterface;

//class FileWsdlStorage implements WsdlStorageInterface
class FileWsdlStorage extends BaseWsdlStorage
{
    private string $storageDir;

    public function __construct(string $storageDir)
    {
        $this->storageDir = rtrim($storageDir, '/') . '/';
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    public function getWsdl(string $serviceName): ?string
    {
        $filePath = $this->storageDir . $serviceName . '.wsdl';
        if (!file_exists($filePath)) {
            return null;
        }
        return file_get_contents($filePath);
    }

    public function getWsdlVar(string $serviceName): array
    {
        return [];
    }

    public function saveWsdl(string $serviceName, string $url, string $content, string $variablesJson = ''): bool
    {
        $filePath = $this->storageDir . $serviceName . '.wsdl';
        $fileSuccess = file_put_contents($filePath, $content) !== false;
        
        // Also save variables JSON if provided
        if ($variablesJson !== '') {
            $varFilePath = $this->storageDir . $serviceName . '.wsdl.var.json';
            file_put_contents($varFilePath, $variablesJson);
        }
        
        return $fileSuccess;
    }
}