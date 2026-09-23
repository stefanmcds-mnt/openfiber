-- 1. Tabella dei file WSDL (Salvataggio contenuto nudo e crudo)
CREATE TABLE `of_wsdl_storage` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nome del Servizio coincide con il nome file senza esensione. Es. OLO_ActivationSetup_DIA',
    `wsdl_url` VARCHAR(255) NOT NULL COMMENT 'Url OpenFiber dove è presente il file da scaricare. Es. https://ofs-test-ws.openfiber.it/Service/OLO_ActivationSetup_DIA?wsdl',
    `wsdl_raw` LONGTEXT NOT NULL COMMENT 'Contenuto nudo e crudo del file WSDL',
    `wsdl_var` LONGTEXT NOT NULL COMMENT 'JSON delle variabili da valorizzare. Es. [
    {
        "message": "ActivationSetupRequest",
        "part": "parameters",
        "element": "tns:ActivationSetup",
        "type": null,
        "usage": "input"
    },
    {
        "type": "ActivationSetupType",
        "category": "complexType",
        "attributes": [
            {
                "name": "orderId",
                "type": "string",
                "use": "required"
            },
            {
                "name": "customerCode",
                "type": "string",
                "use": "optional",
                "default": "000"
            }
        ]
    }
]',
    `local_path` VARCHAR(255) NULL COMMENT 'Path ove può essere slavato il file. Es. storage/wsdl/',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_wsdl_service` (`service_name`),
    INDEX `idx_wsdl_updated_at` (`updated_at`),
    COMMENT 'Tabella per i files WSDL che vengono scaricati da Endpoint'
);

-- 2. Configurazione SOAP Client (Verso Open Fiber Gateway)
CREATE TABLE `of_soap_client_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `environment` ENUM('test', 'production') DEFAULT 'test' COMMENT 'Tipologia di variabile. Es. test o production',
    `endpoint_url` VARCHAR(255) NOT NULL COMMENT 'Endpoint di OpenFiber. Es. https://ofs-test-ws.openfiber.it/Service/ per test oppure https://ofs-ws.openfiber.it/Service/ per produzione',
    `server_cert_path` VARCHAR(255) NULL COMMENT 'Path dei certificati SSL forniti da OpenFiber. Es. storage/cert/of/openfiber.cer',
    `client_cert_path` VARCHAR(255) NULL COMMENT 'Path del certificato SSL usato dal client. Es. storage/cert/client/client.cer oppure /etc/letsencrypt/live/cert.pem',
    `client_key_path` VARCHAR(255) NULL COMMENT 'Path del certificato privkey SSL usato dal client. Es. storage/cert/client/privkey.cer oppure /etc/letsencrypt/live/provkey.pem',
    `passphrase` VARCHAR(100) NULL COMMENT 'Password fornita da Openfiber o IT Api Key',
    `timeout` INT DEFAULT 30 COMMENT 'Impostazione di Timeout',
    INDEX `idx_soap_client_config_active` (`is_active`),
    COMMENT 'Tabella di configurazione del SOAP Client per connessione al B2B Gateway OpenFiber'
);

-- 3. Configurazione SOAP Server (Lato OLO - Per ricevere notifiche da OF)
CREATE TABLE `of_soap_server_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_name` VARCHAR(100) NOT NULL COMMENT 'Nome del Servizio WSDL esposto',
    `listening_endpoint` VARCHAR(255) NOT NULL COMMENT 'Endpoint esposto',
    `allowed_openfiber_ips` TEXT NOT NULL COMMENT 'Lista IP separati da virgola o notazione CIDR dei server B2B Gateway di OpenFiber',
    `allowed_openfiber_hosts` TEXT NOT NULL COMMENT 'Lista HOSTS separati da virgola dei server B2B Gateway di OpenFiber',
    `require_client_cert` TINYINT(1) DEFAULT 1 COMMENT 'Flag di abilitazione per usare i certificati',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Questo Endopoint è attivo',
    INDEX `idx_soap_server_config_active` (`is_active`),
    COMMENT 'Tabella di configurazione del server SOAP esposto al B2B Gateway di OpenFiber'
);

-- 4. Configurazione Globale del Package
CREATE TABLE `of_package_config` (
    `config_key` VARCHAR(50) PRIMARY KEY COMMENT 'Nome della chiave di configurazione',
    `config_value` TEXT NOT NULL COMMENT 'Valore della vhiave di configurazione',
    `description` VARCHAR(255) NULL COMMENT 'Descrizione di uso',
    INDEX `idx_package_config_key` (`config_key`),
    COMMENT 'Tabella di configurazione della libreria'
);

-- Inserimento configurazioni base indicative
INSERT INTO `of_package_config` (`config_key`, `config_value`, `description`) VALUES
('storage_mode', 'hybrid', 'Valori ammessi: file, database, hybrid'),
('wsdl_local_dir', '/var/www/storage/wsdl/', 'Percorso di salvataggio locale dei file WSDL'),
('olo_operator_code', '123', 'Codice di 3 cifre concordato con Open Fiber');

-- 1. Coda dei Retry Asincroni (Per gestire le politiche di pagina 12)
CREATE TABLE `of_queue_retry` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_name` VARCHAR(100) NOT NULL,
    `codice_ordine_olo` VARCHAR(18) NOT NULL,
    `id_notifica` VARCHAR(100) NOT NULL,
    `payload_json` LONGTEXT NOT NULL,                -- Dati originali del DTO anonimo
    `retry_type` ENUM('AUTOMATIC', 'NACK') NOT NULL, -- Politiche par. 5.1.4 / 5.1.5
    `attempts` INT DEFAULT 0,
    `max_attempts` INT DEFAULT 5,
    `status` ENUM('pending', 'processing', 'failed', 'completed') DEFAULT 'pending',
    `next_attempt_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_queue_retry_status_attempt` (`status`, `next_attempt_at`),
    INDEX `idx_queue_retry_service_ordine` (`service_name`, `codice_ordine_olo`),
    INDEX `idx_queue_retry_id_notifica` (`id_notifica`),
    COMMENT 'Tabella delle risposte asincrone'
);

-- 2. Log delle Transazioni Automatiche (Request/Response XML per certificazione)
CREATE TABLE `of_soap_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `direction` ENUM('CLIENT_OUT', 'SERVER_IN') NOT NULL, -- OUT = verso OF, IN = da OF
    `service_name` VARCHAR(100) NOT NULL,
    `codice_ordine_olo` VARCHAR(18) NULL,
    `id_notifica` VARCHAR(100) NULL,
    `request_xml` LONGTEXT NOT NULL,       -- XML nudo e crudo inviato/ricevuto
    `response_xml` LONGTEXT NULL,          -- XML nudo e crudo di risposta (ACK/NACK)
    `http_status` INT NULL,
    `execution_time_ms` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_soap_logs_service_dir` (`service_name`, `direction`),
    INDEX `idx_soap_logs_id_notifica` (`id_notifica`),
    COMMENT 'Tabella dei log di tranzazione SOAP'
);

