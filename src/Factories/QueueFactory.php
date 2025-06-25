<?php

namespace Horus\Chronicles\Factories;

use Horus\Chronicles\Contracts\QueueInterface;
use Horus\Chronicles\Contracts\StorageInterface;
use Horus\Chronicles\Queue\RedisQueue;
use Horus\Chronicles\Queue\SyncQueue;
use InvalidArgumentException;

/**
 * Cria instâncias dos drivers de fila.
 * Recebe todas as dependências possíveis para injetar nos drivers.
 */
class QueueFactory
{
    public function __construct(
        private array $config, // Apenas a seção 'queue' da config
        private \Predis\Client $redis,
        private EventFactory $eventFactory,
        private StorageInterface $storage // Necessário para a SyncQueue
    ) {
    }

    public function make(string $driver): QueueInterface
    {
        return match ($driver) {
            'redis' => $this->createRedisQueue(),
            'sync' => $this->createSyncQueue(),
            default => throw new InvalidArgumentException("Driver de fila [{$driver}] não suportado."),
        };
    }

    private function createRedisQueue(): RedisQueue
    {
        $queueConfig = $this->config['redis'];
        return new RedisQueue(
            $this->redis,
            $this->eventFactory,
            $queueConfig['queue_name'],
            $queueConfig['dlq_name']
        );
    }

    private function createSyncQueue(): SyncQueue
    {
        // Agora usa o driver de storage que já foi injetado!
        return new SyncQueue($this->storage, $this->eventFactory);
    }
}