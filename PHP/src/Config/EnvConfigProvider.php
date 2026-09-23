<?php
declare(strict_types=1);

namespace OpenFiber\Config;

use OpenFiber\Interfaces\ConfigProvider;

class EnvConfigProvider implements ConfigProvider
{
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

    private function castValue(mixed $value): mixed
    {
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if ($value === 'null') return null;
        
        // Try to parse as integer
        if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
            return (int) $value;
        }
        
        // Try to parse as float
        if (filter_var($value, FILTER_VALIDATE_FLOAT) !== false) {
            return (float) $value;
        }
        
        return $value;
    }
}