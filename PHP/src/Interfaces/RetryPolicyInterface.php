<?php
declare(strict_types=1);

namespace OpenFiber\Interfaces;

interface RetryPolicyInterface
{
    public function shouldRetry(\Exception $e): bool;
    public function queueForRetry(string $serviceName, string $codiceOrdineOlo, string $idNotifica, array $payload, \Exception $e): void;
}