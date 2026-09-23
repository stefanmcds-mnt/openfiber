# OpenFiber - Esempi di Utilizzo

Questa directory contiene esempi pratici per utilizzare OpenFiber in diversi scenari.

## 📁 Struttura

```
examples/
├── 01_basic_client.php              # Client base
├── 02_retry_configuration.php       # Retry avanzato
├── 03_circuit_breaker.php           # Circuit breaker
├── 04_multiple_storage_backends.php # Storage multipli
├── integration/                     # Integrazione framework
│   ├── laravel/                    # Laravel
│   ├── symfony/                    # Symfony
│   └── pure-php/                   # PHP puro
└── deployment/                      # Deploy
    ├── docker/                     # Docker
    └── serverless/                 # AWS Lambda
```

## 🚀 Esempi Base

### 1. Client Base
**File**: `01_basic_client.php`

Mostra come:
- Configurare storage e dipendenze
- Creare un DTO
- Inviare richieste SOAP
- Gestire errori

**Esegui**:
```bash
php examples/01_basic_client.php
```

### 2. Configurazione Retry
**File**: `02_retry_configuration.php`

Mostra come:
- Configurare retry policy avanzata
- Gestire errori recuperabili
- Monitorare la coda di retry
- Configurare backoff esponenziale

**Esegui**:
```bash
php examples/02_retry_configuration.php
```

### 3. Circuit Breaker
**File**: `03_circuit_breaker.php`

Mostra come:
- Implementare circuit breaker pattern
- Prevenire cascate di errori
- Gestire fail-fast
- Auto-recovery del servizio

**Esegui**:
```bash
php examples/03_circuit_breaker.php
```

### 4. Storage Multipli
**File`: `04_multiple_storage_backends.php`

Mostra come:
- Usare file storage
- Usare in-memory storage
- Switchare tra ambienti
- Best practices per ambiente

**Esegui**:
```bash
php examples/04_multiple_storage_backends.php
```

## 🔧 Integrazione Framework

### Laravel
Directory: `integration/laravel/`

Include:
- Service Provider
- Configurazione
- Controller esempio
- Routes

[Documentazione completa](integration/laravel/README.md)

### Symfony
Directory: `integration/symfony/`

Include:
- Configurazione servizi
- Controller esempio
- Bundle integration

[Documentazione completa](integration/symfony/README.md)

### Pure PHP
Directory: `integration/pure-php/`

Include:
- Bootstrap file
- Routing semplice
- Environment config
- Singleton pattern

[Documentazione completa](integration/pure-php/README.md)

## 🚢 Deployment

### Docker
Directory: `deployment/docker/`

Include:
- Dockerfile
- docker-compose.yml
- Multi-service setup

[Documentazione completa](deployment/docker/README.md)

### Serverless (AWS Lambda)
Directory: `deployment/serverless/`

Include:
- Lambda handler
- Serverless config
- Worker setup

[Documentazione completa](deployment/serverless/README.md)

## ⚙️ Setup Ambiente

Prima di eseguire gli esempi:

```bash
# 1. Installa dipendenze
composer install

# 2. Crea directory storage
mkdir -p storage/{wsdl,logs,retry}
chmod -R 755 storage

# 3. Copia e configura .env (se necessario)
cp .env.example .env
# Modifica .env con le tue credenziali
```

## 🧪 Testing

Per testare gli esempi:

```bash
# Syntax check
php -l examples/01_basic_client.php

# Esecuzione
php examples/01_basic_client.php
```

## 📚 Prossimi Passi

- Leggi la [documentazione completa](../docs/README.md)
- Esplora l'[API reference](../docs/api/README.md)
- Impara sulla [gestione errori](../docs/error-handling.md)
- Scopri le [best practices](../docs/performance.md)

## 🆘 Supporto

Hai problemi? 

- 📖 Consulta la [documentazione](../docs/README.md)
- 💬 Chiedi su [GitHub Discussions](https://github.com/stefanmcds-mnt/openfiber/discussions)
- 🐛 Segnala bug su [GitHub Issues](https://github.com/stefanmcds-mnt/openfiber/issues)
