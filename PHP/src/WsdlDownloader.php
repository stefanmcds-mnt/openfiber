<?php
declare(strict_types=1);

namespace OpenFiber;

use DOMDocument;
use DOMXPath;
use OpenFiber\ConfigRepositoryInterface;
use OpenFiber\Exception\WsdlNotFoundException;
use Throwable;

class WsdlDownloader
{
    public function __construct(private ConfigRepositoryInterface $configManager) {}
    
    /**
     * Estrae le variabili dal contenuto WSDL
     * @param string $wsdlContent
     * @return string JSON delle variabili estratte
     */
    private function extractWsdlVariables(string $wsdlContent): array|string
    {
        // Carica il XML
        $dom = new DOMDocument();
        // Suppress warnings for malformed XML
        libxml_use_internal_errors(true);
        $dom->loadXML($wsdlContent);
        libxml_clear_errors();
        
        // Namespace comuni nei WSDL
        $namespaces = [
            'soap' => 'http://schemas.xmlsoap.org/wsdl/soap/',
            'wsdl' => 'http://schemas.xmlsoap.org/wsdl/',
            'xsd' => 'http://www.w3.org/2001/XMLSchema',
        ];
        
        $xpath = new DOMXPath($dom);
        foreach ($namespaces as $prefix => $uri) {
            $xpath->registerNamespace($prefix, $uri);
        }
        
        $variables = [];
        
        // Estrarre messaggi e parti
        $messages = $xpath->query('//wsdl:message');
        foreach ($messages as $message) {
            $messageName = $message->getAttribute('name');
            $parts = $xpath->query('./wsdl:part', $message);
            
            foreach ($parts as $part) {
                $partName = $part->getAttribute('name');
                $element = $part->getAttribute('element');
                $type = $part->getAttribute('type');
                
                $variables[] = [
                    'message' => $messageName,
                    'part' => $partName,
                    'element' => $element,
                    'type' => $type,
                    'usage' => 'input' // o output a seconda del contesto
                ];
            }
        }
        
        // Estrarre tipi complessi e elementi
        $types = $xpath->query('//xsd:complexType | //xsd:simpleType | //xsd:element');
        foreach ($types as $typeNode) {
            $typeName = $typeNode->getAttribute('name');
            if (!$typeName) {
                // Elemento anonimo
                continue;
            }
            
            // Estrarre attributi/elementi figli
            $attributes = [];
            $attributeNodes = $xpath->query('./xsd:attribute | ./xsd:complexContent/xsd:extension/xsd:attribute', $typeNode);
            foreach ($attributeNodes as $attrNode) {
                $attributes[] = [
                    'name' => $attrNode->getAttribute('name'),
                    'type' => $attrNode->getAttribute('type'),
                    'use' => $attrNode->getAttribute('use') ?? 'optional',
                    'default' => $attrNode->getAttribute('default')
                ];
            }
            
            if ($attributes) {
                $variables[] = [
                    'type' => $typeName,
                    'category' => 'complexType',
                    'attributes' => $attributes
                ];
            }
        }
        
        return json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Scarica una lista o un singolo URL di WSDL
     * @param string|array<string> $urls
     */
    public function download(string|array $urls): array
    {
        $urls = is_array($urls) ? $urls : [$urls];
        $results = [];

        foreach ($urls as $url) {
            // Estrazione dinamica del nome del servizio dall'URL
            // es: https://ofs-test-ws.openfiber.it/Service/OLO_ActivationSetup_DIA?wsdl -> OLO_ActivationSetup_DIA
            preg_match('/\/Service\/([A-Za-z0-9_]+)/', $url, $matches);
            $serviceName = $matches[1] ?? 'UnknownService_' . uniqid();

            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Controllo HTTPS obbligatorio
                
                $rawContent = curl_exec($ch);
                $curlError = curl_error($ch);
                $curlErrno = curl_errno($ch);
                curl_close($ch);
                
                if ($curlErrno) {
                    throw WsdlNotFoundException::downloadFailed(
                        $serviceName,
                        $url,
                        "Errore cURL #{$curlErrno}: {$curlError}"
                    );
                }

                if (empty($rawContent) || !is_string($rawContent)) {
                    throw WsdlNotFoundException::downloadFailed(
                        $serviceName,
                        $url,
                        "Il file WSDL scaricato è vuoto o non valido"
                    );
                }

                // Nuova logica per estrazione variabili
                $variablesJson = $this->extractWsdlVariables($rawContent);

                // Salvataggio con entrambe le informazioni
                $saved = $this->configManager->saveWsdl($serviceName, $url, (string)$rawContent, $variablesJson);
                $results[$serviceName] = $saved;

            } catch (Throwable $e) {
                // Se è già una WsdlNotFoundException, mantienila
                if ($e instanceof WsdlNotFoundException) {
                    $results[$serviceName] = 'Error: ' . $e->getMessage();
                } else {
                    // Altrimenti avvolgi l'errore in WsdlNotFoundException
                    $wsdlException = WsdlNotFoundException::downloadFailed(
                        $serviceName,
                        $url,
                        $e->getMessage()
                    );
                    $results[$serviceName] = 'Error: ' . $wsdlException->getMessage();
                }
            }
        }

        return $results;
    }
}

