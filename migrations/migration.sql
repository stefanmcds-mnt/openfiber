-- 1. Tabella dei file WSDL (Salvataggio contenuto nudo e crudo)
CREATE TABLE `of_wsdl_storage` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_name` VARCHAR(100) NOT NULL UNIQUE, -- es. OLO_ActivationSetup_DIA
    `wsdl_url` VARCHAR(255) NOT NULL,
    `wsdl_raw` LONGTEXT NOT NULL,                -- Contenuto XML nudo e crudo
    `wsdl_var` LONGTEXT NOT NULL,                -- Json delle variabili del XML da valorizzare
    `local_path` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_wsdl_service` (`service_name`),
    INDEX `idx_wsdl_updated_at` (`updated_at`)
);

-- 2. Configurazione SOAP Client (Verso Open Fiber Gateway)
CREATE TABLE `of_soap_client_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `environment` ENUM('test', 'production') DEFAULT 'test',
    `endpoint_url` VARCHAR(255) NOT NULL,
    `server_cert_path` VARCHAR(255) NULL,        -- Certificato mutuo TLS (Mtls)
    `client_cert_path` VARCHAR(255) NULL,
    `client_key_path` VARCHAR(255) NULL,
    `passphrase` VARCHAR(100) NULL,
    `timeout` INT DEFAULT 30,
    INDEX `idx_soap_client_config_active` (`is_active`)
);

-- 3. Configurazione SOAP Server (Lato OLO - Per ricevere notifiche da OF)
CREATE TABLE `of_soap_server_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_name` VARCHAR(100) NOT NULL,
    `listening_endpoint` VARCHAR(255) NOT NULL,
    `allowed_openfiber_ips` TEXT NOT NULL,       -- Lista IP separati da virgola o notazione CIDR
    `allowed_openfiber_hosts` TEXT NOT NULL,     -- Lista HOSTS separati da virgola
    `require_client_cert` TINYINT(1) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    INDEX `idx_soap_server_config_active` (`is_active`)
);

-- 4. Configurazione Globale del Package
CREATE TABLE `of_package_config` (
    `config_key` VARCHAR(50) PRIMARY KEY,
    `config_value` TEXT NOT NULL,
    `description` VARCHAR(255) NULL,
    INDEX `idx_package_config_key` (`config_key`)
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
    INDEX `idx_queue_retry_id_notifica` (`id_notifica`)
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
    INDEX `idx_soap_logs_id_notifica` (`id_notifica`)
);

