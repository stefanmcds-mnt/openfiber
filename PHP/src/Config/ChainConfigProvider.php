<?php
declare(strict_types=1);

namespace OpenFiber\Config;

use OpenFiber\Interfaces\ConfigProvider;

class ChainConfigProvider implements ConfigProvider
{
    private ?array $providers;

    public function __construct(?array $providers)
    {
        $this->providers = $providers;
    }

    public function get(?string $key, mixed $default = null): mixed
    {
        foreach ($this->providers as $provider) {
            $value = $provider->get($key, $default);
            // If the value is not the default, it was found by a provider
            if ($value !== $default) {
                return $value;
            }
        }
        return $default;
    }

    public function set(?string $key, mixed $value): bool
    {
        $success = true;
        foreach ($this->providers as $provider) {
            $result = $provider->set($key, $value);
            $success = $success && $result;
        }
        return $success;
    }
}