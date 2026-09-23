<?php
declare(strict_types=1);

namespace OpenFiber\Interfaces;

interface ConfigProvider
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): bool;
}