# OpenFiber

PHP 8.4 library for dynamic and asynchronous interfacing with the Open Fiber Gateway (Release 2.0 Delivery Process).

## Overview

OpenFiber is a PHP library designed to simplify communication with the Open Fiber Gateway using modern PHP 8.4 features. It provides dynamic SOAP client/server capabilities, automatic retry mechanisms, WSDL management, and comprehensive transaction logging.

## Key Features

- **PHP 8.4 Advanced Features**: Utilizes readonly classes, property hooks, and asymmetric visibility for immediate validation
- **Dynamic SOAP Communication**: Generates SOAP clients and servers dynamically from WSDL stored in database/file system
- **Automatic Retry Mechanism**: Implements asynchronous retry queue with exponential backoff for failed transmissions
- **Hybrid Configuration Management**: Stores configuration in both database and local files for flexibility and redundancy
- **Comprehensive Logging**: Automatically logs all SOAP transactions (request/response) for certification and debugging
- **WSDL Management**: Automatically downloads, caches, and manages WSDL files from Open Fiber endpoints
- **DTO Validation**: Immediate validation of data transfer objects using PHP 8.4 property hooks
- **Environment Agnostic**: Designed to work in various environments (web, CLI, containerized)

## Project Structure

```
OpenFiber/
├── src/
│   ├── DynamicSoapClient.php       # Dynamic SOAP client with logging and retry
│   ├── DynamicSoapServer.php       # Dynamic SOAP server for receiving OF notifications
│   ├── DynamicDtoInterface.php     # Interface for data transfer objects
│   ├── HybridConfigManager.php     # Hybrid configuration (DB + file system)
│   ├── ConfigRepositoryInterface.php # Configuration repository interface
│   ├── RetryQueueManager.php       # Manages asynchronous retry queue
│   ├── AsyncRetryWorker.php        # Worker for processing retry queue
│   └── WsdlDownloader.php          # Downloads and caches WSDL files
├── migrations/
│   └── migration.sql               # Database schema for OpenFiber
├── plans/
│   └── improvement_plan.md         # Improvement proposals for the project
├── esempio_utilizzo.php            # Usage example
├── caso_uso.php                    # Use case example
├── admin_wizard.php                # Administration utility
├── esempio_soap_server_produzione.php # Production SOAP server example
├── composer.json                   # Project dependencies
└── README.md                       # This file
```

## Installation

### Prerequisites

- PHP 8.4 or higher
- PDO extension
- SOAP extension
- cURL extension
- JSON extension
- Composer

### Via Composer

Add OpenFiber as a dependency in your project:

```bash
composer require stefanmcds-mnt/openfiber
```

### Manual Installation

1. Copy the `src/` directory to your project
2. Ensure Composer autoloading is configured for the `OpenFiber` namespace
3. Run the migration script to create the required database tables

## Database Setup

Run the SQL migration script located in `migrations/migration.sql` to create the necessary tables:

```sql
-- Tables for WSDL storage, SOAP client/server configuration, package config, retry queue, and SOAP logs
```

## Usage Examples

### Basic SOAP Client Usage

See `esempio_utilizzo.php` for a complete example:

```php
<?php
use OpenFiber\DynamicSoapClient;
use OpenFiber\DynamicDtoInterface;
use InvalidArgumentException;
use PDO;

// 1. Define and instantiate DTO on-the-fly as anonymous class with PHP 8.4 rules
$attivazioneAlVolo = new #[Readonly] class(
    codiceOperatore: '321',
    codiceOrdineOlo: '321_ORD20260918',
    idNotifica: 'NOT-' . uniqid(),
    cognomeCliente: 'Rossi S.p.A.',
    telefono: '0141123456',
    idBuilding: '01005_0023_00012',
    pop: 'POP_AT_01',
    profilo: 'DIA_1GB_100MB'
) implements DynamicDtoInterface {
    // Immediate validation via PHP 8.4 Property Hooks
    public string $CODICE_ORDINE_OLO {
        set {
            if (strlen($value) > 18) throw new InvalidArgumentException("Maximum length 18 characters.");
            if (preg_match('/[\x5C\x2F\x2A\x3F\x3C\x3E\x7C\x24]/', $value)) {
                throw new InvalidArgumentException("Special characters not allowed in CODICE_ORDINE_OLO.");
            }
            $this->CODICE_ORDINE_OLO = $value;
        }
    }

    public string $CODICE_OPERATORE {
        set {
            if (strlen($value) !== 3) throw new InvalidArgumentException("Operator code must be 3 digits.");
            $this->CODICE_OPERATORE = $value;
        }
    }

    public function __construct(
        string $codiceOperatore,
        string $codiceOrdineOlo,
        public string $ID_NOTIFICA,
        public string $COGNOME_CLIENTE,
        public string $RECAPITO_TELEFONICO_CLIENTE_1,
        public string $ID_BUILDING,
        public string $IDENTIFICATIVO_DEL_POP,
        public string $PROFILO
    ) {
        $this->CODICE_OPERATORE = $codiceOperatore;
        $this->CODICE_ORDINE_OLO = $codiceOrdineOlo;
    }

    public function toSoapPayload(): array {
        return [
            'CODICE_OPERATORE' => $this->CODICE_OPERATORE,
            'CODICE_ORDINE_OLO' => $this->CODICE_ORDINE_OLO,
            'DATA_NOTIFICA' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:sP'),
            'ID_NOTIFICA' => $this->ID_NOTIFICA,
            'COGNOME_CLIENTE' => $this->COGNOME_CLIENTE,
            'RECAPITO_TELEFONICO_CLIENTE_1' => $this->RECAPITO_TELEFONICO_CLIENTE_1,
            'ID_BUILDING' => $this->ID_BUILDING,
            'IDENTIFICATIVO_DEL_POP' => $this->IDENTIFICATIVO_DEL_POP,
            'PROFILO' => $this->PROFILO,
            'DATA_PREVISTA_ATTIVAZIONE' => (new \DateTimeImmutable('+14 days'))->format('Y-m-d')
        ];
    }
};

// 2. Pass the instantiated object directly to the client constructor and send
$pdo = new PDO('mysql:host=localhost;dbname=openfiber;charset=utf8mb4', 'username', 'password');
$client = new DynamicSoapClient($pdo, 'OLO_ActivationSetup_DIA', $attivazioneAlVolo);
$response = $client->send();

print_r($response);
```

### SOAP Server Usage

See `esempio_soap_server_produzione.php` for setting up a SOAP server to receive notifications from Open Fiber:

```php
<?php
use OpenFiber\DynamicSoapServer;
use PDO;

$pdo = new PDO('mysql:host=localhost;dbname=openfiber;charset=utf8mb4', 'username', 'password');

$server = new DynamicSoapServer($pdo, 'OLO_ActivationSetup_DIA');

$server->handle(function(string $method, array $data) {
    // Your business logic here
    // Example: Save to local CRM, process the request, etc.
    
    return [
        'success' => true,
        'code' => '000',
        'message' => 'Request processed successfully'
    ];
});
```

### WSDL Management

Download and cache WSDL files automatically:

```php
<?php
use OpenFiber\WsdlDownloader;
use OpenFiber\HybridConfigManager;
use PDO;

$pdo = new PDO('mysql:host=localhost;dbname=openfiber;charset=utf8mb4', 'username', 'password');
$configManager = new HybridConfigManager($pdo, '/path/to/storage');

$downloader = new WsdlDownloader($configManager);
$results = $downloader->download([
    'https://ofs-test-ws.openfiber.it/Service/OLO_ActivationSetup_DIA?wsdl',
    'https://ofs-test-ws.openfiber.it/Service/OLO_ActivationSetup_FTTH?wsdl'
]);

foreach ($results as $serviceName => $result) {
    if ($result === true) {
        echo "WSDL for {$serviceName} downloaded and cached successfully.\n";
    } else {
        echo "Failed to download WSDL for {$serviceName}: {$result}\n";
    }
}
```

### Processing Retry Queue

Process failed transmissions from the retry queue:

```php
<?php
use OpenFiber\AsyncRetryWorker;
use PDO;

$pdo = new PDO('mysql:host=localhost;dbname=openfiber;charset=utf8mb4', 'username', 'password');
$worker = new AsyncRetryWorker($pdo);

// Process next job in the queue (can be called periodically via cron or supervisor)
$worker->processNextJob();
```

## Configuration

OpenFiber uses a hybrid configuration system that stores settings in both database and local files.

### Default Configuration Keys

- `storage_mode`: Values allowed: `file`, `database`, `hybrid` (default: `hybrid`)
- `wsdl_local_dir`: Path for local WSDL storage (default: `/var/www/storage/wsdl/`)
- `olo_operator_code`: 3-digit operator code agreed with Open Fiber (default: `123`)

Configuration can be managed through the `HybridConfigManager` class or directly in the `of_package_config` database table.

## Environment Variables

For environment-agnostic deployment, OpenFiber supports configuration via environment variables:

- `OPENFIBER_STORAGE_MODE`: Storage mode (`file`, `database`, `hybrid`)
- `OPENFIBER_WSDL_DIR`: Local directory for WSDL storage
- `OPENFIBER_OLO_OPERATOR_CODE`: Operator code for Open Fiber communications
- Database connection variables (if using database storage):
  - `OPENFIBER_DB_HOST`
  - `OPENFIBER_DB_NAME`
  - `OPENFIBER_DB_USER`
  - `OPENFIBER_DB_PASS`
  - `OPENFIBER_DB_PORT` (default: 3306)
  - `OPENFIBER_DB_CHARSET` (default: utf8mb4)

## Improvement Proposals

See `plans/improvement_plan.md` for detailed proposals to make the project more independent and environment-agnostic while maintaining abstraction and dynamicity.

Key improvement areas include:
- Separation of concerns through middleware/decorator patterns
- More flexible configuration system with environment variable support
- Persistence layer abstraction for different storage backends
- Enhanced error handling with specific exception hierarchies
- Improved testability through dependency injection
- Better support for different execution environments (web, CLI, container, serverless)

## Testing

Run the test suite with PHPUnit:

```bash
vendor/bin/phpunit
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support, please open an issue in the repository or contact:
- Email: info@stefan-mcds.it
- Website: https://www.stefan-mcds.it

## Acknowledgments

- Built with PHP 8.4 modern features
- Inspired by Open Fiber Gateway specifications Release 2.0
- Utilizes PSR-4 autoloading standards