<?php
declare(strict_types=1);

namespace OpenFiber\Interfaces;

interface WsdlStorageInterface
{
    public function getWsdl(string $serviceName): ?string;
    public function getWsdlVar(string $serviceName): array|object|string;
    public function saveWsdl(string $serviceName, string $url, string $content, string $variablesJson = ''): bool;
}