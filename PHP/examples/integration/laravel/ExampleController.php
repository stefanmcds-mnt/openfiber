<?php
/**
 * Example Controller per Laravel
 * 
 * Mostra come utilizzare OpenFiber in un controller Laravel
 * con dependency injection e gestione errori appropriata.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenFiber\DynamicSoapClient;
use OpenFiber\Interfaces\DynamicDtoInterface;
use OpenFiber\Exception\SoapCommunicationException;
use OpenFiber\Exception\ConfigurationException;
use OpenFiber\Exception\ServiceUnavailableException;

class OpenFiberController extends Controller
{
    /**
     * Constructor con dependency injection
     */
    public function __construct(
        private DynamicSoapClient $openFiberClient
    ) {}
    
    /**
     * Attiva un servizio Open Fiber
     */
    public function activate(Request $request): JsonResponse
    {
        // Valida la richiesta
        $validated = $request->validate([
            'order_id' => 'required|string',
            'customer_code' => 'required|string',
            'service_type' => 'required|in:FTTH,FTTC',
            'address' => 'required|string',
        ]);
        
        try {
            // Crea il DTO
            $dto = new class($validated) implements DynamicDtoInterface {
                public function __construct(private array $data) {}
                
                public function toSoapPayload(): array
                {
                    return [
                        'CODICE_ORDINE_OLO' => $this->data['order_id'],
                        'CODICE_CLIENTE' => $this->data['customer_code'],
                        'TIPO_SERVIZIO' => $this->data['service_type'],
                        'INDIRIZZO' => $this->data['address'],
                    ];
                }
                
                public function validate(): bool
                {
                    return !empty($this->data['order_id']);
                }
            };
            
            // Invia la richiesta
            $response = $this->openFiberClient->send($dto);
            
            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Attivazione completata con successo',
            ]);
            
        } catch (SoapCommunicationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'soap_communication_error',
                'message' => $e->getMessage(),
                'fault_code' => $e->getFaultCode(),
            ], 502);
            
        } catch (ServiceUnavailableException $e) {
            return response()->json([
                'success' => false,
                'error' => 'service_unavailable',
                'message' => 'Il servizio OpenFiber non è al momento disponibile',
                'retry_after' => 60,
            ], 503);
            
        } catch (ConfigurationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'configuration_error',
                'message' => $e->getMessage(),
            ], 500);
            
        } catch (\Exception $e) {
            // Log dell'errore
            \Log::error('OpenFiber error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'internal_error',
                'message' => 'Si è verificato un errore interno',
            ], 500);
        }
    }
    
    /**
     * Verifica la disponibilità di un servizio
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address' => 'required|string',
        ]);
        
        // Implementazione simile a activate()
        // ...
        
        return response()->json([
            'success' => true,
            'available' => true,
            'service_types' => ['FTTH', 'FTTC'],
        ]);
    }
}
