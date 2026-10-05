<?php
declare(strict_types=1);

namespace OpenFiber\Storage;

use OpenFiber\Interfaces\WsdlStorageInterface;
use PDO;

//class PdoWsdlStorage implements WsdlStorageInterface
class PdoWsdlStorage extends BaseWsdlStorage
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getWsdl(string $serviceName): ?string
    {
        $stmt = $this->pdo->prepare('SELECT wsdl_raw FROM of_wsdl_storage WHERE service_name = ?');
        $stmt->execute([$serviceName]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['wsdl_raw'] : null;
    }

    public function getWsdlVar(string $serviceName): array
    {
        $stmt = $this->pdo->prepare('SELECT wsdl_var FROM of_wsdl_storage WHERE service_name = ?');
        $stmt->execute([$serviceName]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? json_decode($result['wsdl_var']) : null;
    }

    public function saveWsdl(string $serviceName, string $url, string $content, string $variablesJson = ''): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO of_wsdl_storage (service_name, wsdl_url, wsdl_raw, wsdl_var, created_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                 wsdl_url = VALUES(wsdl_url),
                 wsdl_raw = VALUES(wsdl_raw),
                 wsdl_var = VALUES(wsdl_var),
                 updated_at = NOW()'
        );
        return $stmt->execute([$serviceName, $url, $content, $variablesJson]);
    }
}