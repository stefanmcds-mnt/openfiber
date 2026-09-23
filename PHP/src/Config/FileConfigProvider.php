<?php
declare(strict_types=1);

namespace OpenFiber\Config;

use OpenFiber\Interfaces\ConfigProvider;

class FileConfigProvider implements ConfigProvider
{
    private ?string $configDir;
    private ?array $configCache = [];

    public function __construct(?string $configDir)
    {
        $this->configDir = rtrim($configDir, '/') . '/';
        // Ensure directory exists
        if (!is_dir($this->configDir)) {
            mkdir($this->configDir, 0755, true);
        }
    }

    public function get(?string $key, mixed $default = null): mixed
    {
        // Check cache first
        if (isset($this->configCache[$key])) {
            return $this->configCache[$key];
        }

        $filePath = $this->configDir . $key . '.php';
        if (!file_exists($filePath)) {
            return $default;
        }

        $config = include $filePath;
        $value = $config[$key] ?? $default;
        
        $this->configCache[$key] = $value;
        return $value;
    }

    public function set(?string $key, mixed $value): bool
    {
        $filePath = $this->configDir . $key . '.php';
        $dir = dirname($filePath);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $content = "<?php\nreturn [\n    '" . $key . "' => " . var_export($value, true) . ",\n];\n";
        
        return file_put_contents($filePath, $content) !== false;
    }
}