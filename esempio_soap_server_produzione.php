<?php
// percorso: /var/www/html/public/openfiber/notifica-stato.php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use OpenFiber\DynamicSoapServer;

// Inizializzazione Database globale
$pdo = new PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Istanziamo il Server indicando quale servizio risiede su questo endpoint (es. Notifica Cambiamenti Stato)
$server = new DynamicSoapServer($pdo, 'OF_StatusUpdate_DIA');

// Avviamo l'ascolto passando la business logic sotto forma di callback (Callable)
$server->handle(function(string $method, ?stdClass $arguments) use ($pdo) {
    
    // I campi dell'oggetto sono mappati e pronti da essere usati (Riferimento Pagina 32 del documento)
    $statoOrdine = $arguments->STATO_ORDINE ?? null; // Es: "4" = Sospeso, "6" = Cessato
    $codiceOrdineOlo = $arguments->CODICE_ORDINE_OLO ?? null;
    $causaleCodice = $arguments->CODICE_MOTIVAZIONE ?? null; // Es: C05, D05 (Pagina 41)
    
    try {
        // Esempio: Aggiorna lo stato dell'ordine nel tuo DB Interno / CRM
        $stmt = $pdo->prepare("UPDATE miei_ordini SET stato_of = ?, ultima_causale = ?, updated_at = NOW() WHERE codice_olo = ?");
        $stmt->execute([$statoOrdine, $causaleCodice, $codiceOrdineOlo]);

        // Restituiamo un array che indica il successo. Il Server lo convertirà in un ACK formale (Esito 0)
        return [
            'success' => true,
            'message' => 'Notifica ricevuta ed elaborata nei sistemi OLO.'
        ];
    } catch (Throwable $e) {
        // Se qualcosa va storto restituiamo false. Il server risponderà con un NACK sincrono (Esito 1)
        return [
            'success' => false,
            'code' => 'B99', // Tuo codice di errore interno per KO tecnico
            'message' => 'Errore durante il salvataggio locale della notifica: ' . $e->getMessage()
        ];
    }
});

