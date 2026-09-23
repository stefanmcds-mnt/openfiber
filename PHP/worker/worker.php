#!/usr/bin/env php
<?php
// percorso: /tuo_progetto/worker.php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    echo "Questo script può essere eseguito solo da riga di comando.\n";
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

use OpenFiber\AsyncRetryWorker;

echo "[" . date('Y-m-d H:i:s') . "] Avvio del Worker di Retry Open Fiber...\n";

// Inizializzazione DB
try {
    $pdo = new PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
} catch (\Exception $e) {
    echo "Errore di connessione al Database: " . $e->getMessage() . "\n";
    exit(1);
}

$worker = new AsyncRetryWorker($pdo);

// Eseguiamo un loop infinito controllato per elaborare la coda (Pattern Daemon)
$maxExecutionSeconds = 58; // Si ferma poco prima del minuto se usato con Crontab
$startTime = time();

while ((time() - $startTime) < $maxExecutionSeconds) {
    try {
        // Cerca un job pendente, esegue il mTLS verso OF e salva i log XML in automatico
        $worker->processNextJob();
    } catch (\Throwable $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Eccezione durante l'elaborazione del job: " . $e->getMessage() . "\n";
    }
    
    // Evita sovraccarico della CPU in caso di coda vuota
    usleep(250000); // Pausa di 250ms ad ogni ciclo
}

echo "[" . date('Y-m-d H:i:s') . "] Elaborazione terminata per timeout di ciclo di sicurezza.\n";
exit(0);

