<?php
declare(strict_types=1);

namespace OpenFiber\Interfaces;

interface SoapLogStorageInterface
{
    public function logSoapTransaction(array $transactionData): bool;
    public function getSoapLogs(array $filters): array;
}