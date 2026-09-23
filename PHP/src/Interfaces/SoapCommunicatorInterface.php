<?php
declare(strict_types=1);

namespace OpenFiber\Interfaces;

interface SoapCommunicatorInterface
{
    public function send(string $serviceName, array $payload): string;
    public function getWsdl(string $serviceName): ?string;
}
