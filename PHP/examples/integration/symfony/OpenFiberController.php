<?php
/**
 * Example Controller per Symfony
 * 
 * Mostra come utilizzare OpenFiber in un controller Symfony
 * con autowiring e dependency injection.
 */

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use OpenFiber\DynamicSoapClient;
use OpenFiber\Interfaces\DynamicDtoInterface;
use OpenFiber\Exception\SoapCommunicationException;
use OpenFiber\Exception\ConfigurationException;
use OpenFiber\Exception\ServiceUnavailableException;

#[Route('/api/openfiber')]
class OpenFiberController extends AbstractController
{
    public function __construct(
        private DynamicSoapClient $openFiberClient
    ) {}
    
    /**
     * Attiva un servizio Open Fiber
     */
    #[Route('/activate', name: 'openfiber_activate', methods: ['POST'])]
    public function activate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Validazione base
        if (empty($data['order_id']) || empty($data['customer_code'])) {
            return $this->json([
                'success' => false,
                'error' => 'Parametri mancanti',
            ], 400);
        }
        
        try {
            // Crea il DTO anonimo
            $dto = new class($data) implements DynamicDtoInterface {
                public function __construct(private array $data) {}
                
                public function toSoapPayload(): array
                {
                    return [
                        'CODICE_ORDINE_OLO' => $this->data['order_id'],
                        'CODICE_CLIENTE' => $this->data['customer_code'],
                        'TIPO_SERVIZIO' => $this->data['service_type'] ?? 'FTTH',
                    ];
                }
                
                public function validate(): bool
                {
                    return !empty($this->data['order_id']);
                }
            };
            
            // Invia la richiesta
            $response = $this->openFiberClient->send($dto);
            
            return $this->json([
                'success' => true,
                'data' => $response,
            ]);
            
        } catch (SoapCommunicationException $e) {
            return $this->json([
                'success' => false,
                'error' => 'soap_error',
                'message' => $e->getMessage(),
                'fault_code' => $e->getFaultCode(),
            ], 502);
            
        } catch (ServiceUnavailableException $e) {
            return $this->json([
                'success' => false,
                'error' => 'service_unavailable',
                'message' => 'Servizio temporaneamente non disponibile',
            ], 503);
            
        } catch (\Exception $e) {
            $this->container->get('logger')->error('OpenFiber error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->json([
                'success' => false,
                'error' => 'internal_error',
            ], 500);
        }
    }
    
    /**
     * Health check
     */
    #[Route('/health', name: 'openfiber_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'service' => 'openfiber',
            'timestamp' => time(),
        ]);
    }
}
