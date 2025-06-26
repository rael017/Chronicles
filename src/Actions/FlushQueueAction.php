<?php

namespace Horus\Chronicles\Actions;

use Horus\Chronicles\Contracts\QueueInterface;

class FlushQueueAction
{
    public function __construct(private QueueInterface $queue)
    {
    }

    /**
     * Limpa a fila principal e retorna o número de itens removidos.
     *
     * @return int
     */
    public function execute(): int
    {
        return $this->queue->flushQueue();
    }
}