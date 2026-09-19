<?php
declare(strict_types=1);

namespace OpenFiber;

use OpenFiber\ConfigRepositoryInterface;
use RuntimeException;

class WsdlDownloader 
{
    public function __construct(private ConfigRepositoryInterface $configManager) {}

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
                if (curl_errno($ch)) {
                    throw new RuntimeException(curl_error($ch));
                }
                curl_close($ch);

                if (empty($rawContent)) {
                    throw new RuntimeException("Il file WSDL scaricato è vuoto.");
                }

                // Salvataggio tramite il gestore di configurazione
                $saved = $this->configManager->saveWsdl($serviceName, $url, (string)$rawContent);
                $results[$serviceName] = $saved;

            } catch (\Throwable $e) {
                $results[$serviceName] = 'Error: ' . $e->getMessage();
            }
        }

        return $results;
    }
}

