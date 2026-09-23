<?php
declare(strict_types=1);

namespace OpenFiber\Interfaces;

interface RetryQueueStorageInterface
{
    public function enqueueRetry(array $retryData): bool;
    public function getNextRetry(): ?array;
    public function updateRetryStatus(int $id, string $status): bool;
}