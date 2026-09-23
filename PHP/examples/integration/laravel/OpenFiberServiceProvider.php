<?php
/**
 * Laravel Service Provider per OpenFiber
 * 
 * Questo provider registra automaticamente il client OpenFiber
 * nel container di Laravel e lo rende disponibile per dependency injection.
 * 
 * @package OpenFiber\Integration\Laravel
 */

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenFiber\DynamicSoapClient;
use OpenFiber\Storage\FileWsdlStorage;
use OpenFiber\Storage\FileSoapLogStorage;
use OpenFiber\Storage\FileRetryQueueStorage;
use OpenFiber\SoapLogger;
use OpenFiber\RetryPolicy;

class OpenFiberServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registra le configurazioni
        $this->mergeConfigFrom(
            __DIR__.'/../../config/openfiber.php', 'openfiber'
        );
        
        // Registra gli storage come singleton
        $this->app->singleton(FileWsdlStorage::class, function ($app) {
            $storagePath = config('openfiber.storage.wsdl_path', storage_path('openfiber/wsdl'));
            return new FileWsdlStorage($storagePath);
        });
        
        $this->app->singleton(FileSoapLogStorage::class, function ($app) {
            $logsPath = config('openfiber.storage.logs_path', storage_path('openfiber/logs'));
            return new FileSoapLogStorage($logsPath);
        });
        
        $this->app->singleton(FileRetryQueueStorage::class, function ($app) {
            $retryPath = config('openfiber.storage.retry_path', storage_path('openfiber/retry'));
            return new FileRetryQueueStorage($retryPath);
        });
        
        // Registra logger e retry policy
        $this->app->singleton(SoapLogger::class, function ($app) {
            return new SoapLogger($app->make(FileSoapLogStorage::class));
        });
        
        $this->app->singleton(RetryPolicy::class, function ($app) {
            return new RetryPolicy(
                retryStorage: $app->make(FileRetryQueueStorage::class),
                maxAttempts: config('openfiber.retry.max_attempts', 3),
                retryableErrors: config('openfiber.retry.retryable_errors', [
                    'Server.Timeout',
                    'Server.Unavailable',
                ])
            );
        });
        
        // Registra il client principale
        $this->app->bind(DynamicSoapClient::class, function ($app) {
            return new DynamicSoapClient(
                serviceName: config('openfiber.service_name', 'default'),
                wsdlStorage: $app->make(FileWsdlStorage::class),
                logger: $app->make(SoapLogger::class),
                retryPolicy: $app->make(RetryPolicy::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Pubblica la configurazione
        $this->publishes([
            __DIR__.'/../../config/openfiber.php' => config_path('openfiber.php'),
        ], 'config');
        
        // Crea le directory di storage se non esistono
        $storagePaths = [
            storage_path('openfiber'),
            storage_path('openfiber/wsdl'),
            storage_path('openfiber/logs'),
            storage_path('openfiber/retry'),
        ];
        
        foreach ($storagePaths as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
}
