<?php

namespace Horus\Chronicles\Core;

use Horus\Chronicles\Factories\QueueFactory;
use Horus\Chronicles\Factories\StorageFactory;
use Horus\Chronicles\Utils\Sanitizer;
use Horus\Chronicles\Utils\PayloadLimiter;
use PDO;
use Predis\Client as PredisClient;

/**
 * Class Dispatcher
 *
 * Atua como um "Service Container" ou "Service Locator" para o projeto.
 * É responsável por instanciar e gerenciar os singletons de conexões (PDO, Redis)
 * e os drivers de fila e armazenamento, com base na configuração.
 * Centraliza a lógica de criação de objetos.
 */
final class Dispatcher
{
    private static ?array $config = null;
    private static array $instances = [];

    /**
     * Carrega a configuração e prepara o Dispatcher.
     * Este método deve ser chamado pelo Chronicles::init().
     *
     * @param string $configPath
     * @return void
     */
    public static function bootstrap(string $configPath): void
    {
        if (is_null(self::$config)) {
            self::$config = ConfigLoader::load($configPath);
        }
    }

    /**
     * Retorna a configuração completa ou uma chave específica.
     *
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function getConfig(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return self::$config;
        }
        return self::$config[$key] ?? $default;
    }

    /**
     * Retorna a instância singleton da conexão PDO.
     *
     * @return PDO
     */
    public static function getDbConnection(): PDO
    {
        if (!isset(self::$instances['pdo'])) {
            $config = self::getConfig('connections')['mysql'];
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            self::$instances['pdo'] = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        }
        return self::$instances['pdo'];
    }

    /**
     * Retorna a instância singleton da conexão Redis.
     *
     * @return Redis
     */
    public static function getRedisConnection() : PredisClient
    {
        if (!isset(self::$instances['redis'])) {
           $config = self::getConfig('connections')['redis'];

            // O Predis usa um formato de array um pouco diferente
            $parameters = [
                'scheme' => 'tcp',
                'host'   => $config['host'],
                'port'   => $config['port'],
                'timeout' => $config['timeout'],
            ];

            if (!empty($config['password'])) {
                $parameters['password'] = $config['password'];
            }

            if (isset($config['database'])) {
                $parameters['database'] = $config['database'];
            }
            
            self::$instances['redis'] = new PredisClient($parameters);
        }
        return self::$instances['redis'];
    }

    /**
     * Retorna a instância singleton do Sanitizer.
     *
     * @return Sanitizer
     */
    public static function getSanitizer(): Sanitizer
    {
        if (!isset(self::$instances['sanitizer'])) {
            self::$instances['sanitizer'] = new Sanitizer(self::getConfig('sanitizer', []));
        }
        return self::$instances['sanitizer'];
    }

    /**
     * Retorna a instância singleton do PayloadLimiter.
     *
     * @return PayloadLimiter
     */
    public static function getPayloadLimiter(): PayloadLimiter
    {
        if (!isset(self::$instances['payload_limiter'])) {
            self::$instances['payload_limiter'] = new PayloadLimiter(self::getConfig('payload_limiter', []));
        }
        return self::$instances['payload_limiter'];
    }

    /**
     * Retorna a instância da fábrica de filas.
     * @return QueueFactory
     */
    public static function getQueueFactory(): QueueFactory
    {
        if (!isset(self::$instances['queue_factory'])) {
            $factoryClass = self::getConfig('factories')['queue'];
            self::$instances['queue_factory'] = new $factoryClass(self::getConfig('queue'));
        }
        return self::$instances['queue_factory'];
    }

    /**
     * Retorna a instância da fábrica de armazenamento.
     * @return StorageFactory
     */
    public static function getStorageFactory(): StorageFactory
    {
        if (!isset(self::$instances['storage_factory'])) {
             $factoryClass = self::getConfig('factories')['storage'];
             self::$instances['storage_factory'] = new $factoryClass(self::getConfig('connections'));
        }
        return self::$instances['storage_factory'];
    }
    
    /**
     * Fecha conexões persistentes, se houver.
     * @return void
     */
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