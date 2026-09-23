<?php
/**
 * Esempio di applicazione Pure PHP con OpenFiber
 * 
 * Questo file mostra un esempio completo di utilizzo di OpenFiber
 * in una applicazione PHP pura.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OpenFiber\Exception\SoapCommunicationException;
use OpenFiber\Exception\ServiceUnavailableException;

// ====================================================================
// GESTIONE RICHIESTA
// ====================================================================

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// ====================================================================
// ROUTING SEMPLICE
// ====================================================================

try {
    switch ($path) {
        case '/':
        case '/health':
            // Health check
            echo json_encode([
                'status' => 'ok',
                'service' => 'openfiber-pure-php',
                'timestamp' => time(),
            ]);
            break;
            
        case '/api/activate':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            
            // Leggi dati dal body
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['order_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Parametri mancanti']);
                break;
            }
            
            // Usa la funzione helper
            $response = openfiber_send([
                'CODICE_ORDINE_OLO' => $input['order_id'],
                'CODICE_CLIENTE' => $input['customer_code'] ?? '',
                'TIPO_SERVIZIO' => $input['service_type'] ?? 'FTTH',
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => $response,
            ]);
            break;
            
        case '/api/status':
            // Stato del sistema
            echo json_encode([
                'success' => true,
                'storage' => [
                    'wsdl' => is_dir(__DIR__ . '/storage/wsdl'),
                    'logs' => is_dir(__DIR__ . '/storage/logs'),
                    'retry' => is_dir(__DIR__ . '/storage/retry'),
                ],
                'php_version' => PHP_VERSION,
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
    }
    
} catch (SoapCommunicationException $e) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'soap_communication_error',
        'message' => $e->getMessage(),
        'fault_code' => $e->getFaultCode(),
    ]);
    
} catch (ServiceUnavailableException $e) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'service_unavailable',
        'message' => 'Il servizio non è al momento disponibile',
    ]);
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'internal_error',
        'message' => 'Si è verificato un errore interno',
    ]);
}
