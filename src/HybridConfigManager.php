<?php
declare(strict_types=1);

namespace OpenFiber;

use PDO;
use OpenFiber\ConfigRepositoryInterface;

/**
 * Gestore Ibrido che coordina il salvataggio sia su DB (PDO) che su File.
 */
class HybridConfigManager implements ConfigRepositoryInterface 
{
    // PHP 8.4 Asymmetric Visibility & Property Hooks (Sintassi nativa avanzata)
    public private(set) string $storageDir {
        get => $this->storageDir ?: sys_get_temp_dir();
    }

    public function __construct(
        private PDO $db,
        string $storageDir
    ) {
        $this->storageDir = rtrim($storageDir, '/') . '/';
    }

    public function get(string $key): mixed 
    {
        $stmt = $this->db->prepare("SELECT config_value FROM of_package_config WHERE config_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn() ?: null;
    }

    public function set(string $key, mixed $value): bool 
    {
        // 1. Salva su Database
        $stmt = $this->db->prepare("INSERT INTO of_package_config (config_key, config_value) VALUES (?, ?) 
                                    ON DUPLICATE KEY UPDATE config_value = ?");
        $dbSuccess = $stmt->execute([$key, (string)$value, (string)$value]);

        // 2. Salva su File di configurazione locale locale
        $fileSuccess = file_put_contents($this->storageDir . 'config_manifest.json', json_encode([$key => $value], JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND) !== false;

        return $dbSuccess && $fileSuccess;
    }

    public function saveWsdl(string $serviceName, string $url, string $rawContent): bool 
    {
        // Percorso locale
        $localPath = $this->storageDir . $serviceName . '.wsdl';
        
        // Scrittura File nudo e crudo
        $fileSuccess = file_put_contents($localPath, $rawContent) !== false;

        // Scrittura DB
        $stmt = $this->db->prepare("INSERT INTO of_wsdl_storage (service_name, wsdl_url, wsdl_raw, local_path) 
                                    VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE wsdl_raw = ?, local_path = ?");
        $dbSuccess = $stmt->execute([$serviceName, $url, $rawContent, $localPath, $rawContent, $localPath]);

        return $fileSuccess && $dbSuccess;
    }

    /**
     * Validate service name to prevent injection and ensure proper format
     */
    private function isValidServiceName(string $serviceName): bool
    {
        // Allow only alphanumeric characters and underscores
        return preg_match('/^[A-Za-z0-9_]+$/', $serviceName) === 1;
    }
    
    /**
     * Get PDO instance for direct database access
     */
    public function getPdo(): PDO
    {
        return $this->db;
    }
}

