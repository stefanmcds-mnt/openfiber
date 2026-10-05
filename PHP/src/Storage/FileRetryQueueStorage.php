<?php
declare(strict_types=1);

namespace OpenFiber\Storage;

use OpenFiber\Interfaces\RetryQueueStorageInterface;

//class FileRetryQueueStorage implements RetryQueueStorageInterface
class FileRetryQueueStorage extends BaseWsdlStorage
{
    private string $storageDir;
    private string $queueFile;

    public function __construct(string $storageDir)
    {
        $this->storageDir = rtrim($storageDir, '/') . '/';
        $this->queueFile = $this->storageDir . 'retry_queue.json';
        
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
        
        if (!file_exists($this->queueFile)) {
            file_put_contents($this->queueFile, '[]');
        }
    }

    public function enqueueRetry(array $retryData): bool
    {
        $queue = json_decode(file_get_contents($this->queueFile), true) ?? [];
        $queue[] = array_merge(['id' => uniqid()], $retryData);
        return file_put_contents($this->queueFile, json_encode($queue)) !== false;
    }

    public function getNextRetry(): ?array
    {
        if (!file_exists($this->queueFile)) {
            return null;
        }
        
        $queue = json_decode(file_get_contents($this->queueFile), true) ?? [];
        
        foreach ($queue as $index => $item) {
            if (($item['status'] ?? 'pending') === 'pending') {
                $queue[$index]['status'] = 'processing';
                file_put_contents($this->queueFile, json_encode($queue));
                return $item;
            }
        }
        return null;
    }

    public function updateRetryStatus(int $id, string $status): bool
    {
        if (!file_exists($this->queueFile)) {
            return false;
        }
        
        $queue = json_decode(file_get_contents($this->queueFile), true) ?? [];
        
        foreach ($queue as $index => $item) {
            if (($item['id'] ?? '') === (string)$id) {
                $queue[$index]['status'] = $status;
                $queue[$index]['updated_at'] = date('Y-m-d H:i:s');
                return file_put_contents($this->queueFile, json_encode($queue)) !== false;
            }
        }
        return false;
    }
}