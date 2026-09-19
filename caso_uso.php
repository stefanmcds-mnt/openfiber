<?php
require_once __DIR__ . '/vendor/autoload.php';

use OpenFiber\HybridConfigManager;
use OpenFiber\WsdlDownloader;
use OpenFiber\DynamicSoapClient;
use OpenFiber\DynamicSoapServer;

// 1. Inizializzazione Database e Configurazione
$pdo = new PDO('mysql:host=localhost;dbname=openfiber_db', 'user', 'password', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
$configManager = new HybridConfigManager($pdo, __DIR__ . '/storage/wsdl');

// 2. Download di una lista di WSDL di Open Fiber
$downloader = new WsdlDownloader($configManager);
$wsdlUrls = [
    'https://ofs-test-ws.openfiber.it/Service/OLO_ActivationSetup_DIA?wsdl',
    'https://ofs-test-ws.openfiber.it/Service/OLO_ChangeSetup_DIA?wsdl'
];
$downloader->download($wsdlUrls);

// 3. Esecuzione Richiesta CLIENT Dinamica (Invio Ordine a Open Fiber)
try {
    $client = new DynamicSoapClient($pdo, 'OLO_ActivationSetup_DIA');
    
    // Struttura dati conforme ai campi del tracciato tecnico Open Fiber (Pagina 21 del documento)
    $payload = [
        'CODICE_OPERATORE' => '123',
        'CODICE_ORDINE_OLO' => '123_ORD99982',
        'DATA_NOTIFICA' => date('Y-m-d\TH:i:sP'),
        'ID_NOTIFICA' => uniqid('NOT-'),
        'COGNOME_CLIENTE' => 'Rossi S.r.l.',
        'RECAPITO_TELEFONICO_CLIENTE_1' => '021234567',
        'ID_BUILDING' => '030120_VIA_ROMA_10', // Identificativo civico univoco
        'IDENTIFICATIVO_DEL_POP' => 'POP_MI_1',
        'PROFILO' => 'DIA_1GB_100MB',
        'DATA_PREVISTA_ATTIVAZIONE' => date('Y-m-d', strtotime('+15 days'))
    ];

    // Chiamata dinamica al metodo previsto dal WSDL
    $response = $client->OLO_ActivationSetup_DIA($payload);

    echo "Risposta Ricevuta con Successo da Open Fiber (ACK):\n";
    print_r($response);

} catch (\Exception $e) {
    echo "Errore nell'invio della richiesta: " . $e->getMessage();
}

// 4. Inizializzazione SERVER per ricevere notifiche asincrone (es: OF_StatusUpdate_DIA)
// Questo blocco va inserito nella tua rotta / endpoint dedicato alle notifiche OLO
$server = new DynamicSoapServer($pdo, 'OF_StatusUpdate_DIA');
$server->handle(function(string $method, $data) {
    // Logica applicativa interna: Aggiorna lo stato nel tuo CRM/Database
    // $data conterrà campi come STATO_ORDINE, CODICE_MOTIVAZIONE, MOTIVAZIONE (Pagina 32)
    
    return [
        'success' => true,
        'message' => 'Notifica acquisita correttamente nel sistema OLO'
    ];
});

