<?php

namespace Horus\Chronicles\Queue;

use Horus\Chronicles\Contracts\QueueInterface;
use Horus\Chronicles\Contracts\StorageInterface;
use Horus\Chronicles\Factories\EventFactory;
use Throwable;

/**
 * Class SyncQueue
 *
 * Implementação de fila "síncrona". Não enfileira de verdade; em vez disso,
 * processa e armazena o evento imediatamente. Útil para ambientes de
 * desenvolvimento e testes onde não se quer rodar um worker.
 */
class SyncQueue implements QueueInterface
{
    public function __construct(
        private StorageInterface $storage,
        private EventFactory $eventFactory
    ) {}

    /**
     * Processa e armazena o evento imediatamente.
     * @param string $payload
     */
    public function push(string $payload): void
    {
        try {
            $event = $this->eventFactory->make($payload);
            $this->storage->store($event);
        } catch (Throwable $e) {
            // Em modo síncrono, se a persistência falhar, logamos o erro mas não quebramos a app.
            error_log('Chronicles Sync Mode Error: Falha ao armazenar evento. Erro: ' . $e->getMessage());
        }
    }

    // Métodos de fila não são aplicáveis no modo síncrono.
    public function pop(): ?string { return null; }
    public function moveToDlq(string $payload): void {}
    public function getQueueName(): string { return 'sync'; }
    public function getDlqName(): ?string { return null; }
    public function getQueueSize(): int { return 0; }
    public function getDlqSize(): int { return 0; }
    public function flushQueue(): int { return 0; }
    public function inspectDlq(int $limit = 50): array { return []; }
    public function removeFromDlq(string $payload): int { return 0; }
}