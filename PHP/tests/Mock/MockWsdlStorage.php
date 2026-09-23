<?php

declare(strict_types=1);

namespace OpenFiber\Tests\Mock;

use OpenFiber\Interfaces\WsdlStorageInterface;

/**
 * Mock implementation of WsdlStorageInterface for testing
 */
class MockWsdlStorage implements WsdlStorageInterface
{
    private array $wsdls = [];
    private bool $shouldFail = false;

    public function getWsdl(string $serviceName): ?string
    {
        if ($this->shouldFail) {
            return null;
        }
        return $this->wsdls[$serviceName] ?? null;
    }

    public function saveWsdl(string $serviceName, string $url, string $content, string $variablesJson = ''): bool
    {
        if ($this->shouldFail) {
            return false;
        }
        $this->wsdls[$serviceName] = $content;
        return true;
    }

    /**
     * Test helper: Set a WSDL content for testing
     */
    public function setWsdl(string $serviceName, string $content): void
    {
        $this->wsdls[$serviceName] = $content;
    }

    /**
     * Test helper: Make the storage fail
     */
    public function setShouldFail(bool $shouldFail): void
    {
        $this->shouldFail = $shouldFail;
    }

    /**
     * Test helper: Check if a WSDL was saved
     */
    public function hasWsdl(string $serviceName): bool
    {
        return isset($this->wsdls[$serviceName]);
    }

    /**
     * Test helper: Get all stored WSDLs
     */
    public function getAllWsdls(): array
    {
        return $this->wsdls;
    }

    /**
     * Test helper: Clear all WSDLs
     */
    public function clear(): void
    {
        $this->wsdls = [];
    }
}
