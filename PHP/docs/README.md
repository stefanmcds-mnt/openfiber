# OpenFiber - Documentazione Completa

OpenFiber è una libreria PHP 8.4 per l'interfacciamento dinamico e asincrono tra OLO e B2B Gateway di Open Fiber.

## 📚 Indice della Documentazione

### Getting Started
- [Guida Rapida](getting-started.md) - Inizia qui!
- [Installazione](installation.md) - Requisiti e setup
- [Configurazione](configuration.md) - Opzioni di configurazione

### Guide
- [Architettura](architecture.md) - Design e pattern utilizzati
- [Esempi Base](../examples/README.md) - Esempi pratici
- [Testing](testing.md) - Come testare con mock

### Integrazione
- [Laravel](../examples/integration/laravel/README.md) - Integrazione Laravel
- [Symfony](../examples/integration/symfony/README.md) - Integrazione Symfony
- [Pure PHP](../examples/integration/pure-php/README.md) - PHP Puro

### Deployment
- [Docker](../examples/deployment/docker/README.md) - Deploy con Docker
- [Serverless](../examples/deployment/serverless/README.md) - AWS Lambda

### Advanced
- [Gestione Errori](error-handling.md) - Exception e Circuit Breaker
- [Retry e Queue](retry-queue.md) - Sistema di retry automatico
- [Storage Backends](storage-backends.md) - File, Memory, Database
- [Performance](performance.md) - Ottimizzazioni e best practices

### API Reference
- [DynamicSoapClient](api/DynamicSoapClient.md) - Client principale
- [Interfaces](api/Interfaces.md) - Interfacce disponibili
- [Exceptions](api/Exceptions.md) - Gerarchia eccezioni
- [Storage](api/Storage.md) - Backend storage

### Migration
- [Migration](migration.md) - Guida migrazione

## 🚀 Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use OpenFiber\DynamicSoapClient;
use OpenFiber\Storage\FileWsdlStorage;
use OpenFiber\SoapLogger;
use OpenFiber\RetryPolicy;

// Setup
$client = new DynamicSoapClient(
    serviceName: 'my_service',
    wsdlStorage: new FileWsdlStorage('/path/to/wsdl'),
    logger: new SoapLogger(/* ... */),
    retryPolicy: new RetryPolicy(/* ... */)
);

// Usa il client
$response = $client->send($myDto);
```

## 💡 Caratteristiche Principali

- ✅ **PHP 8.4** - Property hooks e funzionalità moderne
- ✅ **Dependency Injection** - Architettura modulare e testabile
- ✅ **Storage Intercambiabili** - File, Memory, Database
- ✅ **Retry Automatico** - Con backoff esponenziale
- ✅ **Circuit Breaker** - Protezione da cascate di errori
- ✅ **Logging Completo** - Tracciamento transazioni SOAP
- ✅ **Testing** - Mock completi per unit test
- ✅ **Framework Agnostic** - Funziona ovunque

## 📖 Documentazione per Ruolo

### Sviluppatori
Inizia con:
1. [Guida Rapida](getting-started.md)
2. [Esempi Base](../examples/README.md)
3. [Testing](testing.md)

### DevOps
Inizia con:
1. [Docker Setup](../examples/deployment/docker/README.md)
2. [Configurazione](configuration.md)
3. [Performance](performance.md)

### Architetti
Inizia con:
1. [Architettura](architecture.md)
2. [Storage Backends](storage-backends.md)
3. [API Reference](api/README.md)

## 🆘 Support

- 📧 Email: info@stefan-mcds.it
- 🐛 Issues: [GitHub Issues](https://github.com/stefanmcds-mnt/openfiber/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/stefanmcds-mnt/openfiber/discussions)

## 📝 License

OpenFiber è rilasciato sotto licenza MIT. Vedi [LICENSE.md](../LICENSE.md) per dettagli.

## 🤝 Contribuire

Contributi sono benvenuti! Leggi [CONTRIBUTING.md](../CONTRIBUTING.md) per le linee guida.

---

**Ultima Modifica**: 2026-09-19  
**Versione Documentazione**: 2.0.0  
**Versione Libreria**: 2.0.0
