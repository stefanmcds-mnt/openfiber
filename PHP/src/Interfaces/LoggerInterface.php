<?php
declare(strict_types=1);

namespace OpenFiber\Interfaces;

use OpenFiber\Interfaces\DynamicDtoInterface;

interface LoggerInterface
{
    public function logSuccess(string $serviceName, DynamicDtoInterface $dto, string $response): void;
    public function logFailure(string $serviceName, DynamicDtoInterface $dto, \Exception $e): void;
}