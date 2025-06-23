<?php

namespace Horus\Chronicles\Factories;

use Horus\Chronicles\Contracts\QueueInterface;
use Horus\Chronicles\Core\Dispatcher;
use Horus\Chronicles\Queue\RedisQueue;
use Horus\Chronicles\Queue\SyncQueue;
use InvalidArgumentException;

/**
 * Class QueueFactory
 *
 * Cria instâncias dos drivers de fila com base na configuração.
 */
class QueueFactory
{
    /**
     * @param array $config A seção 'queue' da configuração principal.
     */
    public function __construct(private array $config)
    {
    }

    /**
     * Cria e retorna uma instância do driver de fila especificado.
     *
     * @param string $driver O nome do driver (ex: 'redis', 'sync').
     * @return QueueInterface
     */
    public function make(string $driver): QueueInterface
    {
        return match ($driver) {
            'redis' => $this->createRedisQueue(),
            'sync' => $this->createSyncQueue(),
            default => throw new InvalidArgumentException("Driver de fila [{$driver}] não suportado."),
        };
    }

    /**
     * Cria uma instância do RedisQueue, injetando suas dependências.
     * @return RedisQueue
     */
    private function createRedisQueue(): RedisQueue
    {
        $redisConnection = Dispatcher::getRedisConnection();
        $eventFactory = new EventFactory(); // A fábrica de eventos não tem estado, pode ser instanciada aqui.
        $queueConfig = $this->config['redis'];
        
        return new RedisQueue(
            $redisConnection,
            $eventFactory,
            $queueConfig['queue_name'],
            $queueConfig['dlq_name']
        );
    }

    /**
     * Cria uma instância do SyncQueue.
     * O SyncQueue precisa de um driver de armazenamento para funcionar.
     * @return SyncQueue
     */
    private function createSyncQueue(): SyncQueue
    {
        $storageDriverName = Dispatcher::getConfig('storage_driver');
        $storageDriver = Dispatcher::getStorageFactory()->make($storageDriverName);
        $eventFactory = new EventFactory();

        return new SyncQueue($storageDriver, $eventFactory);
    }
}