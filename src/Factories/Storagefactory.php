<?php

namespace Horus\Chronicles\Factories;

use Horus\Chronicles\Contracts\StorageInterface;
use Horus\Chronicles\Core\Dispatcher;
use Horus\Chronicles\Storage\FileStorage;
use Horus\Chronicles\Storage\MySQLStorage;
use Horus\Chronicles\Storage\NullStorage;
use Horus\Chronicles\Storage\RedisStorage;
use Horus\Chronicles\Utils\Helpers;
use InvalidArgumentException;

/**
 * Class StorageFactory
 *
 * Cria instâncias dos drivers de armazenamento com base na configuração.
 */
class StorageFactory
{
    /**
     * @param array $config A seção 'connections' da configuração principal.
     */
    public function __construct(private array $config)
    {
    }

    /**
     * Cria e retorna uma instância do driver de armazenamento especificado.
     *
     * @param string $driver O nome do driver (ex: 'mysql', 'redis', 'file', 'null').
     * @return StorageInterface
     */
    public function make(string $driver): StorageInterface
    {
        return match ($driver) {
            'mysql' => new MySQLStorage(Dispatcher::getDbConnection()),
            'redis' => new RedisStorage(Dispatcher::getRedisConnection()),
            'file' => $this->createFileStorage(),
            'null' => new NullStorage(),
            default => throw new InvalidArgumentException("Driver de armazenamento [{$driver}] não suportado."),
        };
    }

    /**
     * Cria uma instância do FileStorage, garantindo que o diretório de log exista.
     * @return FileStorage
     */
    private function createFileStorage(): FileStorage
    {
        $path = $this->config['file']['events_path'];
        $directory = dirname($path);
        Helpers::ensureDirectoryExists($directory);

        return new FileStorage($path);
    }
}