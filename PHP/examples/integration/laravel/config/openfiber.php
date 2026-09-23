<?php
/**
 * Configurazione OpenFiber per Laravel
 * 
 * Dopo aver pubblicato questo file con:
 * php artisan vendor:publish --provider="App\Providers\OpenFiberServiceProvider"
 * 
 * Configura le variabili d'ambiente nel file .env
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | Service Name
    |--------------------------------------------------------------------------
    |
    | Il nome del servizio OpenFiber da utilizzare
    |
    */
    'service_name' => env('OPENFIBER_SERVICE_NAME', 'default'),
    
    /*
    |--------------------------------------------------------------------------
    | WSDL Configuration
    |--------------------------------------------------------------------------
    |
    | URL del WSDL e credenziali di accesso
    |
    */
    'wsdl_url' => env('OPENFIBER_WSDL_URL', 'https://gateway.openfiber.it/wsdl/default.wsdl'),
    'username' => env('OPENFIBER_USERNAME', ''),
    'password' => env('OPENFIBER_PASSWORD', ''),
    
    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    |
    | Percorsi per lo storage di WSDL, logs e retry queue
    |
    */
    'storage' => [
        'wsdl_path' => env('OPENFIBER_WSDL_PATH', storage_path('openfiber/wsdl')),
        'logs_path' => env('OPENFIBER_LOGS_PATH', storage_path('openfiber/logs')),
        'retry_path' => env('OPENFIBER_RETRY_PATH', storage_path('openfiber/retry')),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configurazione per il sistema di retry automatico
    |
    */
    'retry' => [
        'enabled' => env('OPENFIBER_RETRY_ENABLED', true),
        'max_attempts' => env('OPENFIBER_RETRY_MAX_ATTEMPTS', 3),
        'retryable_errors' => [
            'Server.Timeout',
            'Server.Unavailable',
            'Network.ConnectionLost',
            'HTTP.502',
            'HTTP.503',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Configuration
    |--------------------------------------------------------------------------
    |
    | Configurazione per il circuit breaker
    |
    */
    'circuit_breaker' => [
        'enabled' => env('OPENFIBER_CIRCUIT_BREAKER_ENABLED', true),
        'failure_threshold' => env('OPENFIBER_CB_FAILURE_THRESHOLD', 5),
        'timeout_seconds' => env('OPENFIBER_CB_TIMEOUT', 60),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | SOAP Options
    |--------------------------------------------------------------------------
    |
    | Opzioni aggiuntive per SoapClient
    |
    */
    'soap_options' => [
        'trace' => true,
        'exceptions' => true,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'connection_timeout' => env('OPENFIBER_CONNECTION_TIMEOUT', 30),
    ],
    
];
