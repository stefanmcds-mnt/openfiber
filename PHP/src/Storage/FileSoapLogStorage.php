<?php
declare(strict_types=1);

namespace OpenFiber\Storage;

use OpenFiber\Interfaces\SoapLogStorageInterface;

class FileSoapLogStorage implements SoapLogStorageInterface
{
    private string $logDir;

    public function __construct(string $logDir)
    {
        $this->logDir = rtrim($logDir, '/') . '/';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public function logSoapTransaction(array $transactionData): bool
    {
        $filename = $this->logDir . date('Y-m-d') . '.log';
        $entry = json_encode($transactionData, JSON_PRETTY_PRINT) . "\n";
        return file_put_contents($filename, $entry, FILE_APPEND) !== false;
    }

    public function getSoapLogs(array $filters): array
    {
        $logs = [];
        $files = glob($this->logDir . '/*.log');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $entries = explode("\n", $content);
            foreach ($entries as $entry) {
                if (empty(trim($entry))) continue;
                $data = json_decode($entry, true);
                if ($data && $this->matchesFilters($data, $filters)) {
                    $logs[] = $data;
                }
            }
        }
        return $logs;
    }

    private function matchesFilters(array $data, array $filters): bool
    {
        foreach ($filters as $key => $value) {
            if (!isset($data[$key]) || $data[$key] !== $value) {
                return false;
            }
        }
        return true;
    }
}