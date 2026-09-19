<?php
declare(strict_types=1);

namespace OpenFiber;

interface ConfigRepositoryInterface 
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value): bool;
    public function saveWsdl(string $serviceName, string $url, string $rawContent): bool;
}