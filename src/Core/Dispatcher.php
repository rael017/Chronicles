<?php
namespace Horus\Chronicles\Core;

use Horus\Chronicles\Factories\EventFactory;
use Horus\Chronicles\Factories\QueueFactory;
use Horus\Chronicles\Factories\StorageFactory;
use Horus\Chronicles\Utils\PayloadLimiter;
use Horus\Chronicles\Utils\Sanitizer;
use PDO;
use Predis\Client as PredisClient;

/**
 * Atua como um "Service Container" / "Service Locator" para o projeto.
 * Responsável por instanciar e gerenciar os singletons de todos os serviços.
 */
final class Dispatcher
{
    private static ?array $config = null;
    private static array $instances = [];

    public static function bootstrap(string $configPath): void
    {
        if (is_null(self::$config)) {
            self::$config = ConfigLoader::load($configPath);
        }
    }

    public static function getConfig(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return self::$config;
        }
        return self::$config[$key] ?? $default;
    }

    // --- CONSTRUTORES DE SERVIÇOS DE BAIXO NÍVEL ---

    public static function getDbConnection(): PDO
    {
        if (!isset(self::$instances['pdo'])) {
            $config = self::getConfig('connections')['mysql'];
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            self::$instances['pdo'] = new \PDO($dsn, $config['username'], $config['password'], $config['options'] ?? []);
        }
        return self::$instances['pdo'];
    }

    public static function getRedisConnection(): PredisClient
    {
        if (!isset(self::$instances['redis'])) {
            $config = self::getConfig('connections') ['redis'];
            self::$instances['redis'] = new PredisClient($config);
        }
        return self::$instances['redis'];
    }

    // --- CONSTRUTORES DE UTILITÁRIOS ---

    public static function getSanitizer(): Sanitizer
    {
        if (!isset(self::$instances['sanitizer'])) {
            $config = self::getConfig('security.sanitizer', []);
            self::$instances['sanitizer'] = new Sanitizer($config);
        }
        return self::$instances['sanitizer'];
    }

    public static function getPayloadLimiter(): PayloadLimiter
    {
        if (!isset(self::$instances['payload_limiter'])) {
            $config = self::getConfig('security.payload_limiter', []);
            self::$instances['payload_limiter'] = new PayloadLimiter($config);
        }
        return self::$instances['payload_limiter'];
    }
    
    // --- CONSTRUTORES DE FÁBRICAS E INTERFACES ---

    public static function getEventFactory(): EventFactory
    {
        if (!isset(self::$instances['event_factory'])) {
            // A EventFactory precisa do Sanitizer e do Limiter
            self::$instances['event_factory'] = new EventFactory(
                self::getSanitizer(),
                self::getPayloadLimiter()
            );
        }
        return self::$instances['event_factory'];
    }

    public static function getStorageFactory(): StorageFactory
    {
        if (!isset(self::$instances['storage_factory'])) {
            // A StorageFactory precisa das conexões e do file path
            self::$instances['storage_factory'] = new StorageFactory(
                self::getDbConnection(),
                self::getRedisConnection(),
                self::getConfig('connections.file.events_path', '')
            );
        }
        return self::$instances['storage_factory'];
    }
    
    // ESTE É O MÉTODO QUE VOCÊ QUERIA CORRIGIR, AGORA COMPLETO
    public static function getQueueFactory(): QueueFactory
    {
        if (!isset(self::$instances['queue_factory'])) {
            // A QueueFactory precisa de TUDO que seus drivers possam precisar.
            self::$instances['queue_factory'] = new QueueFactory(
                self::getConfig('queue', []),
                self::getRedisConnection(),
                self::getEventFactory(),
                // Para a SyncQueue, precisamos de um driver de storage. Vamos criá-lo aqui.
                self::getStorageFactory()->make(self::getConfig('storage_driver'))
            );
        }
        return self::$instances['queue_factory'];
    }

    public static function terminate(): void
    {
        if (isset(self::$instances['redis'])) {
            self::$instances['redis']->close();
        }
        // PDO com ATTR_PERSISTENT não tem um método close explícito gerenciado pelo PHP.
        self::$instances = [];
        self::$config = null;
    }
}

