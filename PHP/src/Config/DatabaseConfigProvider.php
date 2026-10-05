<?php
declare(strict_types=1);

namespace OpenFiber\Config;

use OpenFiber\Interfaces\ConfigProvider;
use PDO;
use OpenFiber\Traits\CastableValueTrait;

class DatabaseConfigProvider implements ConfigProvider
{
    use CastableValueTrait;

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function get(?string $key, mixed $default = null): mixed
    {
        $stmt = $this->pdo->prepare("SELECT config_value FROM of_package_config WHERE config_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();

        if ($value === false) {
            return $default;
        }

        return $this->castValue($value);
    }

    public function set(?string $key, mixed $value): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO of_package_config (config_key, config_value) VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE config_value = ?"
        );
        
        return $stmt->execute([$key, (string)$value, (string)$value]);
    }

}