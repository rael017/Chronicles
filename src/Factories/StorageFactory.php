<?php

namespace Horus\Chronicles\Factories;

use Horus\Chronicles\Contracts\StorageInterface;
use Horus\Chronicles\Storage\FileStorage;
use Horus\Chronicles\Storage\MySqlStorage;
use Horus\Chronicles\Storage\NullStorage;
use Horus\Chronicles\Storage\RedisStorage;
use Horus\Chronicles\Utils\Helpers;
use InvalidArgumentException;

/**
 * Cria instâncias dos drivers de armazenamento.
 * Recebe as dependências prontas para injetar nos drivers.
 */
class StorageFactory
{
    public function __construct(
        private \PDO $pdo,
        private \Predis\Client $redis,
        private string $filePath
    ) {
    }

    public function make(string $driver): StorageInterface
    {
        return match ($driver) {
            'mysql' => new MySqlStorage($this->pdo),
            'redis' => new RedisStorage($this->redis),
            'file' => $this->createFileStorage(),
            'null' => new NullStorage(),
            default => throw new InvalidArgumentException("Driver de armazenamento [{$driver}] não suportado."),
        };
    }

    private function createFileStorage(): FileStorage
    {
        Helpers::ensureDirectoryExists(dirname($this->filePath));
        return new FileStorage($this->filePath);
    }
}