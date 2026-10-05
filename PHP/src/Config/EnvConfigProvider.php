<?php
declare(strict_types=1);

namespace OpenFiber\Config;

use OpenFiber\Interfaces\ConfigProvider;
use OpenFiber\Traits\CastableValueTrait;

class EnvConfigProvider implements ConfigProvider
{
    use CastableValueTrait;

    private ?string $prefix;

    public function __construct(?string $prefix = 'OPENFIBER_')
    {
        $this->prefix = $prefix;
    }

    public function get(?string $key, mixed $default = null): mixed
    {
        $envKey = $this->prefix . strtoupper(str_replace(['.', '[', ']'], '_', $key));
        $value = $_ENV[$envKey] ?? $default;

        return $this->castValue($value);
    }

    public function set(?string $key, mixed $value): bool
    {
        // Environment variables are typically read-only, so we don't implement set
        // for env provider. Return false to indicate this is not supported.
        return false;
    }

}