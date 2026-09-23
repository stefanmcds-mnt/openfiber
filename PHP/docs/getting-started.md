# Guida Rapida - OpenFiber

Questa guida ti aiuterà a configurare ed utilizzare OpenFiber in pochi minuti.

## Prerequisiti

- PHP 8.4 o superiore
- Composer
- Estensioni PHP: `soap`, `mbstring`, `curl`

## Installazione

```bash
composer require stefanmcds-mnt/openfiber
```

## Setup Base (5 minuti)

### 1. Crea le Directory di Storage

```bash
mkdir -p storage/openfiber/{wsdl,logs,retry}
chmod -R 755 storage/openfiber
```

### 2. Configurazione Minima

```php
<?php
require_once 'vendor/autoload.php';

use OpenFiber\DynamicSoapClient;
use OpenFiber\Storage\FileWsdlStorage;
use OpenFiber\Storage\FileSoapLogStorage;
use OpenFiber\Storage\FileRetryQueueStorage;
use OpenFiber\SoapLogger;
use OpenFiber\RetryPolicy;

// Setup storage
$wsdlStorage = new FileWsdlStorage(__DIR__ . '/storage/wsdl');
$logStorage = new FileSoapLogStorage(__DIR__ . '/storage/logs');
$retryStorage = new FileRetryQueueStorage(__DIR__ . '/storage/retry');

// Setup logger e retry
$logger = new SoapLogger($logStorage);
$retryPolicy = new RetryPolicy(
    retryStorage: $retryStorage,
    maxAttempts: 3
);

// Crea il client
$client = new DynamicSoapClient(
    serviceName: 'activation_service',
    wsdlStorage: $wsdlStorage,
    logger: $logger,
    retryPolicy: $retryPolicy
);
```

### 3. Crea un DTO (Data Transfer Object)

```php
use OpenFiber\Interfaces\DynamicDtoInterface;

$activationDto = new class implements DynamicDtoInterface {
    public function __construct(
        public string $orderId = 'ORD-001',
        public string $customerCode = 'CUST-001',
        public string $serviceType = 'FTTH'
    ) {}
    
    public function toSoapPayload(): array
    {
        return [
            'CODICE_ORDINE_OLO' => $this->orderId,
            'CODICE_CLIENTE' => $this->customerCode,
            'TIPO_SERVIZIO' => $this->serviceType,
        ];
    }
    
    public function validate(): bool
    {
        return !empty($this->orderId);
    }
};
```

### 4. Invia la Richiesta

```php
try {
    $response = $client->send($activationDto);
    echo "✅ Successo: " . print_r($response, true);
    
} catch (\OpenFiber\Exception\SoapCommunicationException $e) {
    echo "❌ Errore SOAP: " . $e->getMessage();
    
} catch (\Exception $e) {
    echo "❌ Errore: " . $e->getMessage();
}
```

## Prossimi Passi

### Sviluppo
- Esplora gli [esempi completi](../examples/README.md)
- Impara sulla [gestione errori](error-handling.md)
- Scopri come [testare](testing.md) il tuo codice

### Produzione
- Configura il [Circuit Breaker](error-handling.md#circuit-breaker)
- Setup del [worker di retry](retry-queue.md#worker)
- Ottimizza le [performance](performance.md)

### Integrazione
- [Laravel](../examples/integration/laravel/README.md)
- [Symfony](../examples/integration/symfony/README.md)
- [Pure PHP](../examples/integration/pure-php/README.md)

## Troubleshooting

### Problema: "WSDL non trovato"

**Soluzione**: Assicurati che il WSDL sia stato scaricato:

```php
use OpenFiber\WsdlDownloader;

$downloader = new WsdlDownloader($wsdlStorage);
$downloader->download(
    serviceName: 'activation_service',
    wsdlUrl: 'https://gateway.openfiber.it/wsdl/activation.wsdl'
);
```

### Problema: "Permission denied" sullo storage

**Soluzione**: Verifica i permessi:

```bash
chmod -R 755 storage/openfiber
chown -R www-data:www-data storage/openfiber  # Per web server
```

### Problema: Errori di timeout

**Soluzione**: Aumenta il timeout SOAP:

```php
$client = new DynamicSoapClient(
    // ... altri parametri
    soapOptions: [
        'connection_timeout' => 60,
        'default_socket_timeout' => 60,
    ]
);
```

## Supporto

- 📖 [Documentazione Completa](README.md)
- 💬 [GitHub Discussions](https://github.com/stefanmcds-mnt/openfiber/discussions)
- 🐛 [Segnala un Bug](https://github.com/stefanmcds-mnt/openfiber/issues)

---

**Pronto per iniziare?** Vai agli [esempi pratici](../examples/README.md)!
